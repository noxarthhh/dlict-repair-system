<?php
session_start();
include 'db_connect.php'; 

// 1. ตรวจสอบสิทธิ์ (เฉพาะ Requester)
if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] !== 'requester') {
    header("Location: dashboard_tech.php");
    exit();
}

$page_title = 'ติดตามงานซ่อม';
$staff_id = $_SESSION['staff_id'];

// 2. ดึงข้อมูลงานซ่อมของผู้ใช้
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

<style>
    /* บังคับหน้าเดียวจบ */
    body { overflow: hidden; background-color: var(--bg-body); }

    .tracking-wrapper {
        height: calc(100vh - 80px); /* ความสูงเต็มจอ - Header */
        display: flex; flex-direction: column;
        padding: 15px 25px; gap: 20px;
        max-width: 1400px; margin: 0 auto;
        animation: slideUp 0.6s ease-out;
    }
    @keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

    /* Header Section */
    .tracking-header {
        flex-shrink: 0;
        margin-bottom: 5px;
        display: flex; justify-content: space-between; align-items: center;
    }
    
    .tracking-header h1 { margin: 0; font-size: 1.8rem; color: var(--text-main); font-weight: 800; display: flex; align-items: center; gap: 10px; }
    
    /* ปุ่มแจ้งซ่อมเพิ่ม (เด่นๆ) */
    .btn-add-new {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        color: white; padding: 10px 25px; border-radius: 50px;
        text-decoration: none; font-weight: 700; font-size: 0.95rem;
        box-shadow: 0 5px 15px rgba(37, 99, 235, 0.3);
        transition: all 0.3s; display: inline-flex; align-items: center; gap: 8px;
    }
    .btn-add-new:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(37, 99, 235, 0.4); }

    /* Table Card (ยืดเต็ม) */
    .table-card {
        background: var(--card-bg);
        border-radius: 16px;
        border: 1px solid var(--border);
        box-shadow: var(--shadow);
        display: flex; flex-direction: column;
        flex-grow: 1;
        overflow: hidden; /* ห้ามล้น */
    }

    .table-scroll { flex-grow: 1; overflow-y: auto; overflow-x: auto; }

    table { width: 100%; border-collapse: collapse; min-width: 1000px; /* กันบีบจนเละ */ }
    
    th {
        position: sticky; top: 0; z-index: 10;
        background: var(--input-bg);
        padding: 18px 15px;
        text-align: left; font-weight: 700; color: var(--text-muted);
        border-bottom: 2px solid var(--border); white-space: nowrap;
    }
    td { padding: 15px; border-bottom: 1px solid var(--border); vertical-align: middle; font-size: 0.95rem; color: var(--text-main); }
    tr:hover { background-color: var(--input-bg); transition: 0.1s; }

    /* Custom Badges */
    .status-badge { padding: 5px 12px; border-radius: 50px; font-weight: 700; font-size: 0.85rem; display: inline-block; min-width: 100px; text-align: center; }
    
    /* ปุ่มดูรายละเอียด (วงกลม) */
    .btn-view {
        width: 35px; height: 35px; border-radius: 50%;
        background: #f1f5f9; color: var(--text-muted);
        display: flex; align-items: center; justify-content: center;
        transition: 0.2s; border: 1px solid var(--border);
    }
    .btn-view:hover { background: var(--primary); color: white; border-color: var(--primary); transform: scale(1.1); }

    /* Colors */
    .s-pending { background: #fff7ed; color: #c2410c; border: 1px solid #ffedd5; }
    .s-progress { background: #eff6ff; color: #1d4ed8; border: 1px solid #dbeafe; }
    .s-completed { background: #f0fdf4; color: #15803d; border: 1px solid #dcfce7; }

    @media (max-width: 1024px) {
        body { overflow: auto; }
        .tracking-wrapper { height: auto; display: block; }
        .table-card { height: 600px; }
    }
</style>

<div class="tracking-wrapper">
    
    <div class="tracking-header">
        <div>
            <h1><i class="fa-solid fa-folder-open" style="color:var(--info);"></i> งานซ่อมของฉัน</h1>
            <p style="margin:5px 0 0 0; color:var(--text-muted);">ประวัติและสถานะการแจ้งซ่อมทั้งหมดของคุณ</p>
        </div>
        <a href="new_request.php" class="btn-add-new">
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
                            
                            // กำหนดสีและข้อความสถานะ
                            $status_lower = strtolower(str_replace(' ', '_', $r['status']));
                            if ($status_lower == 'pending') {
                                $s_class = 's-pending'; $s_text = '⏳ รอรับเรื่อง';
                            } elseif ($status_lower == 'in_progress') {
                                $s_class = 's-progress'; $s_text = '🔧 กำลังซ่อม';
                            } else {
                                $s_class = 's-completed'; $s_text = '✅ เสร็จสิ้น';
                            }
                        ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($r['request_no']); ?></strong></td>
                        
                        <td><span class="status-badge <?php echo $s_class; ?>"><?php echo $s_text; ?></span></td>
                        
                        <td><?php echo htmlspecialchars($show_type); ?></td>
                        
                        <td><?php echo htmlspecialchars($show_asset); ?></td>
                        
                        <td title="<?php echo htmlspecialchars($r['issue_details']); ?>">
                            <?php echo htmlspecialchars(mb_substr($r['issue_details'], 0, 40)); ?>...
                        </td>
                        
                        <td><?php echo htmlspecialchars($r['technician_name'] ?: 'รอดำเนินการ'); ?></td>
                        
                        <td><?php echo date('d/m/Y', strtotime($r['request_date'])); ?></td>
                        
                        <td style="text-align:center;">
                            <a href="repair_details.php?id=<?php echo $r['request_id']; ?>" class="btn-view" title="ดูรายละเอียด">
                                <i class="fa-solid fa-chevron-right"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
                <div style="text-align:center; padding:60px; color:var(--text-muted); display:flex; flex-direction:column; align-items:center; justify-content:center; height:100%;">
                    <div style="font-size:5rem; margin-bottom:15px; opacity:0.3;">📭</div>
                    <h2 style="margin:0; color:var(--text-main);">ยังไม่มีรายการแจ้งซ่อม</h2>
                    <p>คุณสามารถแจ้งปัญหาการใช้งานได้โดยกดปุ่มด้านขวาบน</p>
                    <a href="new_request.php" class="btn-add-new" style="margin-top:20px;">
                        <i class="fa-solid fa-plus"></i> เริ่มแจ้งซ่อมเลย
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php 
// include 'includes/footer.php'; 
?>