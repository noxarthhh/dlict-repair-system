<?php
session_start();
include 'db_connect.php';

// 1. ⛔ Access Control
if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: home.php");
    exit();
}

$page_title = 'เพิ่มผู้ใช้งานใหม่';
$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $full_name = trim($_POST['full_name']);
    $group_name = trim($_POST['group_name']);
    $position = trim($_POST['position']);
    $role = $_POST['role'];

    if (empty($username) || empty($password) || empty($full_name)) {
        $error_msg = "กรุณากรอกข้อมูลที่จำเป็น (*) ให้ครบถ้วน";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM staffs WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetchColumn() > 0) {
                $error_msg = "ชื่อผู้ใช้งานนี้ (Username) มีอยู่ในระบบแล้ว";
            } else {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $sql = "INSERT INTO staffs (username, password_hash, full_name, group_name, position, role) 
                        VALUES (:username, :pass, :fname, :gname, :pos, :role)";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    'username' => $username, 'pass' => $password_hash,
                    'fname' => $full_name, 'gname' => $group_name,
                    'pos' => $position, 'role' => $role
                ]);
                $success_msg = "✅ เพิ่มผู้ใช้งาน \"$full_name\" เรียบร้อยแล้ว!";
            }
        } catch (PDOException $e) { $error_msg = "เกิดข้อผิดพลาด: " . $e->getMessage(); }
    }
}

include 'includes/header.php';
?>

<style>
    /* บังคับหน้าเดียวจบ */
    body { overflow: hidden; }

    /* จัดกึ่งกลาง และล็อกความสูง */
    .add-user-wrapper {
        height: calc(100vh - 80px); /* ความสูงจอ - Header */
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        padding: 20px;
    }

    /* การ์ดฟอร์มแนวนอน */
    .form-card-wide {
        width: 100%;
        max-width: 1000px; /* กว้างขึ้นเพื่อวาง 2 คอลัมน์สบายๆ */
        background: #fff;
        padding: 40px;
        border-radius: 16px;
        box-shadow: var(--shadow);
        border: 1px solid var(--border);
        
        /* ถ้าจอเล็กมาก ให้ Scroll เฉพาะในการ์ด */
        max-height: 100%;
        overflow-y: auto;
    }

    .form-title {
        text-align: center;
        margin-bottom: 25px;
        border-bottom: 2px solid #f1f5f9;
        padding-bottom: 15px;
    }
    .form-title h1 { margin: 0; font-size: 1.8rem; color: var(--primary); }
    .form-title p { margin: 5px 0 0; color: var(--text-muted); font-size: 1rem; }

    /* Grid Layout 2 คอลัมน์ */
    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr; /* แบ่งครึ่งซ้ายขวา */
        gap: 40px; /* ช่องว่างตรงกลาง */
    }

    /* หัวข้อย่อย */
    .section-head {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--text-main);
        margin-bottom: 20px;
        display: flex; align-items: center; gap: 10px;
    }
    
    /* ปรับแต่ง Input ให้กระชับ */
    .form-group { margin-bottom: 20px; }
    label { font-size: 0.95rem; margin-bottom: 5px; }
    input, select { padding: 12px; font-size: 1rem; }

    /* Responsive: จอเล็กให้เรียงลงมา */
    @media (max-width: 768px) {
        body { overflow: auto; }
        .add-user-wrapper { height: auto; display: block; padding-top: 40px; }
        .form-grid { grid-template-columns: 1fr; gap: 20px; }
    }
</style>

<div class="add-user-wrapper">
    
    <div class="form-card-wide">
        <div class="form-title">
            <h1>👤 เพิ่มผู้ใช้งานใหม่</h1>
            <p>สร้างบัญชีสำหรับเจ้าหน้าที่ หรือผู้แจ้งซ่อม</p>
        </div>

        <?php if ($success_msg): ?>
            <div class="alert alert-success" style="margin-bottom: 20px;"><?php echo $success_msg; ?></div>
        <?php endif; ?>
        <?php if ($error_msg): ?>
            <div class="alert alert-danger" style="margin-bottom: 20px;"><?php echo $error_msg; ?></div>
        <?php endif; ?>

        <form method="POST" action="admin_add_user.php">
            <div class="form-grid">
                
                <div class="col-left">
                    <div class="section-head" style="color: var(--primary);">
                        <span>🔐</span> ข้อมูลเข้าระบบ (Login)
                    </div>
                    
                    <div class="form-group">
                        <label>Username / Email <span style="color:red">*</span></label>
                        <input type="text" name="username" required placeholder="ตั้งชื่อผู้ใช้ หรืออีเมล" autocomplete="off">
                    </div>
                    
                    <div class="form-group">
                        <label>รหัสผ่าน (Password) <span style="color:red">*</span></label>
                        <input type="text" name="password" required placeholder="ตั้งรหัสผ่านเริ่มต้น">
                    </div>

                    <div class="form-group">
                        <label>สิทธิ์การใช้งาน (Role) <span style="color:red">*</span></label>
                        <select name="role" required style="cursor: pointer;">
                            <option value="requester">👤 Requester (ผู้แจ้งซ่อมทั่วไป)</option>
                            <option value="technician">🛠️ Technician (ช่างซ่อม)</option>
                            <option value="admin">👑 Admin (ผู้ดูแลระบบ)</option>
                        </select>
                    </div>
                </div>

                <div class="col-right" style="border-left: 1px dashed #e2e8f0; padding-left: 40px;">
                    <div class="section-head" style="color: var(--info);">
                        <span>📝</span> ข้อมูลส่วนตัว (Personal)
                    </div>

                    <div class="form-group">
                        <label>ชื่อ-นามสกุล <span style="color:red">*</span></label>
                        <input type="text" name="full_name" required placeholder="เช่น นายสมชาย ใจดี">
                    </div>

                    <div class="form-group">
                        <label>กลุ่ม/ฝ่าย</label>
                        <input type="text" name="group_name" placeholder="เช่น บริหารงานบุคคล">
                    </div>

                    <div class="form-group">
                        <label>ตำแหน่ง</label>
                        <input type="text" name="position" placeholder="เช่น นักวิชาการคอมพิวเตอร์">
                    </div>
                </div>

            </div>

            <div style="margin-top: 30px; text-align: center;">
                <button type="submit" class="btn-primary" style="width: 100%; padding: 14px; font-size: 1.1rem; border-radius: 50px;">
                    💾 บันทึกข้อมูลผู้ใช้
                </button>
            </div>
        </form>
    </div>
</div>

<?php 
// ไม่ต้องใส่ Footer เพื่อประหยัดพื้นที่
// include 'includes/footer.php'; 
?>