<?php
session_start();
include 'db_connect.php'; 
if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] !== 'requester') { header("Location: dashboard_tech.php"); exit(); }
$page_title = 'ติดตามงานซ่อม';
$staff_id = $_SESSION['staff_id'];

$sql = "SELECT rr.*, a.asset_number, t.full_name AS technician_name 
        FROM repair_requests rr 
        LEFT JOIN assets a ON rr.asset_id = a.asset_id 
        LEFT JOIN staffs t ON rr.technician_id = t.staff_id 
        WHERE rr.requester_id = ? ORDER BY rr.request_date DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$staff_id]);
$requests = $stmt->fetchAll();

include 'includes/header.php'; 
?>

<div class="container">
    <h1>📂 งานซ่อมของฉัน</h1>
    <div class="card">
        <?php if (empty($requests)): ?>
            <p style="text-align:center; padding: 20px;">คุณยังไม่มีรายการแจ้งซ่อม <a href="new_request.php">แจ้งซ่อมใหม่คลิกที่นี่</a></p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>เลขที่</th>
                        <th>เครื่อง</th>
                        <th>อาการ</th>
                        <th>สถานะ</th>
                        <th>ช่างผู้รับผิดชอบ</th>
                        <th>รายละเอียด</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requests as $r): ?>
                    <tr>
                        <td data-label="เลขที่"><b><?php echo $r['request_no']; ?></b></td>
                        <td data-label="เครื่อง"><?php echo htmlspecialchars($r['asset_number'] ?: ($r['manual_asset']?:'-')); ?></td>
                        <td data-label="อาการ"><?php echo htmlspecialchars(mb_substr($r['issue_details'], 0, 30)); ?>...</td>
                        <td data-label="สถานะ"><span class="status-badge status-<?php echo strtolower(str_replace(' ','_',$r['status'])); ?>"><?php echo $r['status']; ?></span></td>
                        <td data-label="ช่าง"><?php echo htmlspecialchars($r['technician_name']?:'รอดำเนินการ'); ?></td>
                        <td data-label="รายละเอียด"><a href="repair_details.php?id=<?php echo $r['request_id']; ?>" class="btn-detail">ดูรายละเอียด</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
<?php include 'includes/footer.php'; ?>