<?php
session_start();
include 'db_connect.php';

// 1. ⛔ Access Control
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

    // --- QUERY 2: Top Technicians (3 อันดับแรกพอ เพื่อประหยัดที่) ---
    $sql_tech = "SELECT s.full_name, COUNT(r.request_id) as job_count 
                 FROM repair_requests r JOIN staffs s ON r.technician_id = s.staff_id
                 WHERE r.status IN ('In Progress', 'Completed')
                 GROUP BY r.technician_id ORDER BY job_count DESC LIMIT 3";
    $stmt_tech = $pdo->query($sql_tech);
    $tech_stats = $stmt_tech->fetchAll(PDO::FETCH_ASSOC);
    
    $tech_names = array_column($tech_stats, 'full_name');
    $tech_counts = array_column($tech_stats, 'job_count');

    // --- QUERY 3: 7 รายการล่าสุด (เพิ่มจำนวนหน่อยเพราะพื้นที่เหลือ) ---
    $sql_recent = "SELECT r.request_no, r.status, s.full_name AS req_name, t.full_name AS tech_name
                   FROM repair_requests r
                   LEFT JOIN staffs s ON r.requester_id = s.staff_id
                   LEFT JOIN staffs t ON r.technician_id = t.staff_id
                   ORDER BY r.request_date DESC LIMIT 7";
    $stmt_recent = $pdo->query($sql_recent);
    $recent_jobs = $stmt_recent->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) { $error_message = $e->getMessage(); }

include 'includes/header.php';
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    /* ปรับแต่ง CSS เฉพาะหน้านี้เพื่อทำ Single Screen */
    
    /* ล็อกความสูงหน้าจอ */
    body { overflow: hidden; } 
    
    /* Container หลัก จัดเป็น Grid */
    .dashboard-container {
        height: calc(100vh - 80px); /* ความสูงเต็มจอ - ความสูง Header */
        display: grid;
        grid-template-rows: auto 1fr; /* แถวบน: หัวข้อ/Stats, แถวล่าง: กราฟ/ตาราง */
        gap: 15px;
        padding: 10px 20px 20px 20px;
        max-width: 1400px;
        margin: 0 auto;
    }

    /* แถวบน: สรุปตัวเลข (Stats Row) */
    .stats-row {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 15px;
    }
    .stat-box {
        background: #fff;
        border-radius: 12px;
        padding: 15px 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        box-shadow: var(--shadow);
        border: 1px solid var(--border);
    }
    .stat-title { font-size: 0.9rem; color: var(--text-muted); font-weight: 600; }
    .stat-value { font-size: 2rem; font-weight: 800; line-height: 1.2; }
    .stat-icon { font-size: 2.2rem; opacity: 0.2; }

    /* แถวล่าง: เนื้อหาหลัก (Main Content) แบ่ง 3 คอลัมน์ */
    .main-row {
        display: grid;
        grid-template-columns: 1fr 1fr 1.2fr; /* กราฟกลม | กราฟแท่ง | ตาราง */
        gap: 15px;
        min-height: 0; /* สำคัญ! ป้องกัน Flex item ล้น */
    }

    /* กล่อง Panel มาตรฐาน */
    .panel {
        background: #fff;
        border-radius: 12px;
        border: 1px solid var(--border);
        box-shadow: var(--shadow);
        display: flex;
        flex-direction: column;
        overflow: hidden; /* ซ่อนส่วนเกิน */
    }
    .panel-head {
        padding: 12px 15px;
        background: #f8fafc;
        border-bottom: 1px solid var(--border);
        font-weight: 700;
        color: var(--text-main);
        display: flex; justify-content: space-between; align-items: center;
    }
    .panel-body {
        flex: 1;
        padding: 10px;
        position: relative;
        min-height: 0; /* เพื่อให้ Chart ย่อขยายได้ */
        display: flex; justify-content: center; align-items: center;
    }

    /* ตารางขนาดเล็ก */
    .mini-table-wrapper {
        width: 100%;
        height: 100%;
        overflow-y: auto; /* ให้ Scroll ได้เฉพาะในตาราง */
    }
    .mini-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
    .mini-table th { position: sticky; top: 0; background: #fff; z-index: 1; text-align: left; padding: 8px; border-bottom: 2px solid var(--border); color: var(--text-muted); }
    .mini-table td { padding: 8px; border-bottom: 1px solid #f1f5f9; }
    .mini-table tr:last-child td { border-bottom: none; }
    
    /* Responsive: ถ้าจอเล็กเกินไป ให้กลับมา Scroll ได้ */
    @media (max-width: 1024px) {
        body { overflow: auto; }
        .dashboard-container { height: auto; display: block; }
        .stats-row, .main-row { grid-template-columns: 1fr; margin-bottom: 20px; }
        .panel { height: 300px; margin-bottom: 15px; }
    }
</style>

<div class="dashboard-container">
    
    <div class="stats-row">
        <div class="stat-box" style="border-left: 4px solid #64748b;">
            <div>
                <div class="stat-title">ทั้งหมด</div>
                <div class="stat-value"><?php echo number_format($total_jobs); ?></div>
            </div>
            <i class="fa-solid fa-folder-open stat-icon" style="color:#64748b;"></i>
        </div>
        <div class="stat-box" style="border-left: 4px solid #f59e0b;">
            <div>
                <div class="stat-title">รอคิว</div>
                <div class="stat-value" style="color: #f59e0b;"><?php echo number_format($pending); ?></div>
            </div>
            <i class="fa-solid fa-clock stat-icon" style="color:#f59e0b;"></i>
        </div>
        <div class="stat-box" style="border-left: 4px solid #3b82f6;">
            <div>
                <div class="stat-title">กำลังซ่อม</div>
                <div class="stat-value" style="color: #3b82f6;"><?php echo number_format($in_progress); ?></div>
            </div>
            <i class="fa-solid fa-wrench stat-icon" style="color:#3b82f6;"></i>
        </div>
        <div class="stat-box" style="border-left: 4px solid #10b981;">
            <div>
                <div class="stat-title">เสร็จสิ้น</div>
                <div class="stat-value" style="color: #10b981;"><?php echo number_format($completed); ?></div>
            </div>
            <i class="fa-solid fa-circle-check stat-icon" style="color:#10b981;"></i>
        </div>
    </div>

    <div class="main-row">
        
        <div class="panel">
            <div class="panel-head">📈 สัดส่วนงาน</div>
            <div class="panel-body">
                <canvas id="statusChart"></canvas>
            </div>
        </div>

        <div class="panel">
            <div class="panel-head">🏆 ช่างยอดฝีมือ (Top 3)</div>
            <div class="panel-body">
                <canvas id="techChart"></canvas>
            </div>
        </div>

        <div class="panel">
            <div class="panel-head">
                <span>🕒 ความเคลื่อนไหวล่าสุด</span>
                <a href="dashboard_tech.php" style="font-size:0.8rem; color:var(--primary);">ดูทั้งหมด</a>
            </div>
            <div class="panel-body" style="padding: 0; display: block;">
                <div class="mini-table-wrapper">
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
                                <td title="<?php echo htmlspecialchars($job['req_name']); ?>">
                                    <?php echo mb_substr($job['req_name'], 0, 12) . '..'; ?>
                                </td>
                                <td>
                                    <?php 
                                        $s = $job['status'];
                                        $color = match($s) { 'Pending'=>'#f59e0b', 'In Progress'=>'#3b82f6', 'Completed'=>'#10b981', default=>'#666' };
                                    ?>
                                    <span style="color:<?php echo $color; ?>; font-weight:700; font-size:0.75rem; background:<?php echo $color; ?>15; padding:2px 6px; border-radius:4px;">
                                        <?php echo $s; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
    const commonOpts = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } } }
    };

    // 1. Donut Chart
    new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: {
            labels: ['รอคิว', 'กำลังซ่อม', 'เสร็จ'],
            datasets: [{
                data: [<?php echo "$pending, $in_progress, $completed"; ?>],
                backgroundColor: ['#f59e0b', '#3b82f6', '#10b981'],
                borderWidth: 0
            }]
        },
        options: commonOpts
    });

    // 2. Bar Chart
    new Chart(document.getElementById('techChart'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($tech_names); ?>,
            datasets: [{
                label: 'งานที่รับ',
                data: <?php echo json_encode($tech_counts); ?>,
                backgroundColor: '#3b82f6',
                borderRadius: 4,
                barThickness: 30
            }]
        },
        options: {
            ...commonOpts,
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } }, x: { ticks: { font: { size: 10 } } } },
            plugins: { legend: { display: false } }
        }
    });
</script>

<?php 
// ไม่ต้องใส่ Footer ในหน้านี้เพื่อประหยัดพื้นที่แนวตั้ง
// include 'includes/footer.php'; 
?>
</body>
</html>