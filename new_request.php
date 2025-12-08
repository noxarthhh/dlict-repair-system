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
    /* บังคับหน้าเดียวจบ */
    body { overflow: hidden; }

    /* Wrapper จัดกึ่งกลางและล็อกความสูง */
    .request-wrapper {
        height: calc(100vh - 80px); /* ความสูงจอ - Header */
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        padding: 10px 20px;
    }

    /* การ์ดแนวนอน (Wide Card) */
    .form-card-wide {
        width: 100%;
        max-width: 1100px; /* กว้างพอสำหรับ 2 คอลัมน์ */
        background: #fff;
        padding: 35px;
        border-radius: 16px;
        box-shadow: var(--shadow);
        border: 1px solid var(--border);
        
        /* ถ้าจอเล็กมากจริงๆ ให้ Scroll เฉพาะในการ์ด */
        max-height: 100%;
        overflow-y: auto;
    }

    /* หัวข้อหน้า */
    .form-header {
        text-align: center;
        margin-bottom: 25px;
        padding-bottom: 15px;
        border-bottom: 2px solid #f1f5f9;
    }
    .form-header h1 { margin: 0; font-size: 1.8rem; color: var(--primary); }
    .form-header p { margin: 5px 0 0; color: var(--text-muted); font-size: 1rem; }

    /* Grid Layout: แบ่งซ้ายขวา */
    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr; /* แบ่งครึ่ง 50:50 */
        gap: 40px; /* ช่องว่างตรงกลาง */
    }

    /* ปรับแต่ง Input ให้สวยงาม */
    .form-group { margin-bottom: 20px; }
    label { font-size: 1rem; margin-bottom: 8px; color: var(--text-main); }
    
    /* กล่องอัปโหลดรูป */
    .upload-box {
        border: 2px dashed #cbd5e1;
        background: #f8fafc;
        border-radius: 12px;
        padding: 15px;
        text-align: center;
        transition: 0.2s;
    }
    .upload-box:hover { border-color: var(--primary); background: #f0f9ff; }

    /* Responsive: จอเล็กเรียงลงมา */
    @media (max-width: 900px) {
        body { overflow: auto; }
        .request-wrapper { height: auto; display: block; padding-top: 30px; }
        .form-grid { grid-template-columns: 1fr; gap: 20px; }
    }
</style>

<div class="request-wrapper">
    
    <div class="form-card-wide">
        
        <div class="form-header">
            <h1>🔔 แจ้งซ่อมใหม่</h1>
            <p>กรอกรายละเอียดปัญหาที่พบ เพื่อแจ้งเจ้าหน้าที่</p>
        </div>

        <?php if (isset($_GET['status']) && $_GET['status'] == 'success'): ?>
            <div class="alert alert-success" style="text-align:center;">
                ✅ <b>บันทึกข้อมูลเรียบร้อยแล้ว!</b> เลขที่ใบแจ้งซ่อม: <u><?php echo htmlspecialchars($_GET['no']); ?></u>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['status']) && $_GET['status'] == 'error'): ?>
            <div class="alert alert-danger" style="text-align:center;">
                ❌ เกิดข้อผิดพลาด: <?php echo htmlspecialchars($_GET['msg']); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="submit_request.php" enctype="multipart/form-data">
            <div class="form-grid">
                
                <div class="col-left">
                    <div class="form-group">
                        <label>👤 ผู้แจ้ง</label>
                        <input type="text" value="<?php echo htmlspecialchars($full_name); ?> (<?php echo htmlspecialchars($user_info['position'] ?? '-'); ?>)" disabled style="background-color: #f1f5f9; cursor: not-allowed; color: #64748b;">
                    </div>

                    <div class="form-group">
                        <label>💻 หมายเลขทะเบียนครุภัณฑ์ / ชื่อเครื่อง</label>
                        <input type="text" name="asset_number" placeholder="เช่น PC-001, Printer-05 (เว้นว่างได้)">
                        <small style="color:#94a3b8;">* หากไม่ทราบหรือไม่ต้องการระบุ ให้เว้นว่างไว้</small>
                    </div>

                    <div class="form-group">
                        <label>📸 รูปภาพประกอบ (ถ้ามี)</label>
                        <div class="upload-box">
                            <input type="file" name="repair_image" accept="image/*" style="width:auto; font-size:0.9rem;">
                            <div style="font-size: 0.8rem; color: #94a3b8; margin-top: 5px;">รองรับไฟล์ JPG, PNG (ไม่เกิน 5MB)</div>
                        </div>
                    </div>
                </div>

                <div class="col-right">
                    <div class="form-group" style="height: 100%; display: flex; flex-direction: column;">
                        <label>⚠️ รายละเอียดปัญหา <span style="color:red">*</span></label>
                        <textarea name="issue_details" required placeholder="อธิบายอาการเสียที่พบอย่างละเอียด..." 
                                  style="flex-grow: 1; min-height: 200px; resize: vertical;"></textarea>
                    </div>
                </div>

            </div>

            <div style="margin-top: 30px; text-align: center; border-top: 1px dashed #e2e8f0; padding-top: 20px;">
                <button type="submit" class="btn-primary" style="padding: 14px 60px; font-size: 1.1rem; border-radius: 50px; box-shadow: 0 4px 15px rgba(37, 99, 235, 0.3);">
                    🚀 ส่งเรื่องแจ้งซ่อม
                </button>
            </div>
        </form>
    </div>
</div>

<?php 
// ไม่ต้องใส่ Footer เพื่อประหยัดพื้นที่
// include 'includes/footer.php'; 
?>
</body>
</html>