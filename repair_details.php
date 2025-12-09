<?php
session_start();
include 'db_connect.php'; 

if (!isset($_SESSION['logged_in'])) { header("Location: login.php"); exit(); }

$current_staff_id = $_SESSION['staff_id'];
$user_role = $_SESSION['user_role'];
$is_tech_or_admin = ($user_role == 'technician' || $user_role == 'admin');
$request_id = $_GET['id'] ?? null;

if (!$request_id) { header("Location: " . ($is_tech_or_admin ? 'dashboard_tech.php' : 'tracking.php')); exit(); }

$page_title = 'รายละเอียดงานซ่อม';

// SQL Query ดึงข้อมูลครบ (รวมตำแหน่ง/กลุ่มงาน)
$sql = "SELECT rr.*, a.asset_number, a.asset_type, a.location_group,
        s_req.full_name AS requester_name, s_req.position AS requester_position, s_req.group_name AS requester_group,
        s_tech.full_name AS technician_name 
        FROM repair_requests rr
        LEFT JOIN assets a ON rr.asset_id = a.asset_id
        LEFT JOIN staffs s_req ON rr.requester_id = s_req.staff_id
        LEFT JOIN staffs s_tech ON rr.technician_id = s_tech.staff_id
        WHERE rr.request_id = :request_id";
$stmt = $pdo->prepare($sql);
$stmt->execute(['request_id' => $request_id]);
$request = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$request) { die("ไม่พบข้อมูล"); }
if ($user_role == 'requester' && $request['requester_id'] != $current_staff_id) { die("ไม่มีสิทธิ์เข้าถึง"); }

$asset_show = !empty($request['asset_number']) ? $request['asset_number'] : ($request['manual_asset'] ?: '-');
$type_show = !empty($request['asset_type']) ? $request['asset_type'] : '-';

include 'includes/header.php'; 
?>

<style>
    /* ---- Single Screen CSS ---- */
    body { overflow: hidden; }
    
    .single-view-wrapper {
        height: calc(100vh - 90px);
        max-width: 1400px;
        margin: 0 auto;
        padding: 10px 20px;
        display: flex; flex-direction: column; gap: 15px;
    }
    .view-header { display: flex; justify-content: space-between; align-items: center; flex-shrink: 0; padding-bottom: 10px; border-bottom: 1px solid var(--border); }
    .view-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; flex-grow: 1; min-height: 0; overflow: hidden; }
    .col-left-scroll { overflow-y: auto; padding-right: 5px; display: flex; flex-direction: column; gap: 15px; }
    .col-right-fixed { display: flex; flex-direction: column; height: 100%; overflow: hidden; gap: 15px; }
    
    /* Info Card Styles */
    .info-card { background: var(--card-bg); border: 1px solid var(--border); border-radius: 12px; padding: 20px; box-shadow: var(--shadow); }
    .info-head { font-weight: 700; color: var(--primary); margin-bottom: 10px; border-bottom: 1px dashed var(--border); padding-bottom: 5px; font-size: 1rem; }
    .info-row { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 0.95rem; color: var(--text-main); }
    .info-label { color: var(--text-muted); }
    .issue-box { background: var(--input-bg); padding: 15px; border-radius: 8px; border: 1px solid var(--border); color: var(--text-main); }

    /* Image & Action Styles */
    .image-scroll-area { flex: 1; overflow-y: auto; min-height: 0; background: var(--input-bg); border-radius: 12px; border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; padding: 10px; }
    .image-scroll-area img { max-width: 100%; max-height: 100%; object-fit: contain; cursor: pointer; }
    .action-panel { flex-shrink: 0; background: var(--card-bg); border: 1px solid #bfdbfe; border-top: 4px solid var(--primary); border-radius: 12px; padding: 20px; box-shadow: 0 -5px 15px rgba(0,0,0,0.05); z-index: 10; }

    /* Print & Mobile */
    .paper-header, .signature-section { display: none; }
    @media print { body { overflow: auto; height: auto; } .site-header, .no-print, .btn-primary, footer, .view-header { display: none !important; } .single-view-wrapper { height: auto; display: block; } .view-grid { display: block; } .paper-header, .signature-section { display: block; } .action-panel { display: none; } }
    @media (max-width: 900px) { body { overflow: auto; } .single-view-wrapper { height: auto; } .view-grid { grid-template-columns: 1fr; } .col-right-fixed { height: auto; } .image-scroll-area { min-height: 250px; } }
</style>

<div class="single-view-wrapper" id="printable-area">
    
    <div class="view-header no-print">
        <div>
            <h1 style="margin:0; font-size:1.5rem; color:var(--text-main);">🛠️ รายละเอียดงานซ่อม</h1>
            <span style="color:var(--text-muted); font-size:0.9rem;">เลขที่: <b><?php echo htmlspecialchars($request['request_no']); ?></b></span>
        </div>
        <div style="display:flex; gap:10px;">
            <a href="<?php echo $is_tech_or_admin ? 'dashboard_tech.php' : 'tracking.php'; ?>" class="btn-detail">⬅ กลับ</a>
            <button onclick="window.print()" class="btn-detail" style="background:#64748b; color:white; border:none;">🖨️ พิมพ์ PDF</button>
        </div>
    </div>

    <div class="paper-header">
        <h2 style="margin:0; font-size:16pt;">ใบแจ้งซ่อมครุภัณฑ์คอมพิวเตอร์</h2>
        <p style="margin:5px 0;">สำนักงานเขตพื้นที่การศึกษาประถมศึกษาชลบุรี เขต 2</p>
        <div style="font-size:10pt; text-align:right;">เลขที่: <?php echo htmlspecialchars($request['request_no']); ?></div>
    </div>

    <div class="view-grid">
        <div class="col-left-scroll">
            
            <div class="info-card">
                <div class="info-head">👤 ข้อมูลผู้แจ้ง</div>
                <div class="info-row"><span class="info-label">ชื่อ-สกุล:</span><span><?php echo htmlspecialchars($request['requester_name']); ?></span></div>
                <div class="info-row"><span class="info-label">ตำแหน่ง:</span><span><?php echo htmlspecialchars($request['requester_position'] ?: '-'); ?></span></div>
                <div class="info-row"><span class="info-label">กลุ่ม/ฝ่าย:</span><span><?php echo htmlspecialchars($request['requester_group'] ?: '-'); ?></span></div>
                <hr style="margin:10px 0; border:0; border-top:1px dashed var(--border);">
                <div class="info-row"><span class="info-label">วันที่แจ้ง:</span><span><?php echo date('d/m/Y H:i', strtotime($request['request_date'])); ?></span></div>
            </div>

            <div class="info-card">
                <div class="info-head">💻 ข้อมูลครุภัณฑ์</div>
                <div class="info-row"><span class="info-label">ทะเบียน:</span><strong><?php echo htmlspecialchars($asset_show); ?></strong></div>
                <div class="info-row"><span class="info-label">ชนิด:</span><span><?php echo htmlspecialchars($type_show); ?></span></div>
                <div class="info-row">
                    <span class="info-label">สถานะ:</span>
                    <span class="status-badge status-<?php echo strtolower(str_replace(' ','_',$request['status'])); ?>"><?php echo $request['status']; ?></span>
                </div>
            </div>

            <div class="info-card">
                <div class="info-head">⚠️ อาการที่พบ</div>
                <div class="issue-box"><?php echo nl2br(htmlspecialchars($request['issue_details'])); ?></div>
            </div>

            <?php if ($request['action_taken']): ?>
            <div class="info-card" style="border-left: 4px solid var(--success);">
                <div class="info-head" style="color:var(--success);">✅ ผลการดำเนินการ</div>
                <p style="color:var(--text-main);"><?php echo nl2br(htmlspecialchars($request['action_taken'])); ?></p>
                <div style="margin-top:10px; font-size:0.85rem; color:var(--text-muted);">
                    โดย: <?php echo htmlspecialchars($request['technician_name']); ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="col-right-fixed">
            <div class="image-scroll-area">
                <?php if (!empty($request['image_path']) && file_exists($request['image_path'])): ?>
                    <img src="<?php echo htmlspecialchars($request['image_path']); ?>" onclick="window.open(this.src)" title="คลิกเพื่อดูภาพใหญ่">
                <?php else: ?>
                    <div style="color:var(--text-muted); text-align:center;">
                        <div style="font-size:3rem;">🖼️</div><p>ไม่มีรูปภาพประกอบ</p>
                    </div>
                <?php endif; ?>
            </div>

            <div class="no-print">
            <?php if ($is_tech_or_admin): ?>
                <?php if (trim(strtolower($request['status'])) == 'in progress'): ?>
                    <div class="action-panel">
                        <h3 style="color:var(--primary); font-size:1rem; margin-bottom:10px; margin-top:0;">🛠️ บันทึกการซ่อม</h3>
                        <form id="repairForm" method="POST" action="submit_repair_action.php">
                            <input type="hidden" name="request_id" value="<?php echo htmlspecialchars($request['request_id']); ?>">
                            <div class="form-group" style="margin-bottom:10px;">
                                <textarea name="action_taken" rows="3" required placeholder="ระบุสิ่งที่ทำไป..." style="width:100%; padding:10px; border-radius:8px; border:2px solid var(--border); background:var(--input-bg); color:var(--text-main);"></textarea>
                            </div>
                            <button type="button" onclick="confirmSave(event)" class="btn-primary" style="width:100%; padding:10px;">✅ บันทึกและปิดงาน</button>
                            <input type="hidden" name="action" value="complete">
                        </form>
                    </div>
                <?php elseif (trim(strtolower($request['status'])) == 'pending'): ?>
                    <div style="text-align:center; padding:15px; background:var(--card-bg); border-radius:12px; border:1px solid var(--border); flex-shrink:0;">
                         <div class="alert alert-danger" style="margin-bottom:10px;">⚠️ กรุณากดรับงานก่อน</div>
                         <a href="#" onclick="confirmAccept(event, '<?php echo $request['request_id']; ?>', '<?php echo $request['request_no']; ?>');" class="btn-action" style="width:100%; justify-content:center; padding:10px;">🚀 กดรับงานเดี๋ยวนี้</a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="signature-section">
        </div>
</div>

<script>
function confirmSave(e) {
    e.preventDefault();
    Swal.fire({ title: 'ยืนยันการปิดงาน?', text: "ตรวจสอบข้อมูลเรียบร้อยแล้วใช่หรือไม่", icon: 'question', showCancelButton: true, confirmButtonText: 'ใช่, บันทึกเลย', cancelButtonText: 'ยกเลิก' }).then((result) => { if (result.isConfirmed) { document.getElementById('repairForm').submit(); } });
}
function confirmAccept(e, id, no) {
    e.preventDefault();
    Swal.fire({ title: 'ยืนยันรับงาน?', text: "คุณต้องการรับผิดชอบงานซ่อมนี้ใช่หรือไม่", icon: 'question', showCancelButton: true, confirmButtonText: 'ใช่, รับงาน', cancelButtonText: 'ยกเลิก' }).then((result) => { if (result.isConfirmed) { window.location.href = 'update_status.php?action=accept&id=' + id; } });
}
</script>

<?php include 'includes/footer.php'; ?>