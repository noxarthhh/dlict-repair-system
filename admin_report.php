<?php
session_start();
include 'db_connect.php';

// 1. ⛔ Access Control
if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: dashboard_tech.php");
    exit();
}

$page_title = 'Admin Executive Report';

try {
    // --- QUERY 1: สรุปสถานะงาน ---
    $sql_status = "SELECT status, COUNT(*) as count FROM repair_requests GROUP BY status";
    $stmt = $pdo->query($sql_status);
    $status_data = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $total_jobs = array_sum($status_data);
    $pending = $status_data['Pending'] ?? 0;
    $in_progress = $status_data['In Progress'] ?? 0;
    $completed = $status_data['Completed'] ?? 0;

    // --- QUERY 2: ประสิทธิภาพช่าง (Top 5) ---
    $sql_tech = "SELECT s.full_name, COUNT(r.request_id) as job_count 
                 FROM repair_requests r JOIN staffs s ON r.technician_id = s.staff_id
                 WHERE r.status IN ('In Progress', 'Completed')
                 GROUP BY r.technician_id ORDER BY job_count DESC LIMIT 5";
    $stmt_tech = $pdo->query($sql_tech);
    $tech_stats = $stmt_tech->fetchAll(PDO::FETCH_ASSOC);
    
    $tech_names = array_column($tech_stats, 'full_name');
    $tech_counts = array_column($tech_stats, 'job_count');

} catch (PDOException $e) { $error_message = $e->getMessage(); }

include 'includes/header.php';
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    /* บังคับหน้าเดียวจบ */
    body { overflow: hidden; background-color: #f8fafc; }

    /* Layout หลัก */
    .report-wrapper {
        height: calc(100vh - 80px);
        padding: 20px 30px;
        display: flex;
        flex-direction: column;
        gap: 25px;
        max-width: 1600px; margin: 0 auto;
        animation: fadeIn 0.8s ease-out;
    }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

    /* Header */
    .report-header h1 { font-size: 2rem; color: #1e293b; margin: 0; font-weight: 800; }
    .report-header p { color: #64748b; margin: 5px 0 0; }

    /* 1. Stat Cards (หรูหรา) */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
        flex-shrink: 0;
    }
    .stat-card-premium {
        background: #fff;
        border-radius: 20px;
        padding: 25px;
        position: relative;
        overflow: hidden;
        box-shadow: 0 10px 30px -10px rgba(0,0,0,0.1);
        transition: transform 0.3s;
        border: 1px solid rgba(255,255,255,0.5);
    }
    .stat-card-premium:hover { transform: translateY(-5px); box-shadow: 0 20px 40px -10px rgba(0,0,0,0.15); }
    
    /* วงกลมตกแต่ง */
    .stat-deco { position: absolute; top: -20px; right: -20px; width: 100px; height: 100px; border-radius: 50%; opacity: 0.15; }

    .stat-title { font-size: 0.95rem; font-weight: 600; color: #64748b; z-index: 2; position: relative; }
    .stat-value { font-size: 2.8rem; font-weight: 800; line-height: 1.1; margin-top: 5px; z-index: 2; position: relative; color: #1e293b; }
    
    /* สีเฉพาะการ์ด */
    .card-total .stat-value { color: #3b82f6; } .card-total .stat-deco { background: #3b82f6; }
    .card-pending .stat-value { color: #f59e0b; } .card-pending .stat-deco { background: #f59e0b; }
    .card-progress .stat-value { color: #0ea5e9; } .card-progress .stat-deco { background: #0ea5e9; }
    .card-done .stat-value { color: #10b981; } .card-done .stat-deco { background: #10b981; }

    /* 2. Charts Section */
    .charts-grid {
        display: grid;
        grid-template-columns: 1fr 1.5fr; /* ซ้ายเล็ก ขวาใหญ่ */
        gap: 20px;
        flex-grow: 1;
        min-height: 0;
    }
    
    .chart-card {
        background: #fff;
        border-radius: 20px;
        padding: 25px;
        box-shadow: var(--shadow);
        display: flex; flex-direction: column;
        border: 1px solid #e2e8f0;
    }
    .chart-header { font-size: 1.1rem; font-weight: 700; color: #334155; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
    .chart-body { flex-grow: 1; position: relative; width: 100%; min-height: 0; display: flex; align-items: center; justify-content: center; }

    /* Responsive */
    @media (max-width: 1024px) {
        body { overflow: auto; }
        .report-wrapper { height: auto; }
        .stats-grid { grid-template-columns: 1fr 1fr; }
        .charts-grid { grid-template-columns: 1fr; }
        .chart-card { height: 400px; }
    }
</style>

<div class="report-wrapper">
    
    <div class="report-header">
        <h1>📊 Executive Summary</h1>
        <p>รายงานภาพรวมประสิทธิภาพงานซ่อมบำรุงประจำเดือน</p>
    </div>

    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <div class="stats-grid">
        <div class="stat-card-premium card-total">
            <div class="stat-deco"></div>
            <div class="stat-title">งานทั้งหมด</div>
            <div class="stat-value"><?php echo number_format($total_jobs); ?></div>
            <div style="margin-top:10px; font-size:0.85rem; color:#94a3b8;">Items Total</div>
        </div>
        <div class="stat-card-premium card-pending">
            <div class="stat-deco"></div>
            <div class="stat-title">รอรับเรื่อง</div>
            <div class="stat-value"><?php echo number_format($pending); ?></div>
            <div style="margin-top:10px; font-size:0.85rem; color:#94a3b8;">Pending Request</div>
        </div>
        <div class="stat-card-premium card-progress">
            <div class="stat-deco"></div>
            <div class="stat-title">กำลังดำเนินการ</div>
            <div class="stat-value"><?php echo number_format($in_progress); ?></div>
            <div style="margin-top:10px; font-size:0.85rem; color:#94a3b8;">In Progress</div>
        </div>
        <div class="stat-card-premium card-done">
            <div class="stat-deco"></div>
            <div class="stat-title">เสร็จสิ้น</div>
            <div class="stat-value"><?php echo number_format($completed); ?></div>
            <div style="margin-top:10px; font-size:0.85rem; color:#94a3b8;">Completed Jobs</div>
        </div>
    </div>

    <div class="charts-grid">
        
        <div class="chart-card">
            <div class="chart-header">
                <i class="fa-solid fa-chart-pie" style="color:var(--primary);"></i> สัดส่วนสถานะงาน
            </div>
            <div class="chart-body">
                <canvas id="statusChart"></canvas>
            </div>
        </div>

        <div class="chart-card">
            <div class="chart-header">
                <i class="fa-solid fa-trophy" style="color:var(--warning);"></i> 5 อันดับช่างยอดเยี่ยม
            </div>
            <div class="chart-body">
                <canvas id="techChart"></canvas>
            </div>
        </div>

    </div>
</div>

<script>
    // Config กลาง
    Chart.defaults.font.family = "'Sarabun', sans-serif";
    Chart.defaults.color = '#64748b';

    // 1. Status Chart (Doughnut)
    new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: {
            labels: ['รอคิว', 'กำลังซ่อม', 'เสร็จสิ้น'],
            datasets: [{
                data: [<?php echo "$pending, $in_progress, $completed"; ?>],
                backgroundColor: ['#f59e0b', '#0ea5e9', '#10b981'],
                hoverOffset: 15,
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%', /* รูกลางใหญ่ขึ้น ดูModern */
            plugins: {
                legend: { position: 'bottom', labels: { padding: 20, usePointStyle: true } }
            }
        }
    });

    // 2. Tech Chart (Bar)
    new Chart(document.getElementById('techChart'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($tech_names); ?>,
            datasets: [{
                label: 'จำนวนงานที่ปิดได้',
                data: <?php echo json_encode($tech_counts); ?>,
                backgroundColor: '#3b82f6',
                borderRadius: 8, /* แท่งมน */
                barThickness: 40
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { stepSize: 1 } },
                x: { grid: { display: false } }
            },
            plugins: { legend: { display: false } }
        }
    });
</script>

<?php 
// include 'includes/footer.php'; 
?>
</body>
</html>