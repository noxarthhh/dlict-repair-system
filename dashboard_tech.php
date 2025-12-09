<?php
session_start();
include 'db_connect.php'; 

// 1. ตรวจสอบสิทธิ์
if (!isset($_SESSION['logged_in']) || ($_SESSION['user_role'] != 'technician' && $_SESSION['user_role'] != 'admin')) {
    header("Location: new_request.php");
    exit();
}

$page_title = 'Dashboard - จัดการงานซ่อม';

// 2. ดึงข้อมูลสรุปยอด (Stats)
$stats = [
    'total' => 0, 'pending' => 0, 'progress' => 0, 'completed' => 0
];
try {
    $stmt_stats = $pdo->query("SELECT status, COUNT(*) as count FROM repair_requests GROUP BY status");
    while ($row = $stmt_stats->fetch()) {
        $s = strtolower(trim($row['status']));
        if ($s == 'pending') $stats['pending'] = $row['count'];
        elseif ($s == 'in progress') $stats['progress'] = $row['count'];
        elseif ($s == 'completed') $stats['completed'] = $row['count'];
    }
    $stats['total'] = array_sum($stats);
} catch (Exception $e) {}

// 3. ดึงรายการงานซ่อม
$sql = "
    SELECT 
        rr.request_id, rr.request_no, rr.issue_details, rr.status, rr.request_date, rr.manual_asset, rr.problem_types,
        a.asset_number, a.asset_type,
        s_req.full_name AS requester_name,
        s_tech.full_name AS technician_name
    FROM repair_requests rr
    LEFT JOIN assets a ON rr.asset_id = a.asset_id
    LEFT JOIN staffs s_req ON rr.requester_id = s_req.staff_id
    LEFT JOIN staffs s_tech ON rr.technician_id = s_tech.staff_id
    ORDER BY FIELD(rr.status, 'Pending', 'In Progress', 'Completed') ASC, rr.request_date DESC
";
$stmt = $pdo->query($sql);
$requests = $stmt->fetchAll();

include 'includes/header.php'; 
?>

<style>
    /* Layout Lock */
    body { overflow: hidden; }

    .dashboard-wrapper {
        height: calc(100vh - 80px); /* เต็มความสูงที่เหลือจาก Header */
        display: flex; flex-direction: column;
        padding: 15px 25px; gap: 20px;
        max-width: 100%; margin: 0 auto;
        animation: fadeIn 0.6s ease-out;
    }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

    /* 1. Stat Cards Grid (การ์ดสรุปยอด) */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
        flex-shrink: 0; /* ห้ามหด */
    }

    .stat-card {
        background: var(--card-bg);
        border-radius: 16px;
        padding: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: var(--shadow);
        border: 1px solid var(--border);
        transition: transform 0.2s, box-shadow 0.2s;
        position: relative;
        overflow: hidden;
    }
    .stat-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
    
    /* เอฟเฟกต์วงกลมตกแต่ง */
    .stat-card::after {
        content: ''; position: absolute; right: -20px; bottom: -20px;
        width: 100px; height: 100px; border-radius: 50%;
        background: currentColor; opacity: 0.1;
    }

    .stat-info h3 { margin: 0; font-size: 0.9rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
    .stat-info .count { margin: 5px 0 0 0; font-size: 2.2rem; font-weight: 800; line-height: 1; }
    
    .stat-icon {
        width: 55px; height: 55px;
        border-radius: 14px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.8rem;
    }

    /* 2. Main Table Area */
    .table-section {
        flex-grow: 1; /* ยืดเต็มพื้นที่ที่เหลือ */
        background: var(--card-bg);
        border-radius: 16px;
        border: 1px solid var(--border);
        box-shadow: var(--shadow);
        display: flex; flex-direction: column;
        overflow: hidden; /* ห้ามการ์ดล้น */
    }
    
    .table-header-title {
        padding: 15px 20px;
        border-bottom: 1px solid var(--border);
        display: flex; align-items: center; gap: 10px;
        font-size: 1.1rem; font-weight: 700; color: var(--text-main);
        background: rgba(var(--input-bg), 0.5);
    }

    .table-scroll { flex-grow: 1; overflow: auto; } /* Scroll เฉพาะตาราง */

    table { width: 100%; border-collapse: collapse; }
    th {
        position: sticky; top: 0; z-index: 10;
        background: var(--input-bg);
        padding: 18px 15px;
        text-align: left; font-weight: 700; color: var(--text-muted);
        border-bottom: 2px solid var(--border); white-space: nowrap;
    }
    td { padding: 15px; border-bottom: 1px solid var(--border); vertical-align: middle; font-size: 0.95rem; color: var(--text-main); }
    tr:hover { background-color: var(--input-bg); transition: 0.1s; }

    /* Action Buttons */
    td[data-label="ดำเนินการ"] { display: flex; gap: 8px; white-space: nowrap; }
    .btn-icon { width: 35px; height: 35px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; font-size: 1rem; }

    /* Colors */
    .c-blue { color: #3b82f6; } .bg-blue-soft { background: #eff6ff; color: #3b82f6; }
    .c-orange { color: #f59e0b; } .bg-orange-soft { background: #fffbeb; color: #f59e0b; }
    .c-purple { color: #8b5cf6; } .bg-purple-soft { background: #f5f3ff; color: #8b5cf6; }
    .c-green { color: #10b981; } .bg-green-soft { background: #ecfdf5; color: #10b981; }

    @media (max-width: 1024px) {
        body { overflow: auto; }
        .dashboard-wrapper { height: auto; display: block; }
        .stats-grid { grid-template-columns: repeat(2, 1fr); margin-bottom: 20px; }
        .table-section { height: 600px; }
    }
</style>

<div class="dashboard-wrapper">
    
    <div class="stats-grid">
        <div class="stat-card" style="border-left: 5px solid #64748b; color: #64748b;">
            <div class="stat-info">
                <h3>ทั้งหมด</h3>
                <div class="count" style="color: var(--text-main);"><?php echo number_format($stats['total']); ?></div>
            </div>
            <div class="stat-icon" style="background: #f1f5f9; color: #64748b;">
                <i class="fa-solid fa-folder-open"></i>
            </div>
        </div>

        <div class="stat-card" style="border-left: 5px solid var(--warning); color: var(--warning);">
            <div class="stat-info">
                <h3>รอคิว (Pending)</h3>
                <div class="count c-orange"><?php echo number_format($stats['pending']); ?></div>
            </div>
            <div class="stat-icon bg-orange-soft">
                <i class="fa-regular fa-clock"></i>
            </div>
        </div>

        <div class="stat-card" style="border-left: 5px solid var(--info); color: var(--info);">
            <div class="stat-info">
                <h3>กำลังซ่อม</h3>
                <div class="count c-blue"><?php echo number_format($stats['progress']); ?></div>
            </div>
            <div class="stat-icon bg-blue-soft">
                <i class="fa-solid fa-wrench"></i>
            </div>
        </div>

        <div class="stat-card" style="border-left: 5px solid var(--success); color: var(--success);">
            <div class="stat-info">
                <h3>เสร็จสิ้น</h3>
                <div class="count c-green"><?php echo number_format($stats['completed']); ?></div>
            </div>
            <div class="stat-icon bg-green-soft">
                <i class="fa-solid fa-check-circle"></i>
            </div>
        </div>
    </div>

    <div class="table-section">
        <div class="table-header-title">
            <i class="fa-solid fa-list-ul" style="color:var(--primary);"></i> รายการแจ้งซ่อมล่าสุด
        </div>
        
        <div class="table-scroll">
            <?php if (count($requests) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th width="8%">เลขที่</th>
                        <th width="10%">สถานะ</th>
                        <th width="10%">ชนิด</th>
                        <th width="12%">ทะเบียน</th>
                        <th width="15%">ผู้แจ้ง</th>
                        <th width="20%">อาการ</th>
                        <th width="10%">วันที่</th>
                        <th width="10%">ช่าง</th>
                        <th width="5%">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requests as $request): ?>
                        <?php 
                            $show_asset = !empty($request['asset_number']) ? $request['asset_number'] : ($request['manual_asset'] ?: '-');
                            $show_type = !empty($request['asset_type']) ? $request['asset_type'] : ($request['problem_types'] ?: '-');
                            $status_class = 'status-' . strtolower(str_replace(' ', '_', $request['status']));
                        ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($request['request_no']); ?></strong></td>
                        
                        <td><span class="status-badge <?php echo $status_class; ?>"><?php echo htmlspecialchars($request['status']); ?></span></td>
                        
                        <td><?php echo htmlspecialchars($show_type); ?></td>
                        
                        <td><?php echo htmlspecialchars($show_asset); ?></td>
                        <td><?php echo htmlspecialchars($request['requester_name']); ?></td>
                        <td title="<?php echo htmlspecialchars($request['issue_details']); ?>">
                            <?php echo htmlspecialchars(mb_substr($request['issue_details'], 0, 30)); ?>...
                        </td>
                        <td><?php echo date('d/m/Y', strtotime($request['request_date'])); ?></td>
                        <td><?php echo htmlspecialchars($request['technician_name'] ?: '-'); ?></td>
                        
                        <td data-label="ดำเนินการ">
                            <?php if ($request['status'] == 'Pending'): ?>
                                <a href="#" class="btn-action btn-icon" onclick="confirmAccept(event, '<?php echo $request['request_id']; ?>', '<?php echo $request['request_no']; ?>');" title="รับงาน">
                                    <i class="fa-solid fa-hand-holding"></i>
                                </a>
                            <?php endif; ?>
                            <a href="repair_details.php?id=<?php echo $request['request_id']; ?>" class="btn-detail btn-icon" title="ดูรายละเอียด">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
                <div style="text-align:center; padding:50px; color:var(--text-muted);">
                    <div style="font-size:3rem; margin-bottom:10px;">📭</div>
                    <p>ไม่มีรายการงานซ่อมในระบบ</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function confirmAccept(e, id, no) { 
    e.preventDefault(); 
    Swal.fire({ 
        title: 'ยืนยันรับงาน?', 
        text: "รับผิดชอบงาน " + no + " ใช่หรือไม่?", 
        icon: 'question', 
        showCancelButton: true, 
        confirmButtonText: 'ใช่, รับงาน', 
        cancelButtonText: 'ยกเลิก',
        customClass: { popup: 'swal-custom-font' }
    }).then((result) => { 
        if (result.isConfirmed) { window.location.href = 'update_status.php?action=accept&id=' + id; } 
    }); 
}

// เช็ค URL param เพื่อแสดง Popup สำเร็จ
const urlParams = new URLSearchParams(window.location.search);
if (urlParams.get('update') === 'success') {
    Swal.fire({
        icon: 'success',
        title: 'สำเร็จ!',
        text: 'อัปเดตสถานะงานเรียบร้อยแล้ว',
        timer: 1500,
        showConfirmButton: false,
        customClass: { popup: 'swal-custom-font' }
    });
}
</script>

<?php 
// include 'includes/footer.php'; 
?>
</body></html>