<?php
session_start();
include 'db_connect.php';

// 1. ⛔ Access Control: เฉพาะ Admin
if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: dashboard_tech.php");
    exit();
}

$page_title = 'Admin Dashboard';

try {
    // --- QUERY 1: สถานะงาน ---
    $sql_status = "SELECT status, COUNT(*) as count FROM repair_requests GROUP BY status";
    $stmt = $pdo->query($sql_status);
    $status_data = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $total_jobs = array_sum($status_data);
    $pending = $status_data['Pending'] ?? 0;
    $in_progress = $status_data['In Progress'] ?? 0;
    $completed = $status_data['Completed'] ?? 0;

    // --- QUERY 2: Top Technicians ---
    $sql_tech = "SELECT s.full_name, COUNT(r.request_id) as job_count 
                 FROM repair_requests r JOIN staffs s ON r.technician_id = s.staff_id
                 WHERE r.status IN ('In Progress', 'Completed')
                 GROUP BY r.technician_id ORDER BY job_count DESC LIMIT 5";
    $stmt_tech = $pdo->query($sql_tech);
    $tech_stats = $stmt_tech->fetchAll(PDO::FETCH_ASSOC);
    
    $tech_names = array_column($tech_stats, 'full_name');
    $tech_counts = array_column($tech_stats, 'job_count');

    // --- QUERY 3: 5 รายการล่าสุด (ย่อข้อมูล) ---
    $sql_recent = "SELECT r.request_no, r.status, s.full_name AS req_name, t.full_name AS tech_name
                   FROM repair_requests r
                   LEFT JOIN staffs s ON r.requester_id = s.staff_id
                   LEFT JOIN staffs t ON r.technician_id = t.staff_id
                   ORDER BY r.request_date DESC LIMIT 6"; // เพิ่มเป็น 6 รายการให้เต็มพื้นที่
    $stmt_recent = $pdo->query($sql_recent);
    $recent_jobs = $stmt_recent->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) { $error_message = $e->getMessage(); }

include 'includes/header.php';
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    /* บังคับให้ Container เต็มความสูงและไม่เลื่อน */
    .dashboard-wrapper {
        max-width: 1400px;
        margin: 0 auto;
        padding: 10px 20px;
        height: calc(100vh - 90px); /* ลบความสูง Header */
        display: flex;
        flex-direction: column;
        gap: 15px;
        overflow: hidden; /* ห้าม Scroll */
    }

    /* แถวที่ 1: การ์ดตัวเลข (Stat Cards) */
    .stats-row {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 15px;
        height: 15%; /* ความสูง 15% ของหน้า */
    }
    .compact-card {
        background: #fff;
        border-radius: 12px;
        padding: 15px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        display: flex;
        align-items: center;
        justify-content: space-between;
        border: 1px solid #e2e8f0;
    }
    .compact-card h3 { font-size: 0.9rem; color: #64748b; margin: 0; }
    .compact-card .num { font-size: 2rem; font-weight: 800; line-height: 1; }
    .compact-card .icon { font-size: 2rem; opacity: 0.8; }

    /* แถวที่ 2: เนื้อหาหลัก (แบ่ง 3 คอลัมน์) */
    .main-content-row {
        display: grid;
        grid-template-columns: 1fr 1.5fr 1.2fr; /* กราฟกลม | กราฟแท่ง | ตาราง */
        gap: 15px;
        height: 85%; /* ใช้พื้นที่ที่เหลือทั้งหมด */
    }
    .panel {
        background: #fff;
        border-radius: 12px;
        padding: 15px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        border: 1px solid #e2e8f0;
        display: flex;
        flex-direction: column;
    }
    .panel-header {
        font-size: 1rem;
        font-weight: 700;
        margin-bottom: 10px;
        color: #1e293b;
        border-bottom: 1px solid #f1f5f9;
        padding-bottom: 5px;
    }
    
    /* ปรับขนาดกราฟให้พอดี */
    .chart-container {
        flex-grow: 1;
        position: relative;
        width: 100%;
        height: 100%;
        min-height: 0; /* สำคัญสำหรับ Flex item */
    }

    /* ตารางย่อ */
    .mini-table {
        width: 100%;
        font-size: 0.85rem;
        border-collapse: collapse;
    }
    .mini-table th { text-align: left; color: #64748b; padding: 8px; border-bottom: 1px solid #e2e8f0; }
    .mini-table td { padding: 8px; border-bottom: 1px solid #f1f5f9; }
    .mini-badge { padding: 2px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 600; }
    
    /* Responsive: ถ้าจอเล็กมากๆ ให้เลื่อนได้ */
    @media (max-width: 1024px) {
        .dashboard-wrapper { height: auto; overflow: auto; }
        .main-content-row { grid-template-columns: 1fr; height: auto; }
        .chart-container { height: 300px; }
    }
</style>

<div class="dashboard-wrapper">
    <div class="stats-row">
        <div class="compact-card" style="border-left: 4px solid #64748b;">
            <div>
                <h3>ทั้งหมด</h3>
                <div class="num"><?php echo number_format($total_jobs); ?></div>
            </div>
            <div class="icon">📁</div>
        </div>
        <div class="compact-card" style="border-left: 4px solid #f59e0b;">
            <div>
                <h3>รอคิว</h3>
                <div class="num" style="color: #f59e0b;"><?php echo number_format($pending); ?></div>
            </div>
            <div class="icon">⏳</div>
        </div>
        <div class="compact-card" style="border-left: 4px solid #3b82f6;">
            <div>
                <h3>กำลังซ่อม</h3>
                <div class="num" style="color: #3b82f6;"><?php echo number_format($in_progress); ?></div>
            </div>
            <div class="icon">🔧</div>
        </div>
        <div class="compact-card" style="border-left: 4px solid #10b981;">
            <div>
                <h3>เสร็จสิ้น</h3>
                <div class="num" style="color: #10b981;"><?php echo number_format($completed); ?></div>
            </div>
            <div class="icon">✅</div>
        </div>
    </div>

    <div class="main-content-row">
        
        <div class="panel">
            <div class="panel-header">📈 สัดส่วนงาน</div>
            <div class="chart-container">
                <canvas id="statusChart"></canvas>
            </div>
        </div>

        <div class="panel">
            <div class="panel-header">🏆 ประสิทธิภาพช่าง (งาน)</div>
            <div class="chart-container">
                <canvas id="techChart"></canvas>
            </div>
        </div>

        <div class="panel">
            <div class="panel-header">🕒 ความเคลื่อนไหวล่าสุด</div>
            <div style="overflow-y: auto;">
                <table class="mini-table">
                    <thead>
                        <tr>
                            <th>เลขที่</th>
                            <th>ผู้แจ้ง</th>
                            <th>สถานะ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_jobs as $job): ?>
                        <tr>
                            <td><b><?php echo $job['request_no']; ?></b></td>
                            <td><?php echo mb_substr($job['req_name'], 0, 15) . '..'; ?></td>
                            <td>
                                <?php 
                                    $s = $job['status'];
                                    $color = ($s=='Pending')?'#f59e0b':(($s=='In Progress')?'#3b82f6':'#10b981');
                                ?>
                                <span class="mini-badge" style="background:<?php echo $color; ?>20; color:<?php echo $color; ?>;">
                                    <?php echo $s; ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div style="text-align: center; margin-top: auto; padding-top: 10px;">
                <a href="dashboard_tech.php" style="font-size: 0.85rem; color: #3b82f6; text-decoration: none;">ดูทั้งหมด &rarr;</a>
            </div>
        </div>
    </div>
</div>

<script>
    // Config กราฟให้ดูเรียบง่าย ประหยัดพื้นที่
    const commonOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } }
        }
    };

    // 1. กราฟวงกลม
    new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: {
            labels: ['รอคิว', 'กำลังซ่อม', 'เสร็จ', 'ยกเลิก'],
            datasets: [{
                data: [<?php echo "$pending, $in_progress, $completed, " . ($status_data['Canceled']??0); ?>],
                backgroundColor: ['#f59e0b', '#3b82f6', '#10b981', '#ef4444'],
                borderWidth: 0
            }]
        },
        options: commonOptions
    });

    // 2. กราฟแท่ง
    new Chart(document.getElementById('techChart'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($tech_names); ?>,
            datasets: [{
                label: 'งานที่รับ',
                data: <?php echo json_encode($tech_counts); ?>,
                backgroundColor: '#3b82f6',
                borderRadius: 4
            }]
        },
        options: {
            ...commonOptions,
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 } },
                x: { ticks: { font: { size: 10 } } }
            },
            plugins: { legend: { display: false } } // ซ่อน Legend เพื่อประหยัดที่
        }
    });
</script>

</body>
</html>