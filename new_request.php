<?php
session_start();
include 'db_connect.php'; 

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

    /* Wrapper จัดกลาง */
    .center-wrapper {
        height: calc(100vh - 80px);
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        padding: 10px 20px;
    }

    /* การ์ดฟอร์มแนวนอน (Wide Card) */
    .form-card-wide {
        width: 100%;
        max-width: 1100px;
        background: var(--card-bg);
        padding: 30px;
        border-radius: 16px;
        box-shadow: var(--shadow);
        border: 1px solid var(--border);
        display: flex;
        flex-direction: column;
        
        /* ให้ Scroll ภายในถ้าจอเล็กมาก */
        max-height: 100%;
        overflow-y: auto;
    }

    /* ส่วนหัวฟอร์ม */
    .form-header {
        text-align: center;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid var(--border);
        flex-shrink: 0;
    }
    .form-header h1 { margin: 0; font-size: 1.8rem; color: var(--primary); }
    .form-header p { margin: 5px 0 0; color: var(--text-muted); font-size: 1rem; }

    /* Grid Layout: แบ่งซ้ายขวา */
    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr; /* แบ่งครึ่ง */
        gap: 30px;
        flex-grow: 1; /* ยืดเต็มพื้นที่ */
    }

    /* Column Styles */
    .col-left, .col-right {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    /* Input Group */
    .form-group label { font-size: 0.95rem; margin-bottom: 5px; color: var(--text-main); font-weight: 600; }
    
    /* กล่องข้อมูลผู้ใช้ (Readonly) */
    .user-info-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
    }
    .readonly-input {
        background-color: var(--bg-body);
        color: var(--text-muted);
        cursor: not-allowed;
        border: 1px solid var(--border);
        font-size: 0.9rem;
    }

    /* กล่องอัปโหลด */
    .upload-box {
        border: 2px dashed var(--border);
        background: var(--input-bg);
        border-radius: 12px;
        padding: 15px;
        text-align: center;
        transition: 0.2s;
    }
    .upload-box:hover { border-color: var(--primary); }

    /* Responsive */
    @media (max-width: 900px) {
        body { overflow: auto; }
        .center-wrapper { height: auto; display: block; padding-top: 30px; }
        .form-grid { grid-template-columns: 1fr; gap: 20px; }
    }
</style>

<div class="center-wrapper">
    
    <div class="form-card-wide">
        
        <div class="form-header">
            <h1>🔔 แจ้งซ่อมใหม่</h1>
            <p>กรอกรายละเอียดปัญหาเพื่อแจ้งเจ้าหน้าที่</p>
        </div>

        <?php if (isset($_GET['status']) && $_GET['status'] == 'success'): ?>
            <div class="alert alert-success" style="text-align:center;">✅ บันทึกข้อมูลเรียบร้อย เลขที่: <b><?php echo htmlspecialchars($_GET['no']); ?></b></div>
        <?php endif; ?>
        <?php if (isset($_GET['status']) && $_GET['status'] == 'error'): ?>
            <div class="alert alert-danger" style="text-align:center;">❌ เกิดข้อผิดพลาด: <?php echo htmlspecialchars($_GET['msg']); ?></div>
        <?php endif; ?>

        <form method="POST" action="submit_request.php" enctype="multipart/form-data" style="display: flex; flex-direction: column; flex-grow: 1;">
            <div class="form-grid">
                
                <div class="col-left">
                    
                    <div class="form-group">
                        <label>👤 ข้อมูลผู้แจ้ง</label>
                        <input type="text" class="readonly-input" value="<?php echo htmlspecialchars($full_name); ?>" disabled style="margin-bottom: 10px;">
                        <div class="user-info-grid">
                            <input type="text" class="readonly-input" value="<?php echo htmlspecialchars($user_info['position'] ?? '-'); ?>" disabled>
                            <input type="text" class="readonly-input" value="<?php echo htmlspecialchars($user_info['group_name'] ?? '-'); ?>" disabled>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>💻 หมายเลขทะเบียนครุภัณฑ์</label>
                        <input type="text" name="asset_number" placeholder="เช่น PC-001 (เว้นว่างได้)">
                    </div>

                    <div class="form-group">
                        <label>📸 รูปภาพประกอบ</label>
                        <div class="upload-box">
                            <input type="file" name="repair_image" accept="image/*" style="width:auto; font-size:0.9rem;">
                            <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 5px;">รองรับไฟล์ภาพ JPG, PNG</div>
                        </div>
                    </div>
                </div>

                <div class="col-right">
                    <div class="form-group" style="height: 100%; display: flex; flex-direction: column;">
                        <label>⚠️ รายละเอียดปัญหา <span style="color:red">*</span></label>
                        <textarea name="issue_details" required placeholder="อธิบายอาการเสียที่พบอย่างละเอียด..." 
                                  style="flex-grow: 1; min-height: 200px; resize: none;"></textarea>
                    </div>
                </div>

            </div>

            <div style="margin-top: 20px; text-align: center; padding-top: 15px; border-top: 1px dashed var(--border);">
                <button type="submit" class="btn-primary" style="padding: 12px 60px; font-size: 1.1rem; border-radius: 50px;">
                    🚀 ส่งเรื่องแจ้งซ่อม
                </button>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>