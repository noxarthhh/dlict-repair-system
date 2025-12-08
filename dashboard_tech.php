<?php
session_start();
include 'db_connect.php'; 

// 1. ตรวจสอบสิทธิ์: อนุญาตเฉพาะ Technician และ Admin
if (!isset($_SESSION['logged_in']) || ($_SESSION['user_role'] != 'technician' && $_SESSION['user_role'] != 'admin')) {
    header("Location: new_request.php");
    exit();
}

$page_title = 'Dashboard - จัดการงานซ่อม';

// 2. ดึงข้อมูลงานซ่อมทั้งหมด
$sql = "
    SELECT 
        rr.request_id, 
        rr.request_no, 
        rr.issue_details,
        rr.status, 
        rr.request_date,
        rr.manual_asset,
        a.asset_number,
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
    /* ปรับแต่งปุ่มและตาราง */
    td[data-label="ดำเนินการ"] {
        display: flex;
        align-items: center;
        gap: 8px;
        white-space: nowrap;
        min-width: 200px;
        justify-content: flex-start;
    }
    .btn-action, .btn-detail { flex-shrink: 0; margin: 0 !important; }
    
    @media (max-width: 768px) {
        td[data-label="ดำเนินการ"] {
            justify-content: flex-end;
            width: 100%;
            padding-top: 15px;
            margin-top: 10px;
            border-top: 1px dashed #eee;
        }
    }
    
    /* CSS เสริมสำหรับ SweetAlert ให้ Font เหมือนเว็บ */
    .swal-custom-font { font-family: 'Sarabun', sans-serif !important; }
</style>

<div class="container">
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:20px;">
        <h1>📋 Dashboard จัดการงานซ่อม</h1>
    </div>
    
    <?php if (isset($_GET['update']) && $_GET['update'] == 'success'): ?>
        <script>
            // แสดง Popup สำเร็จ เมื่อ Redirect กลับมา
            Swal.fire({
                icon: 'success',
                title: 'บันทึกสำเร็จ!',
                text: 'อัปเดตสถานะงานซ่อม #<?php echo htmlspecialchars($_GET['id']); ?> เรียบร้อยแล้ว',
                confirmButtonColor: '#10b981',
                confirmButtonText: 'ตกลง',
                customClass: { popup: 'swal-custom-font' }
            });
        </script>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger">
            ❌ เกิดข้อผิดพลาด: <?php echo htmlspecialchars($_GET['error']); ?>
        </div>
    <?php endif; ?>
    
    <div class="card">
        <?php if (count($requests) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th width="10%">เลขที่</th>
                    <th width="10%">สถานะ</th>
                    <th width="15%">ทะเบียนเครื่อง</th>
                    <th width="15%">ผู้แจ้ง</th>
                    <th width="20%">อาการ</th>
                    <th width="12%">วันที่แจ้ง</th>
                    <th width="13%">ช่างผู้รับผิดชอบ</th>
                    <th width="5%" style="text-align:right;">จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($requests as $request): ?>
                    <?php 
                        $show_asset = !empty($request['asset_number']) ? $request['asset_number'] : ($request['manual_asset'] ?: '-');
                        $status_class = 'status-' . strtolower(str_replace(' ', '_', $request['status']));
                    ?>
                <tr>
                    <td data-label="เลขที่"><strong><?php echo htmlspecialchars($request['request_no']); ?></strong></td>
                    
                    <td data-label="สถานะ">
                        <span class="status-badge <?php echo $status_class; ?>">
                            <?php echo htmlspecialchars($request['status']); ?>
                        </span>
                    </td>
                    
                    <td data-label="ทะเบียนเครื่อง"><?php echo htmlspecialchars($show_asset); ?></td>
                    <td data-label="ผู้แจ้ง"><?php echo htmlspecialchars($request['requester_name']); ?></td>
                    <td data-label="อาการ" title="<?php echo htmlspecialchars($request['issue_details']); ?>">
                        <?php echo htmlspecialchars(mb_substr($request['issue_details'], 0, 40, 'UTF-8')) . '...'; ?>
                    </td>
                    <td data-label="วันที่แจ้ง"><?php echo date('d/m/Y H:i', strtotime($request['request_date'])); ?></td>
                    <td data-label="ช่างผู้รับผิดชอบ"><?php echo htmlspecialchars($request['technician_name'] ?: 'รอดำเนินการ'); ?></td>
                    
                    <td data-label="ดำเนินการ" style="text-align:right;">
                        <div style="display:inline-flex; gap:5px;">
                            <?php if ($request['status'] == 'Pending'): ?>
                                <a href="#" 
                                   class="btn-action" 
                                   onclick="confirmAccept(event, '<?php echo $request['request_id']; ?>', '<?php echo $request['request_no']; ?>');">
                                   รับเรื่อง
                                </a>
                            <?php endif; ?>
                            
                            <a href="repair_details.php?id=<?php echo $request['request_id']; ?>" class="btn-detail">รายละเอียด</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
            <div style="text-align:center; padding: 40px; color: #666;">
                <p>ยังไม่มีรายการงานซ่อมในขณะนี้</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function confirmAccept(e, id, no) {
    e.preventDefault(); // หยุดการลิ้งค์ปกติ
    
    Swal.fire({
        title: 'ยืนยันการรับงาน?',
        text: "คุณต้องการรับผิดชอบงานซ่อมหมายเลข " + no + " ใช่หรือไม่?",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3b82f6', // สีฟ้า
        cancelButtonColor: '#6b7280', // สีเทา
        confirmButtonText: 'ใช่, รับงานเลย',
        cancelButtonText: 'ยกเลิก',
        customClass: {
            popup: 'swal-custom-font',
            title: 'swal-custom-title'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // ถ้ากดตกลง ให้ส่งค่าไปที่ไฟล์ update_status.php
            window.location.href = 'update_status.php?action=accept&id=' + id;
        }
    });
}
</script>

<?php include 'includes/footer.php'; ?>