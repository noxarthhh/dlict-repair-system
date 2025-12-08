<?php
session_start();
include 'db_connect.php'; 

// 1. ตรวจสอบสิทธิ์
if (!isset($_SESSION['logged_in']) || ($_SESSION['user_role'] != 'technician' && $_SESSION['user_role'] != 'admin')) {
    header("Location: new_request.php");
    exit();
}

$page_title = 'Dashboard - จัดการงานซ่อม';

// 2. ดึงข้อมูล
$sql = "
    SELECT 
        rr.request_id, rr.request_no, rr.issue_details, rr.status, rr.request_date, rr.manual_asset,
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
    /* CSS เฉพาะหน้า Dashboard (เหมือนเดิม) */
    body { overflow: hidden; } 
    .dashboard-wrapper {
        height: calc(100vh - 80px);
        display: flex;
        flex-direction: column;
        padding: 15px 25px;
        max-width: 100%;
        margin: 0 auto;
    }
    .dashboard-header {
        flex-shrink: 0;
        margin-bottom: 15px;
        display: flex; justify-content: space-between; align-items: center;
    }
    .table-card {
        background: #fff;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
        display: flex;
        flex-direction: column;
        flex-grow: 1;
        overflow: hidden;
    }
    .table-scroll {
        flex-grow: 1;
        overflow-y: auto;
        overflow-x: auto;
    }
    table { width: 100%; border-collapse: collapse; }
    th {
        position: sticky; top: 0; z-index: 10;
        background: #f8fafc; padding: 15px; text-align: left; font-weight: 600; color: #64748b;
        border-bottom: 2px solid #e2e8f0; box-shadow: 0 2px 4px rgba(0,0,0,0.02);
    }
    td { padding: 15px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
    tr:hover { background-color: #f8fafc; }
    td[data-label="ดำเนินการ"] { display: flex; gap: 8px; white-space: nowrap; }
    @media (max-width: 1024px) {
        body { overflow: auto; }
        .dashboard-wrapper { height: auto; display: block; }
        .table-card { height: 500px; }
    }
    /* Font สำหรับ Popup */
    .swal-custom-font { font-family: 'Sarabun', sans-serif !important; }
</style>

<div class="dashboard-wrapper">
    
    <div class="dashboard-header">
        <div>
            <h1 style="margin:0; font-size:1.8rem; color:#1e293b;">📋 Dashboard จัดการงานซ่อม</h1>
            <p style="margin:0; color:#64748b; font-size:0.95rem;">รายการงานซ่อมทั้งหมดในระบบ</p>
        </div>
        <div style="font-size:0.9rem; font-weight:600; color:#3b82f6; background:#eff6ff; padding:5px 15px; border-radius:50px;">
            รวมทั้งหมด: <?php echo count($requests); ?> รายการ
        </div>
    </div>
    
    <?php if (isset($_GET['update']) && $_GET['update'] == 'success'): ?>
        <script>
            Swal.fire({
                icon: 'success',
                title: 'บันทึกสำเร็จ!',
                text: 'คุณได้รับงานซ่อม #<?php echo htmlspecialchars($_GET['id']); ?> แล้ว',
                timer: 2000,
                showConfirmButton: false,
                customClass: { popup: 'swal-custom-font' }
            });
        </script>
    <?php endif; ?>

    <div class="table-card">
        <div class="table-scroll">
            <?php if (count($requests) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th width="10%">เลขที่</th>
                        <th width="10%">สถานะ</th>
                        <th width="15%">ทะเบียน</th>
                        <th width="15%">ผู้แจ้ง</th>
                        <th width="20%">อาการ</th>
                        <th width="12%">วันที่</th>
                        <th width="13%">ช่าง</th>
                        <th width="5%">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requests as $request): ?>
                        <?php 
                            $show_asset = !empty($request['asset_number']) ? $request['asset_number'] : ($request['manual_asset'] ?: '-');
                            $status_class = 'status-' . strtolower(str_replace(' ', '_', $request['status']));
                        ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($request['request_no']); ?></strong></td>
                        
                        <td>
                            <span class="status-badge <?php echo $status_class; ?>">
                                <?php echo htmlspecialchars($request['status']); ?>
                            </span>
                        </td>
                        
                        <td><?php echo htmlspecialchars($show_asset); ?></td>
                        <td><?php echo htmlspecialchars($request['requester_name']); ?></td>
                        <td title="<?php echo htmlspecialchars($request['issue_details']); ?>">
                            <?php echo htmlspecialchars(mb_substr($request['issue_details'], 0, 40, 'UTF-8')) . '...'; ?>
                        </td>
                        <td><?php echo date('d/m/Y H:i', strtotime($request['request_date'])); ?></td>
                        <td><?php echo htmlspecialchars($request['technician_name'] ?: 'รอดำเนินการ'); ?></td>
                        
                        <td data-label="ดำเนินการ">
                            <?php if ($request['status'] == 'Pending'): ?>
                                <a href="#" 
                                   class="btn-action"
                                   onclick="confirmAccept(event, '<?php echo $request['request_id']; ?>', '<?php echo $request['request_no']; ?>');">
                                   รับเรื่อง
                                </a>
                            <?php endif; ?>
                            <a href="repair_details.php?id=<?php echo $request['request_id']; ?>" class="btn-detail">รายละเอียด</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
                <div style="text-align:center; padding:50px; color:#94a3b8;">
                    <p style="font-size:4rem; margin:0;">📭</p>
                    <p>ยังไม่มีรายการงานซ่อมในขณะนี้</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function confirmAccept(e, id, no) {
    e.preventDefault(); // หยุดการลิ้งค์ไว้ก่อน
    
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

<?php 
// ไม่ต้องใส่ Footer ในหน้านี้เพื่อประหยัดพื้นที่แนวตั้ง
// include 'includes/footer.php'; 
?>