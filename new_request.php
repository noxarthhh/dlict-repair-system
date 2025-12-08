<?php
session_start();
include 'db_connect.php'; 

// 1. ตรวจสอบการ Login
if (!isset($_SESSION['logged_in'])) { header("Location: login.php"); exit(); }

$page_title = 'แจ้งซ่อมใหม่';
$staff_id = $_SESSION['staff_id'];
$full_name = $_SESSION['full_name'];

// ดึงข้อมูลผู้แจ้งเพิ่มเติม
$stmt = $pdo->prepare("SELECT group_name, position FROM staffs WHERE staff_id = ?");
$stmt->execute([$staff_id]);
$user_info = $stmt->fetch();

include 'includes/header.php'; 
?>

<style>
    /* ล้างค่า Container เดิมชั่วคราวเพื่อให้เราคุมเอง */
    main.container {
        display: grid !important;
        place-items: center !important; /* คำสั่งเทพ จัดกลางทั้งแนวตั้งและแนวนอน */
        height: 100% !important;
        overflow-y: auto !important; /* ให้ Scroll ได้ถ้าเนื้อหาล้น */
        padding: 20px !important;
    }

    /* ปรับแต่งการ์ดฟอร์ม */
    .form-card {
        width: 100%;
        max-width: 750px; /* ความกว้างกำลังดี */
        padding: 40px;
        margin: auto; /* ดันตัวเองให้อยู่ตรงกลาง */
        background: #fff;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1); /* เงาสวยๆ */
    }

    .section-header { text-align: center; margin-bottom: 30px; }
    .section-header h1 { font-size: 2rem; color: var(--primary); margin-bottom: 5px; }
    .section-header p { color: var(--text-muted); font-size: 1rem; }

    /* ปรับช่องกรอกข้อมูลให้ดูดี */
    .form-group label { font-size: 1rem; color: var(--text-main); }
    .form-control-plaintext {
        background: #f1f5f9; 
        border: 1px solid #e2e8f0; 
        color: #64748b; 
        font-weight: 600;
        cursor: not-allowed;
    }
    
    /* กล่องอัปโหลดรูป */
    .upload-box {
        border: 2px dashed #cbd5e1;
        border-radius: 12px;
        padding: 20px;
        text-align: center;
        background: #f8fafc;
        transition: 0.2s;
    }
    .upload-box:hover { border-color: var(--primary); background: #f0f9ff; }
</style>

<div class="card form-card">
    
    <div class="section-header">
        <h1>🔔 แจ้งซ่อมใหม่</h1>
        <p>กรอกรายละเอียดปัญหาที่พบ เพื่อแจ้งเจ้าหน้าที่ตรวจสอบ</p>
    </div>

    <?php if (isset($_GET['status']) && $_GET['status'] == 'success'): ?>
        <div class="alert alert-success">
            ✅ <b>บันทึกข้อมูลเรียบร้อยแล้ว!</b> เลขที่ใบแจ้งซ่อม: <u><?php echo htmlspecialchars($_GET['no']); ?></u>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['status']) && $_GET['status'] == 'error'): ?>
        <div class="alert alert-danger">❌ เกิดข้อผิดพลาด: <?php echo htmlspecialchars($_GET['msg']); ?></div>
    <?php endif; ?>

    <form method="POST" action="submit_request.php" enctype="multipart/form-data">
        
        <div class="form-group">
            <label>👤 ข้อมูลผู้แจ้ง</label>
            <input type="text" class="form-control-plaintext" value="<?php echo htmlspecialchars($full_name); ?> (<?php echo htmlspecialchars($user_info['position'] ?? '-'); ?>)" disabled>
        </div>

        <hr style="margin: 25px 0; border: 0; border-top: 1px dashed #e2e8f0;">

        <div class="form-group">
            <label>💻 หมายเลขทะเบียนครุภัณฑ์ / ชื่อเครื่อง (ถ้ามี)</label>
            <input type="text" name="asset_number" placeholder="เช่น PC-001, Printer-05 (เว้นว่างได้ถ้าจำไม่ได้)">
        </div>

        <div class="form-group">
            <label>⚠️ อาการ/รายละเอียดปัญหา <span style="color:red">*</span></label>
            <textarea name="issue_details" rows="5" required placeholder="อธิบายอาการเสียที่พบอย่างละเอียด..."></textarea>
        </div>

        <div class="form-group">
            <label>📸 รูปภาพประกอบ (ถ้ามี)</label>
            <div class="upload-box">
                <input type="file" name="repair_image" accept="image/*" style="width: auto;">
                <div style="font-size: 0.85rem; color: #94a3b8; margin-top: 5px;">รองรับไฟล์ JPG, PNG (ขนาดไม่เกิน 5MB)</div>
            </div>
        </div>
        
        <div style="margin-top: 30px; text-align: center;">
            <button type="submit" class="btn-primary" style="padding: 14px 50px; font-size: 1.1rem; width: 100%; border-radius: 50px; box-shadow: 0 4px 15px rgba(37, 99, 235, 0.3);">
                🚀 ส่งเรื่องแจ้งซ่อม
            </button>
        </div>
    </form>
</div>

<?php include 'includes/footer.php'; ?>