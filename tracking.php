<?php
session_start();
include 'db_connect.php'; 

// Access Control
if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] !== 'requester') {
    header("Location: dashboard_tech.php");
    exit();
}

$page_title = 'ติดตาสถานะ';
$staff_id = $_SESSION['staff_id'];

// Data Query
$sql = "
    SELECT 
        rr.request_id, rr.request_no, rr.issue_details, rr.status, rr.request_date, rr.manual_asset, rr.problem_types,
        a.asset_number, a.asset_type,
        t.full_name AS technician_name
    FROM repair_requests rr
    LEFT JOIN assets a ON rr.asset_id = a.asset_id
    LEFT JOIN staffs t ON rr.technician_id = t.staff_id
    WHERE rr.requester_id = :staff_id
    ORDER BY rr.request_date DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute(['staff_id' => $staff_id]);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php'; 
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
<script src="https://cdnjs.cloudflare.com/ajax/libs/vanilla-tilt/1.8.0/vanilla-tilt.min.js"></script>

<style>
    /* Global No Scroll */
    body { overflow: hidden; background: linear-gradient(-45deg, #eef2ff, #f0fdf4, #fff7ed, #fdf4ff); background-size: 400% 400%; animation: gradientBG 15s ease infinite; }

    .tracking-wrapper {
        height: calc(100vh - 80px); /* เต็มความสูงที่เหลือ */
        display: flex; flex-direction: column;
        padding: 15px 30px; gap: 20px;
        max-width: 1400px; margin: 0 auto;
        animation: fadeIn 0.8s;
    }

    /* Header Section */
    .tracking-header {
        flex-shrink: 0;
        display: flex; justify-content: space-between; align-items: center;
        margin-bottom: 5px;
    }
    .tracking-header h1 { margin: 0; font-size: 1.8rem; color: var(--text-main); font-weight: 800; display: flex; align-items: center; gap: 10px; }
    
    /* ปุ่มแจ้งซ่อมเพิ่ม (เด่นๆ) */
    .btn-add-new {
        background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);
        color: white; padding: 10px 25px; border-radius: 50px;
        font-weight: 700; font-size: 0.95rem;
        box-shadow: 0 5px 15px rgba(79, 70, 229, 0.3);
        transition: all 0.3s; display: inline-flex; align-items: center; gap: 8px;
    }
    .btn-add-new:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(79, 70, 229, 0.4); }

    /* Table Card (Glass Table) */
    .table-card {
        background: rgba(255, 255, 255, 0.85); /* Glassmorphism */
        backdrop-filter: blur(15px);
        border-radius: 20px;
        border: 1px solid rgba(255, 255, 255, 0.7);
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        display: flex; flex-direction: column;
        flex-grow: 1;
        overflow: hidden;
    }

    .table-scroll { flex-grow: 1; overflow-y: auto; overflow-x: auto; padding-bottom: 15px; }

    table { width: 100%; border-collapse: collapse; min-width: 1000px; }
    
    th {
        position: sticky; top: 0; z-index: 10;
        background: rgba(248, 250, 252, 0.95);
        padding: 18px 20px; text-align: left; font-weight: 700; color: #64748b;
        border-bottom: 2px solid #e2e8f0; white-space: nowrap;
    }
    td { padding: 15px 20px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; font-size: 0.95rem; color: #334155; }
    tr:hover { background-color: rgba(79, 70, 229, 0.05); } /* Hover สีม่วงจางๆ */

    /* Custom Badges */
    .status-badge { padding: 5px 12px; border-radius: 50px; font-weight: 700; font-size: 0.85rem; display: inline-block; min-width: 100px; text-align: center; transition: 0.3s; }
    .status-badge:hover { transform: scale(1.1); }
    
    /* ปุ่มดูรายละเอียด (วงกลม) */
    .btn-view {
        width: 35px; height: 35px; border-radius: 50%;
        background: #eef2ff; color: var(--primary);
        display: flex; align-items: center; justify-content: center;
        transition: 0.3s; border: 1px solid #dbeafe;
    }
    .btn-view:hover { background: var(--primary); color: white; transform: rotate(30deg) scale(1.1); }

    @media (max-width: 1024px) {
        body { overflow: auto; }
        .tracking-wrapper { height: auto; display: block; padding-top: 20px; }
        .table-card { height: 600px; }
    }
</style>

<div class="tracking-wrapper">
    
    <div class="tracking-header">
        <div>
            <h1><i class="fa-solid fa-list-check" style="color:var(--info);"></i> งานซ่อมของฉัน</h1>
            <p style="margin:5px 0 0 0; color:var(--text-muted);">ประวัติและสถานะการแจ้งซ่อมทั้งหมดของคุณ</p>
        </div>
        <a href="new_request.php" class="btn-add-new animate__animated animate__pulse" data-wow-iteration="infinite">
            <i class="fa-solid fa-plus"></i> แจ้งซ่อมเพิ่ม
        </a>
    </div>

    <div class="table-card">
        <div class="table-scroll">
            <?php if (count($requests) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th width="10%">เลขที่</th>
                        <th width="12%">สถานะ</th>
                        <th width="15%">ชนิด</th>
                        <th width="15%">เครื่อง/ทะเบียน</th>
                        <th width="20%">อาการ</th>
                        <th width="13%">ผู้รับผิดชอบ</th>
                        <th width="10%">วันที่แจ้ง</th>
                        <th width="5%" style="text-align:center;">ดู</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requests as $r): ?>
                        <?php 
                            $show_asset = !empty($r['asset_number']) ? $r['asset_number'] : ($r['manual_asset'] ?: '-');
                            $show_type = !empty($r['asset_type']) ? $r['asset_type'] : ($r['problem_types'] ?: '-');
                            $status_lower = strtolower(str_replace(' ', '_', $r['status']));
                            $status_text = match($r['status']) {
                                'Pending' => '⏳ รอรับเรื่อง', 'In Progress' => '🔧 กำลังซ่อม', 'Completed' => '✅ เสร็จสิ้น', default => $r['status']
                            };
                            $s_class = 'status-' . $status_lower;
                        ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($r['request_no']); ?></strong></td>
                        <td><span class="status-badge <?php echo $s_class; ?>"><?php echo $status_text; ?></span></td>
                        <td><?php echo htmlspecialchars($show_type); ?></td>
                        <td><?php echo htmlspecialchars($show_asset); ?></td>
                        <td title="<?php echo htmlspecialchars($r['issue_details']); ?>"><?php echo htmlspecialchars(mb_substr($r['issue_details'], 0, 40)); ?>...</td>
                        <td><?php echo htmlspecialchars($r['technician_name'] ?: 'รอดำเนินการ'); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($r['request_date'])); ?></td>
                        <td style="text-align:center;">
                            <a href="repair_details.php?id=<?php echo $r['request_id']; ?>" class="btn-view" title="ดูรายละเอียด">
                                <i class="fa-solid fa-arrow-right"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
                <div style="text-align:center; padding:60px; color:var(--text-muted); display:flex; flex-direction:column; align-items:center; justify-content:center; height:100%;">
                    <i class="fa-regular fa-paper-plane" style="font-size:4rem; margin-bottom:15px; color:#c7d2fe;"></i>
                    <h2 style="margin:0; color:var(--text-main);">ยังไม่มีรายการแจ้งซ่อม</h2>
                    <p>กดปุ่ม "แจ้งซ่อมเพิ่ม" ด้านบน เพื่อเริ่มแจ้งปัญหาแรกของคุณ</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php 
// include 'includes/footer.php'; 
?>