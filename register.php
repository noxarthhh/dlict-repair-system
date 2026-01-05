<?php
// register.php - Compact Design & Confirm Password
session_start();
include 'db_connect.php';

$error_message = '';
$success_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username         = trim($_POST['username'] ?? '');
    $password         = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $email            = trim($_POST['email'] ?? '');
    $full_name        = trim($_POST['full_name'] ?? '');
    $group_name       = $_POST['group_name'] ?? '';
    $position         = $_POST['position'] ?? '';
    $role             = 'requester'; 

    if (empty($username) || empty($password) || empty($confirm_password) || empty($full_name) || empty($email)) {
        $error_message = "กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วน";
    } elseif ($password !== $confirm_password) {
        $error_message = "รหัสผ่านไม่ตรงกัน";
    } else {
        $stmt = $pdo->prepare("SELECT staff_id FROM staffs WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        
        if ($stmt->rowCount() > 0) {
            $error_message = "ชื่อผู้ใช้งานหรืออีเมลนี้มีอยู่ในระบบแล้ว";
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO staffs (username, password_hash, full_name, email, role, group_name, position) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            if ($stmt->execute([$username, $password_hash, $full_name, $email, $role, $group_name, $position])) {
                $success_message = "สมัครสมาชิกสำเร็จ! กรุณาเข้าสู่ระบบ";
            } else {
                $error_message = "เกิดข้อผิดพลาดในการบันทึกข้อมูล";
            }
        }
    }
}
$page_title = 'สมัครสมาชิก - DLICT Repair';
include 'includes/header.php';
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
<style>
    body { 
        background: linear-gradient(-45deg, #eef2ff, #f0fdf4, #fff7ed, #fdf4ff);
        background-size: 400% 400%;
        animation: gradientBG 15s ease infinite;
        font-family: 'Sarabun', sans-serif;
        overflow: hidden; /* ป้องกัน scrollbar */
        height: 100vh;
    }
    @keyframes gradientBG { 0% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } 100% { background-position: 0% 50%; } }

    .register-wrapper {
        height: calc(100vh - 60px); /* ลดความสูง wrapper ลงนิดหน่อย */
        display: flex; justify-content: center; align-items: center; padding: 10px;
    }

    .register-card {
        width: 100%; max-width: 650px;
        background: rgba(255,255,255,0.95); backdrop-filter: blur(20px);
        border-radius: 20px; padding: 25px 35px; /* ลด padding */
        border: 1px solid rgba(255,255,255,0.8);
        box-shadow: 0 15px 40px rgba(0,0,0,0.08);
        position: relative;
    }
    
    .register-header { text-align: center; margin-bottom: 20px; display: flex; align-items: center; justify-content: center; gap: 15px; }
    .icon-bg {
        width: 50px; height: 50px; background: linear-gradient(135deg, #3b82f6, #2563eb);
        color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center;
        font-size: 1.4rem; box-shadow: 0 5px 15px rgba(37, 99, 235, 0.3);
    }
    .header-text h2 { margin: 0; font-size: 1.6rem; font-weight: 800; color: #1e293b; }
    .header-text p { margin: 0; color: #64748b; font-size: 0.9rem; }

    .form-section-title {
        font-size: 0.8rem; font-weight: 700; text-transform: uppercase; color: #94a3b8;
        margin: 15px 0 10px; padding-bottom: 5px; border-bottom: 1px solid #e2e8f0;
    }
    
    .input-group-custom { margin-bottom: 12px; }
    .input-group-custom label { display: block; font-weight: 600; color: #475569; margin-bottom: 4px; font-size: 0.85rem; }
    .input-group-custom input {
        width: 100%; padding: 8px 12px; border-radius: 8px; /* ลด padding input */
        border: 1px solid #e2e8f0; background: #f8fafc; font-size: 0.95rem; color: #334155;
    }
    .input-group-custom input:focus { border-color: #3b82f6; background: #fff; outline: none; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
    
    .row-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }

    .btn-submit {
        width: 100%; padding: 10px; margin-top: 15px;
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        color: white; border: none; border-radius: 50px;
        font-size: 1rem; font-weight: 700; cursor: pointer;
        transition: 0.3s; box-shadow: 0 8px 20px -5px rgba(37, 99, 235, 0.4);
    }
    .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 12px 25px -5px rgba(37, 99, 235, 0.5); }

    .btn-back { display: block; text-align: center; margin-top: 15px; color: #64748b; text-decoration: none; font-size: 0.9rem; font-weight: 500; }
    .btn-back:hover { color: #3b82f6; }

    .alert-box { padding: 10px; border-radius: 8px; margin-bottom: 15px; font-size: 0.9rem; display: flex; align-items: center; gap: 10px; }
    .alert-danger { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
    .alert-success { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
</style>

<div class="register-wrapper">
    <div class="register-card animate__animated animate__fadeInUp">
        
        <div class="register-header">
            <div class="icon-bg"><i class="fa-solid fa-user-plus"></i></div>
            <div class="header-text">
                <h2>สมัครสมาชิกใหม่</h2>
                <p>สร้างบัญชีผู้ใช้งานระบบแจ้งซ่อม</p>
            </div>
        </div>
        
        <?php if ($error_message): ?>
            <div class="alert-box alert-danger animate__animated animate__shakeX">
                <i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success_message): ?>
            <div class="alert-box alert-success animate__animated animate__bounceIn">
                <i class="fa-solid fa-circle-check"></i>
                <div>
                    <?php echo htmlspecialchars($success_message); ?>
                    <a href="login.php" style="color:#16a34a; font-weight:700; margin-left:5px;">เข้าสู่ระบบทันที</a>
                </div>
            </div>
        <?php else: ?>
        
        <form method="POST" action="register.php">
            
            <div class="row-grid">
                <div class="input-group-custom">
                    <label>ชื่อผู้ใช้งาน (Username) <span class="text-danger">*</span></label>
                    <input type="text" name="username" required placeholder="User01" value="<?php echo htmlspecialchars($username ?? ''); ?>">
                </div>
                <div class="input-group-custom">
                    <label>อีเมล (สำหรับกู้คืนรหัสผ่าน) <span class="text-danger">*</span></label>
                    <input type="email" name="email" required placeholder="email@example.com" value="<?php echo htmlspecialchars($email ?? ''); ?>">
                </div>
            </div>

            <div class="row-grid">
                <div class="input-group-custom">
                    <label>รหัสผ่าน <span class="text-danger">*</span></label>
                    <input type="password" name="password" required placeholder="ตั้งรหัสผ่าน">
                </div>
                <div class="input-group-custom">
                    <label>ยืนยันรหัสผ่าน <span class="text-danger">*</span></label>
                    <input type="password" name="confirm_password" required placeholder="ยืนยันรหัสผ่าน">
                </div>
            </div>

            <div class="form-section-title">ข้อมูลส่วนตัว</div>

            <div class="input-group-custom">
                <label>ชื่อ-นามสกุล (ภาษาไทย) <span class="text-danger">*</span></label>
                <input type="text" name="full_name" required placeholder="นายสมชาย ใจดี" value="<?php echo htmlspecialchars($full_name ?? ''); ?>">
            </div>

            <div class="row-grid">
                <div class="input-group-custom">
                    <label>กลุ่ม/ฝ่ายงาน</label>
                    <input type="text" name="group_name" placeholder="ระบุหน่วยงาน" value="<?php echo htmlspecialchars($group_name ?? ''); ?>">
                </div>
                <div class="input-group-custom">
                    <label>ตำแหน่ง</label>
                    <input type="text" name="position" placeholder="ระบุตำแหน่ง" value="<?php echo htmlspecialchars($position ?? ''); ?>">
                </div>
            </div>
            
            <button type="submit" class="btn-submit">
                <i class="fa-solid fa-check"></i> สมัครสมาชิก
            </button>
            
            <a href="login.php" class="btn-back">
                <i class="fa-solid fa-arrow-left"></i> กลับไปหน้าเข้าสู่ระบบ
            </a>

        </form>
        <?php endif; ?>
    </div>
</div>

<?php 
// include 'includes/footer.php'; 
?>