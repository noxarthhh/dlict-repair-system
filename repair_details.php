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

// SQL Query
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
    /* ---- CSS สำหรับหน้าจอปกติ (บังคับไม่ให้ล้น) ---- */
    html, body {
        height: 100%;       /* ความสูงเต็มจอ */
        margin: 0;
        overflow: hidden;   /* ห้าม Scroll ที่ Body เด็ดขาด */
        display: flex;      /* จัด Layout แบบ Flex แนวตั้ง */
        flex-direction: column;
    }

    /* 1. ให้ Header กินพื้นที่ตามจริง */
    .site-header { flex-shrink: 0; }

    /* 2. ให้ Container หลักกินพื้นที่ที่เหลือทั้งหมด */
    .single-view-wrapper {
        flex-grow: 1;       /* ยืดเต็มพื้นที่ที่เหลือ */
        min-height: 0;      /* สำคัญ! ให้หดได้ถ้าจำเป็น */
        width: 100%;
        max-width: 1400px;
        margin: 0 auto;
        padding: 10px 20px 20px 20px; /* เว้นขอบล่างหน่อย */
        display: flex;
        flex-direction: column;
        gap: 10px;
        box-sizing: border-box; /* รวม padding ในความกว้าง/สูง */
    }

    /* ส่วนหัวชื่อเรื่อง */
    .view-header {
        display: flex; justify-content: space-between; align-items: center;
        flex-shrink: 0; padding-bottom: 10px; border-bottom: 1px solid #e2e8f0;
    }
    
    /* Grid แบ่งซ้ายขวา (กินพื้นที่ที่เหลือทั้งหมด) */
    .view-grid {
        display: grid;
        grid-template-columns: 1fr 1fr; /* แบ่งครึ่ง 50:50 */
        gap: 20px;
        flex-grow: 1;    /* ยืดเต็มความสูงที่เหลือ */
        min-height: 0;   /* สำคัญ! ป้องกัน Grid ล้น */
        overflow: hidden; /* ห้าม Scroll ที่ Grid */
    }

    /* คอลัมน์ซ้าย (ข้อมูล) - เลื่อนได้อิสระ */
    .col-left-scroll {
        overflow-y: auto; /* ให้ Scroll เฉพาะในนี้ */
        padding-right: 5px; 
        display: flex; flex-direction: column; gap: 15px;
    }

    /* คอลัมน์ขวา (รูป + ฟอร์ม) */
    .col-right-fixed {
        display: flex;
        flex-direction: column;
        height: 100%;     /* เต็มความสูงของ Grid */
        overflow: hidden; /* ห้ามเลื่อนทั้งแท่ง */
        gap: 15px;
    }

    /* ส่วนแสดงรูป (ยืดหดได้ตามพื้นที่ที่เหลือ) */
    .image-scroll-area {
        flex-grow: 1; /* กินพื้นที่ที่เหลือจากฟอร์ม */
        min-height: 0; /* ยอมให้หดจนสุด */
        overflow-y: auto; /* เลื่อนได้ถ้าภาพยาว */
        background: #f8fafc;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        display: flex; flex-direction: column; align-items: center; justify-content: center;
        padding: 10px;
    }
    .image-scroll-area img {
        max-width: 100%;
        object-fit: contain; /* รูปไม่เพี้ยน */
        cursor: pointer;
    }

    /* ส่วนฟอร์ม (ขนาดคงที่อยู่ด้านล่าง) */
    .action-panel {
        flex-shrink: 0; /* ห้ามหดตัว */
        background: #fff;
        border: 1px solid #bfdbfe;
        border-top: 4px solid var(--primary);
        border-radius: 12px;
        padding: 15px;
        box-shadow: 0 -5px 15px rgba(0,0,0,0.05);
        z-index: 10;
        max-height: 50%; /* กันไม่ให้กินที่เกินครึ่งจอ */
        overflow-y: auto;
    }

    /* การ์ดข้อมูลทั่วไป */
    .info-card {
        background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 15px;
        box-shadow: var(--shadow);
    }
    .info-head { font-weight: 700; color: var(--primary); margin-bottom: 8px; border-bottom: 1px dashed #e2e8f0; padding-bottom: 5px; }
    .info-row { display: flex; justify-content: space-between; margin-bottom: 6px; font-size: 0.95rem; }
    .issue-box { background: #f8fafc; padding: 10px; border-radius: 8px; border: 1px solid #e2e8f0; }

    /* ---- CSS Print ---- */
    .paper-header, .signature-section { display: none; }
    @media print {
        html, body { height: auto; overflow: visible; display: block; }
        .site-header, .no-print, .btn-primary, footer, .view-header { display: none !important; }
        .single-view-wrapper { height: auto; display: block; overflow: visible; }
        .view-grid { display: block; overflow: visible; }
        .col-left-scroll, .col-right-fixed, .image-scroll-area { overflow: visible; display: block; height: auto; border: none; }
        .image-scroll-area { display: none; } /* ซ่อนรูปตอนพิมพ์ประหยัดหมึก หรือจะโชว์ก็ได้ */
        .action-panel { display: none; }
        .paper-header, .signature-section { display: block; }
        .info-card { border: none; box-shadow: none; padding: 0; margin-bottom: 10px; page-break-inside: avoid; }
        .print-row { display: flex; gap: 20px; }
        .print-col { flex: 1; border: 1px solid #ccc; padding: 10px; }
    }
    
    @media (max-width: 900px) {
        body { overflow: auto; }
        .single-view-wrapper { height: auto; }
        .view-grid { grid-template-columns: 1fr; }
        .col-right-fixed { height: auto; }
        .image-scroll-area { min-height: 250px; }
    }
</style>

<div class="single-view-wrapper" id="printable-area">
    
    <div class="view-header no-print">
        <div>
            <h1 style="margin:0; font-size:1.5rem;">🛠️ รายละเอียดงานซ่อม</h1>
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
                <div class="info-head">👤 ข้อมูลทั่วไป</div>
                <div class="info-row"><span class="info-label">ผู้แจ้ง:</span><span><?php echo htmlspecialchars($request['requester_name']); ?></span></div>
                <div class="info-row"><span class="info-label">วันที่แจ้ง:</span><span><?php echo date('d/m/Y H:i', strtotime($request['request_date'])); ?></span></div>
                <hr style="margin:8px 0; border:0; border-top:1px dashed #eee;">
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
                <p><?php echo nl2br(htmlspecialchars($request['action_taken'])); ?></p>
                <div style="margin-top:10px; font-size:0.85rem; color:#666;">
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
                    <div style="color:#aaa; text-align:center;">
                        <div style="font-size:3rem;">🖼️</div>
                        <p>ไม่มีรูปภาพประกอบ</p>
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
                                <textarea name="action_taken" rows="3" required placeholder="ระบุสิ่งที่ทำไป..." style="width:100%; padding:10px; border-radius:8px; border:2px solid #e2e8f0; font-size:0.95rem;"></textarea>
                            </div>
                            
                            <button type="button" onclick="confirmSave(event)" class="btn-primary" style="width:100%; padding:10px;">
                                ✅ บันทึกและปิดงาน
                            </button>
                            <input type="hidden" name="action" value="complete">
                        </form>
                    </div>

                <?php elseif (trim(strtolower($request['status'])) == 'pending'): ?>
                    <div style="text-align:center; padding:15px; background:#fff; border-radius:12px; border:1px solid #e2e8f0; flex-shrink:0;">
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
    Swal.fire({
        title: 'ยืนยันการปิดงาน?',
        text: "ตรวจสอบข้อมูลเรียบร้อยแล้วใช่หรือไม่",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#2563eb',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'ใช่, บันทึกเลย',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) { document.getElementById('repairForm').submit(); }
    });
}
function confirmAccept(e, id, no) {
    e.preventDefault();
    Swal.fire({
        title: 'ยืนยันรับงาน?',
        text: "คุณต้องการรับผิดชอบงานซ่อมนี้ใช่หรือไม่",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3b82f6',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'ใช่, รับงาน',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) { window.location.href = 'update_status.php?action=accept&id=' + id; }
    });
}
</script>

<?php include 'includes/footer.php'; ?>