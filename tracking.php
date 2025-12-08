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

<div class="page-header">
    <h1>📂 งานซ่อมของฉัน</h1>
    <a href="new_request.php" class="btn-primary">➕ แจ้งซ่อมเพิ่ม</a>
</div>

<div class="card" style="padding: 0;">
    <div class="table-wrapper" style="border:none;">
        <?php if (empty($requests)): ?>
            <div style="text-align:center; padding:40px;">
                <p style="font-size:1.2rem; color:#999;">คุณยังไม่มีรายการแจ้งซ่อม</p>
                <a href="new_request.php" class="btn-primary" style="margin-top:10px;">ไปแจ้งซ่อมกันเลย!</a>
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>เลขที่</th>
                        <th>สถานะ</th>
                        <th>เครื่อง</th>
                        <th>อาการ</th>
                        <th>ช่างผู้รับผิดชอบ</th>
                        <th>รายละเอียด</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requests as $r): ?>
                    <tr>
                        <td><strong><?php echo $r['request_no']; ?></strong></td>
                        <td>
                            <span class="status-badge status-<?php echo strtolower(str_replace(' ','_',$r['status'])); ?>">
                                <?php 
                                    $status_map = ['Pending'=>'รอรับเรื่อง ⏳', 'In Progress'=>'กำลังซ่อม 🔧', 'Completed'=>'เสร็จสิ้น ✅'];
                                    echo $status_map[$r['status']] ?? $r['status'];
                                ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($r['asset_number'] ?: ($r['manual_asset']?:'-')); ?></td>
                        <td><?php echo htmlspecialchars(mb_substr($r['issue_details'], 0, 40)); ?>...</td>
                        <td><?php echo htmlspecialchars($r['technician_name']?:'รอดำเนินการ'); ?></td>
                        <td><a href="repair_details.php?id=<?php echo $r['request_id']; ?>" class="btn-detail">ดูรายละเอียด</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
<?php include 'includes/footer.php'; ?>