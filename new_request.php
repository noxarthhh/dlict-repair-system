<?php
session_start();
include 'db_connect.php'; 

if (!isset($_SESSION['logged_in'])) { header("Location: login.php"); exit(); }

$page_title = 'แจ้งซ่อมใหม่';
$staff_id = $_SESSION['staff_id'];
$full_name = $_SESSION['full_name'];

// 1. ดึงข้อมูลผู้แจ้งเพิ่มเติม
$stmt = $pdo->prepare("SELECT group_name, position FROM staffs WHERE staff_id = ?");
$stmt->execute([$staff_id]);
$user_info = $stmt->fetch();

// 2. ✅ ดึงประเภทงานซ่อมจาก Database (ที่ Admin เพิ่มไว้)
// ใช้ try-catch เผื่อกรณียังไม่มีตาราง จะได้ไม่ error
$types = [];
try {
    $types = $pdo->query("SELECT * FROM repair_types ORDER BY type_name ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // ถ้ายังไม่มีตาราง repair_types ก็ปล่อย array เป็นค่าว่าง
}

include 'includes/header.php'; 
?>

<style>
    /* บังคับหน้าเดียวจบ */
    body { overflow: hidden; background-color: #f8fafc; }

    /* Wrapper หลัก: ยืดเต็มความสูงที่เหลือ */
    .request-wrapper {
        height: calc(100vh - 80px); /* ความสูงจอ - Header */
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 20px;
        animation: fadeInUp 0.6s ease-out;
    }
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

    /* การ์ดฟอร์ม (Flex Column) */
    .form-card-premium {
        width: 100%;
        max-width: 1200px;
        height: 100%; /* ยืดเต็มพื้นที่ Wrapper */
        background: #fff;
        border-radius: 20px;
        box-shadow: 0 15px 40px -10px rgba(0,0,0,0.1);
        border: 1px solid rgba(255,255,255,0.8);
        display: flex; /* จัด Layout แนวตั้ง */
        flex-direction: column;
        overflow: hidden; /* ห้ามล้น */
    }

    /* ส่วนหัวการ์ด (Fixed) */
    .form-header {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        padding: 20px 30px;
        color: white;
        display: flex; justify-content: space-between; align-items: center;
        flex-shrink: 0; /* ห้ามหด */
    }
    .form-header h1 { margin: 0; font-size: 1.5rem; font-weight: 800; display: flex; align-items: center; gap: 10px; }
    .form-header p { margin: 0; opacity: 0.9; font-size: 0.9rem; font-weight: 300; }

    /* ส่วนเนื้อหาฟอร์ม (Scrollable Area) */
    .form-body {
        padding: 30px;
        flex-grow: 1;    /* ยืดกินพื้นที่ที่เหลือ */
        overflow-y: auto; /* ให้ Scroll ได้เฉพาะส่วนนี้ */
        display: grid;
        grid-template-columns: 1fr 1.5fr; /* ขวากว้างกว่า */
        gap: 40px;
    }

    /* Input Styling */
    .form-group { margin-bottom: 20px; }
    .form-label { display: block; font-size: 0.9rem; font-weight: 600; color: #475569; margin-bottom: 5px; }
    
    .input-box { position: relative; display: flex; align-items: center; }
    .input-box i { position: absolute; left: 15px; color: #94a3b8; font-size: 1rem; transition: 0.3s; }
    
    .form-control {
        width: 100%;
        padding: 12px 12px 12px 40px; /* เว้นที่ให้ไอคอน */
        border: 2px solid #e2e8f0;
        border-radius: 10px;
        font-size: 0.95rem;
        transition: all 0.3s;
        background: #f8fafc;
        color: #1e293b;
    }
    .form-control:focus {
        border-color: #3b82f6; background: #fff; box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1); outline: none;
    }
    .form-control:focus + i { color: #3b82f6; }

    /* Readonly Inputs */
    .form-control-static { background: #f1f5f9; border-color: transparent; color: #64748b; font-weight: 600; cursor: default; }

    /* Upload Zone (Compact) */
    .upload-zone {
        border: 2px dashed #cbd5e1; border-radius: 12px; padding: 20px;
        text-align: center; transition: 0.3s; background: #f8fafc; cursor: pointer; position: relative;
    }
    .upload-zone:hover { border-color: #3b82f6; background: #eff6ff; }
    .upload-zone input[type="file"] { position: absolute; top: 0; left: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer; }
    .upload-icon { font-size: 2rem; color: #94a3b8; margin-bottom: 5px; transition: 0.3s; }
    .upload-zone:hover .upload-icon { color: #3b82f6; transform: translateY(-3px); }

    /* Footer (Fixed at bottom) */
    .form-footer {
        padding: 15px 30px;
        border-top: 1px solid #f1f5f9;
        text-align: right;
        background: #fff;
        flex-shrink: 0; /* ห้ามหด */
    }
    
    .btn-submit {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        color: white; border: none; padding: 12px 35px; border-radius: 50px;
        font-size: 1rem; font-weight: 700; cursor: pointer;
        box-shadow: 0 5px 15px rgba(37, 99, 235, 0.3); transition: 0.3s;
        display: inline-flex; align-items: center; gap: 8px;
    }
    .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(37, 99, 235, 0.4); }

    @media (max-width: 900px) {
        body { overflow: auto; }
        .request-wrapper { height: auto; display: block; padding-top: 20px; }
        .form-card-premium { height: auto; overflow: visible; }
        .form-body { grid-template-columns: 1fr; gap: 20px; padding: 25px; overflow: visible; }
    }
</style>

<div class="request-wrapper">
    <form method="POST" action="submit_request.php" enctype="multipart/form-data" class="form-card-premium">
        
        <div class="form-header">
            <div>
                <h1><i class="fa-solid fa-bell"></i> แจ้งซ่อมใหม่</h1>
                <p>กรอกรายละเอียดปัญหาเพื่อแจ้งเจ้าหน้าที่</p>
            </div>
            <div style="font-size:2.5rem; opacity:0.2;"><i class="fa-solid fa-file-pen"></i></div>
        </div>

        <div class="form-body">
            
            <div class="col-left">
                <div class="form-group">
                    <label class="form-label">ผู้แจ้ง</label>
                    <div class="input-box"><i class="fa-solid fa-user"></i><input type="text" class="form-control form-control-static" value="<?php echo htmlspecialchars($full_name); ?>" readonly></div>
                </div>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
                    <div class="form-group">
                        <label class="form-label">ตำแหน่ง</label>
                        <div class="input-box"><i class="fa-solid fa-id-badge"></i><input type="text" class="form-control form-control-static" value="<?php echo htmlspecialchars($user_info['position'] ?? '-'); ?>" readonly></div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">ฝ่าย/กลุ่ม</label>
                        <div class="input-box"><i class="fa-solid fa-building"></i><input type="text" class="form-control form-control-static" value="<?php echo htmlspecialchars($user_info['group_name'] ?? '-'); ?>" readonly></div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">ชนิดอุปกรณ์</label>
                    <div class="input-box">
                        <i class="fa-solid fa-laptop"></i>
                        <select name="problem_type" class="form-control" required style="cursor:pointer;">
                            <option value="">-- กรุณาเลือก --</option>
                            
                            <option value="เครื่องคอมพิวเตอร์">คอมพิวเตอร์ (PC)</option>
                            <option value="โน้ตบุ๊ก">โน้ตบุ๊ก (Notebook)</option>
                            <option value="เครื่องพิมพ์">เครื่องพิมพ์ (Printer)</option>
                            <option value="อินเทอร์เน็ต">อินเทอร์เน็ต/Network</option>
                            <option value="โปรแกรม">โปรแกรม/Software</option>

                            <?php if(count($types) > 0): ?>
                                <option value="" disabled>──────────────</option> <?php endif; ?>

                            <?php 
                                // รายชื่อมาตรฐานที่มีอยู่แล้ว (เพื่อเช็คไม่ให้แสดงซ้ำ)
                                $standard_items = ['เครื่องคอมพิวเตอร์', 'โน้ตบุ๊ก', 'เครื่องพิมพ์', 'อินเทอร์เน็ต', 'โปรแกรม', 'อื่นๆ'];
                                
                                foreach ($types as $t): 
                                    // ถ้าชื่อที่ Admin เพิ่ม ไม่ตรงกับของเดิม ค่อยแสดงออกมา
                                    if (!in_array($t['type_name'], $standard_items)): 
                            ?>
                                    <option value="<?php echo htmlspecialchars($t['type_name']); ?>">
                                        <?php echo htmlspecialchars($t['type_name']); ?>
                                    </option>
                            <?php 
                                    endif; 
                                endforeach; 
                            ?>

                            <option value="" disabled>──────────────</option>
                            <option value="อื่นๆ">อื่นๆ</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">เลขทะเบียน (ถ้ามี)</label>
                    <div class="input-box"><i class="fa-solid fa-barcode"></i><input type="text" name="asset_number" class="form-control" placeholder="เช่น PC-001"></div>
                </div>
            </div>

            <div class="col-right" style="display:flex; flex-direction:column;">
                <div class="form-group" style="flex-grow:1; display:flex; flex-direction:column;">
                    <label class="form-label">อาการ/รายละเอียด <span style="color:var(--danger)">*</span></label>
                    <div class="input-box" style="flex-grow:1;">
                        <i class="fa-solid fa-triangle-exclamation" style="top:12px;"></i>
                        <textarea name="issue_details" class="form-control" required placeholder="อธิบายอาการเสียที่พบ..." style="height:100%; resize:none;"></textarea>
                    </div>
                </div>

                <div class="form-group" style="margin-top:15px;">
                    <label class="form-label">รูปภาพประกอบ</label>
                    <div class="upload-zone">
                        <input type="file" name="repair_image" accept="image/*" onchange="previewImage(this)">
                        <div class="upload-icon"><i class="fa-solid fa-cloud-arrow-up"></i></div>
                        <div style="font-weight:600; color:#475569; font-size:0.9rem;">คลิกเพื่อเลือกไฟล์ หรือลากวาง</div>
                        <div id="file-name" style="margin-top:5px; color:var(--primary); font-weight:600; font-size:0.85rem;"></div>
                    </div>
                </div>
            </div>

        </div>

        <div class="form-footer">
            <button type="button" onclick="confirmSend(event)" class="btn-submit">
                <i class="fa-solid fa-paper-plane"></i> ส่งเรื่องแจ้งซ่อม
            </button>
        </div>

    </form>
</div>

<script>
function previewImage(input) {
    if (input.files && input.files[0]) {
        document.getElementById('file-name').innerText = '📸 ' + input.files[0].name;
    }
}

function confirmSend(e) {
    e.preventDefault();
    const form = document.querySelector('form');
    if (!form.checkValidity()) { form.reportValidity(); return; }

    Swal.fire({
        title: 'ยืนยันการแจ้งซ่อม?', text: "ตรวจสอบความถูกต้องของข้อมูล", icon: 'question',
        showCancelButton: true, confirmButtonColor: '#2563eb', cancelButtonColor: '#64748b',
        confirmButtonText: 'ยืนยัน', cancelButtonText: 'ยกเลิก',
        customClass: { popup: 'swal-custom-font' }
    }).then((result) => { if (result.isConfirmed) { form.submit(); } });
}

<?php if (isset($_GET['status']) && $_GET['status'] == 'success'): ?>
    Swal.fire({ icon: 'success', title: 'สำเร็จ!', text: 'เลขที่ใบงาน: <?php echo htmlspecialchars($_GET['no']); ?>', confirmButtonColor: '#10b981', customClass: { popup: 'swal-custom-font' } });
<?php elseif (isset($_GET['status']) && $_GET['status'] == 'error'): ?>
    Swal.fire({ icon: 'error', title: 'ผิดพลาด!', text: '<?php echo htmlspecialchars($_GET['msg']); ?>', confirmButtonColor: '#ef4444', customClass: { popup: 'swal-custom-font' } });
<?php endif; ?>
</script>

<?php 
// ปิด footer เพื่อประหยัดพื้นที่
// include 'includes/footer.php'; 
?>