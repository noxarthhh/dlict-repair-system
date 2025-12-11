<?php
session_start();
include 'db_connect.php'; 

// ถ้าล็อกอินอยู่แล้ว ให้เด้งไปหน้า Home (หรือถ้าจะให้ Admin ใช้หน้านี้ ก็ลบบรรทัดนี้ออกได้ครับ)
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === TRUE) {
    header("Location: home.php");
    exit();
}

$page_title = 'ลงทะเบียนเข้าใช้งาน';
$error_msg = '';
$success_msg = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // รับค่าจากฟอร์ม
    $email = trim($_POST['email']);
    $full_name = trim($_POST['full_name']);
    $position = trim($_POST['position']);
    $group_name = trim($_POST['group_name']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // 1. ตรวจสอบข้อมูลเบื้องต้น
    if (empty($email) || empty($full_name) || empty($password)) {
        $error_msg = "กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วน";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = "รูปแบบอีเมลไม่ถูกต้อง";
    } elseif ($password !== $confirm_password) {
        $error_msg = "รหัสผ่านและการยืนยันรหัสผ่านไม่ตรงกัน";
    } elseif (strlen($password) < 6) {
        $error_msg = "รหัสผ่านต้องมีความยาวอย่างน้อย 6 ตัวอักษร";
    } else {
        try {
            // 2. เช็คว่าอีเมลซ้ำไหม
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM staffs WHERE username = ?");
            $stmt->execute([$email]);
            if ($stmt->fetchColumn() > 0) {
                $error_msg = "อีเมลนี้ถูกใช้งานไปแล้ว";
            } else {
                // 3. บันทึกข้อมูล (กำหนด Role เริ่มต้นเป็น 'requester')
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO staffs (username, password_hash, full_name, position, group_name, role) VALUES (?, ?, ?, ?, ?, 'requester')");
                $stmt->execute([$email, $password_hash, $full_name, $position, $group_name]);
                
                $success_msg = "ลงทะเบียนสำเร็จ! กรุณาเข้าสู่ระบบ";
            }
        } catch (PDOException $e) {
            // Log internal error and show a generic message to the user
            error_log(date('[Y-m-d H:i:s] ') . "register.php: " . $e->getMessage() . PHP_EOL, 3, __DIR__ . '/logs/app_errors.log');
            $error_msg = "เกิดข้อผิดพลาด กรุณาลองใหม่หรือแจ้งผู้ดูแลระบบ";
        }
    }
}

include 'includes/header.php'; 
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    /* Background & Layout */
    body { 
        overflow: hidden; 
        background: linear-gradient(-45deg, #eef2ff, #f0fdf4, #fff7ed, #fdf4ff);
        background-size: 400% 400%;
        animation: gradientBG 15s ease infinite;
    }
    @keyframes gradientBG { 0% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } 100% { background-position: 0% 50%; } }

    .register-wrapper {
        height: calc(100vh - 80px);
        display: flex; justify-content: center; align-items: center; padding: 20px;
        animation: zoomIn 0.5s;
    }

    /* Glass Card */
    .reg-card {
        width: 100%; max-width: 900px;
        background: rgba(255,255,255,0.9); backdrop-filter: blur(20px);
        border-radius: 24px; border: 1px solid rgba(255,255,255,0.7);
        box-shadow: 0 20px 50px rgba(0,0,0,0.1);
        padding: 40px; display: flex; flex-direction: column; gap: 20px;
    }

    .reg-header { text-align: center; margin-bottom: 10px; }
    .reg-header h1 { font-size: 2rem; color: var(--primary); font-weight: 800; margin: 0; }
    .reg-header p { color: #64748b; margin-top: 5px; }

    /* Form Grid (2 Columns) */
    .form-grid {
        display: grid; grid-template-columns: 1fr 1fr; gap: 30px;
    }

    /* Inputs */
    .input-grp { margin-bottom: 15px; position: relative; }
    .input-grp label { display: block; font-weight: 700; color: #475569; margin-bottom: 5px; font-size: 0.9rem; }
    .input-wrapper { position: relative; }
    .input-wrapper i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #94a3b8; transition: 0.3s; }
    
    .form-control {
        width: 100%; padding: 12px 12px 12px 40px; 
        border: 2px solid #e2e8f0; border-radius: 12px;
        background: #f8fafc; font-size: 0.95rem; transition: 0.3s;
    }
    .form-control:focus { border-color: var(--primary); background: #fff; box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1); outline: none; }
    .form-control:focus + i { color: var(--primary); }

    /* Button */
    .btn-submit {
        width: 100%; padding: 14px;
        background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);
        color: white; border: none; border-radius: 50px;
        font-weight: 700; font-size: 1.1rem; cursor: pointer;
        box-shadow: 0 10px 20px rgba(79, 70, 229, 0.3);
        transition: 0.3s; margin-top: 10px;
    }
    .btn-submit:hover { transform: translateY(-3px) scale(1.02); box-shadow: 0 15px 30px rgba(79, 70, 229, 0.4); }

    .login-link { text-align: center; margin-top: 15px; font-size: 0.9rem; color: #64748b; }
    .login-link a { color: var(--primary); font-weight: 700; text-decoration: none; }
    .login-link a:hover { text-decoration: underline; }

    @media (max-width: 768px) {
        body { overflow: auto; }
        .register-wrapper { height: auto; display: block; padding-top: 40px; }
        .form-grid { grid-template-columns: 1fr; gap: 15px; }
    }
</style>

<div class="register-wrapper">
    <div class="reg-card">
        
        <div class="reg-header animate__animated animate__fadeInDown">
            <h1><i class="fa-solid fa-user-plus"></i> สมัครสมาชิกใหม่</h1>
            <p>กรอกข้อมูลเพื่อสร้างบัญชีสำหรับเข้าใช้งานระบบ</p>
        </div>

        <form id="regForm" method="POST" action="register.php" onsubmit="return validateForm()">
            <div class="form-grid">
                
                <div class="animate__animated animate__fadeInLeft">
                    <div style="margin-bottom:15px; padding-bottom:10px; border-bottom:1px dashed #cbd5e1; font-weight:700; color:#4f46e5;">
                        <i class="fa-solid fa-id-card"></i> ข้อมูลทั่วไป
                    </div>
                    
                    <div class="input-grp">
                        <label>ชื่อ-นามสกุล <span style="color:red">*</span></label>
                        <div class="input-wrapper">
                            <input type="text" name="full_name" class="form-control" required placeholder="เช่น นายสมชาย ใจดี">
                            <i class="fa-solid fa-user"></i>
                        </div>
                    </div>

                    <div class="input-grp">
                        <label>ตำแหน่ง</label>
                        <div class="input-wrapper">
                            <input type="text" name="position" class="form-control" placeholder="ระบุตำแหน่งงาน">
                            <i class="fa-solid fa-briefcase"></i>
                        </div>
                    </div>

                    <div class="input-grp">
                        <label>กลุ่ม/ฝ่ายงาน</label>
                        <div class="input-wrapper">
                            <input type="text" name="group_name" class="form-control" placeholder="ระบุสังกัด">
                            <i class="fa-solid fa-building"></i>
                        </div>
                    </div>
                </div>

                <div class="animate__animated animate__fadeInRight">
                    <div style="margin-bottom:15px; padding-bottom:10px; border-bottom:1px dashed #cbd5e1; font-weight:700; color:#4f46e5;">
                        <i class="fa-solid fa-lock"></i> ข้อมูลเข้าสู่ระบบ
                    </div>

                    <div class="input-grp">
                        <label>อีเมล (Username) <span style="color:red">*</span></label>
                        <div class="input-wrapper">
                            <input type="email" name="email" class="form-control" required placeholder="example@email.com">
                            <i class="fa-solid fa-envelope"></i>
                        </div>
                    </div>

                    <div class="input-grp">
                        <label>รหัสผ่าน <span style="color:red">*</span></label>
                        <div class="input-wrapper">
                            <input type="password" name="password" id="password" class="form-control" required placeholder="อย่างน้อย 6 ตัวอักษร">
                            <i class="fa-solid fa-key"></i>
                        </div>
                    </div>

                    <div class="input-grp">
                        <label>ยืนยันรหัสผ่าน <span style="color:red">*</span></label>
                        <div class="input-wrapper">
                            <input type="password" name="confirm_password" id="confirm_password" class="form-control" required placeholder="กรอกรหัสผ่านอีกครั้ง">
                            <i class="fa-solid fa-check-double"></i>
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn-submit animate__animated animate__pulse animate__infinite">
                ลงทะเบียนเข้าใช้งาน
            </button>

            <div class="login-link">
                มีบัญชีอยู่แล้ว? <a href="login.php">เข้าสู่ระบบที่นี่</a>
            </div>
        </form>
    </div>
</div>

<script>
    // ตรวจสอบรหัสผ่านก่อนส่ง
    function validateForm() {
        var pw = document.getElementById("password").value;
        var cpw = document.getElementById("confirm_password").value;
        if (pw != cpw) {
            Swal.fire({
                icon: 'warning',
                title: 'รหัสผ่านไม่ตรงกัน',
                text: 'กรุณาตรวจสอบการยืนยันรหัสผ่านอีกครั้ง',
                confirmButtonColor: '#f59e0b'
            });
            return false;
        }
        return true;
    }

    // แจ้งเตือนผลลัพธ์จาก PHP
    <?php if ($error_msg): ?>
        Swal.fire({ icon: 'error', title: 'ผิดพลาด', text: '<?php echo htmlspecialchars($error_msg); ?>', confirmButtonColor: '#ef4444' });
    <?php elseif ($success_msg): ?>
        Swal.fire({ 
            icon: 'success', 
            title: 'สำเร็จ!', 
            text: '<?php echo htmlspecialchars($success_msg); ?>', 
            confirmButtonColor: '#10b981' 
        }).then((result) => {
            if (result.isConfirmed) { window.location.href = 'login.php'; }
        });
    <?php endif; ?>
</script>

<?php 
// include 'includes/footer.php'; 
?>