<?php
session_start();
include 'db_connect.php'; 
if (!isset($_SESSION['logged_in'])) { header("Location: login.php"); exit(); }
$page_title = 'แจ้งซ่อมใหม่';
$full_name = $_SESSION['full_name'];
include 'includes/header.php'; 
?>

<div class="container">
    <div style="text-align: center; margin-bottom: 30px;">
        <h1>📝 แบบฟอร์มแจ้งซ่อม</h1>
        <p style="color: var(--text-muted);">กรอกรายละเอียดปัญหาเพื่อแจ้งเจ้าหน้าที่</p>
    </div>

    <?php if (isset($_GET['status']) && $_GET['status'] == 'success'): ?>
        <div class="alert alert-success">✅ บันทึกข้อมูลเรียบร้อย เลขที่ใบงาน: <b><?php echo htmlspecialchars($_GET['no']); ?></b></div>
    <?php endif; ?>
    <?php if (isset($_GET['status']) && $_GET['status'] == 'error'): ?>
        <div class="alert alert-danger">❌ เกิดข้อผิดพลาด: <?php echo htmlspecialchars($_GET['msg']); ?></div>
    <?php endif; ?>

    <div class="card form-card">
        <form method="POST" action="submit_request.php" enctype="multipart/form-data">
            <div class="form-group">
                <label>👤 ผู้แจ้ง</label>
                <input type="text" value="<?php echo htmlspecialchars($full_name); ?>" disabled style="background: #e2e8f0;">
            </div>
            <div class="form-group">
                <label>💻 เลขทะเบียนครุภัณฑ์ (ถ้ามี)</label>
                <input type="text" name="asset_number" placeholder="เช่น PC-001 หรือ Printer-05">
            </div>
            <div class="form-group">
                <label>⚠️ รายละเอียดปัญหา (จำเป็น)</label>
                <textarea name="issue_details" rows="5" required placeholder="อธิบายอาการเสียที่พบ..."></textarea>
            </div>
            <div class="form-group">
                <label>📸 รูปภาพประกอบ</label>
                <input type="file" name="repair_image" accept="image/*">
            </div>
            <button type="submit" class="btn-primary" style="width:100%; padding: 15px; font-size: 1.1rem;">🚀 ส่งเรื่องแจ้งซ่อม</button>
        </form>
    </div>
</div>
<?php include 'includes/footer.php'; ?>