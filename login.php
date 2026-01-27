<?php
// login.php - Compact Version (No Scroll)
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS'])); 
date_default_timezone_set('Asia/Bangkok');

session_start();
require_once 'db_connect.php'; 

// 2. Logout handler
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
    }
    header('Location: login.php');
    exit();
}

// 3. Check Login State
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === TRUE) {
    header("Location: home.php");
    exit();
}

// สร้างตาราง login_attempts อัตโนมัติ
function initLoginSystem($pdo) {
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS login_attempts (id INT AUTO_INCREMENT PRIMARY KEY, username VARCHAR(40), ip_address VARCHAR(45), success TINYINT(1), attempt_time DATETIME DEFAULT CURRENT_TIMESTAMP, INDEX idx_username_time (username, attempt_time)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (PDOException $e) { }
}
initLoginSystem($pdo);

// 4. Security Functions
function checkBruteForce($pdo, $username) {
    $max_attempts = 5;
    $lockout_time = 900; 
    $stmt = $pdo->prepare("SELECT COUNT(*) as attempts FROM login_attempts WHERE username = ? AND ip_address = ? AND attempt_time > DATE_SUB(NOW(), INTERVAL ? SECOND) AND success = 0");
    $stmt->execute([$username, $_SERVER['REMOTE_ADDR'], $lockout_time]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return ($result['attempts'] >= $max_attempts);
}

function logLoginAttempt($pdo, $username, $success, $ip_address) {
    try {
        $stmt = $pdo->prepare("INSERT INTO login_attempts (username, ip_address, success) VALUES (?, ?, ?)");
        $stmt->execute([$username, $ip_address, $success ? 1 : 0]);
    } catch (PDOException $e) { }
}

// 5. Process Login
$error_message = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';

    if (empty($username) || empty($password)) {
        $error_message = "กรุณากรอกชื่อผู้ใช้งานและรหัสผ่าน";
    } else {
        if (checkBruteForce($pdo, $username)) {
            $error_message = "บัญชีนี้ถูกระงับชั่วคราว กรุณาลองใหม่ใน 15 นาที";
            logLoginAttempt($pdo, $username, false, $ip_address);
        } else {
            try {
                $stmt = $pdo->prepare("SELECT staff_id, full_name, password_hash, role FROM staffs WHERE username = ? LIMIT 1");
                $stmt->execute([$username]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user && password_verify($password, trim($user['password_hash']))) {
                    session_regenerate_id(true);
                    $_SESSION['logged_in'] = TRUE;
                    $_SESSION['staff_id'] = $user['staff_id'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['user_role'] = $user['role'];
                    logLoginAttempt($pdo, $username, true, $ip_address);
                    header("Location: home.php");
                    exit();
                } else {
                    $error_message = "ชื่อผู้ใช้งานหรือรหัสผ่านไม่ถูกต้อง";
                    logLoginAttempt($pdo, $username, false, $ip_address);
                }
            } catch (PDOException $e) {
                $error_message = "ระบบฐานข้อมูลขัดข้อง";
            }
        }
    }
}

$page_title = 'เข้าสู่ระบบ - ระบบบริหารจัดการและซ่อมบำรุงคอมพิวเตอร์สำนักงานเขตพื้นที่การศึกษาประถมศึกษาชลบุรี เขต 2';
include 'includes/header.php';
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> 

<style>
    body { 
        background: linear-gradient(-45deg, #eef2ff, #f0fdf4, #fff7ed, #fdf4ff);
        background-size: 400% 400%;
        animation: gradientBG 15s ease infinite;
        overflow-y: auto;
        min-height: 100vh;
    }
    @keyframes gradientBG { 0% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } 100% { background-position: 0% 50%; } }

    .login-wrapper {
        height: 100vh; /* เต็มความสูงหน้าจอ */
        display: flex; justify-content: center; align-items: center; 
        padding: 0 20px;
    }

    .login-card {
        width: 100%; max-width: 400px; /* ลดความกว้างลง */
        background: rgba(255,255,255,0.9); backdrop-filter: blur(20px);
        border-radius: 20px; padding: 30px; /* ลด padding */
        border: 1px solid rgba(255,255,255,0.6);
        box-shadow: 0 15px 40px rgba(0,0,0,0.1);
        text-align: center; 
        position: relative;
    }

    .logo-container {
        width: 80px; height: 80px; margin: 0 auto 15px; /* ลดขนาดโลโก้ */
        background: #fff; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        box-shadow: 0 8px 15px rgba(0,0,0,0.1); 
    }
    .logo-container img { width: 65%; height: auto; }

    .login-title { font-size: 1.5rem; font-weight: 800; color: #1e293b; margin-bottom: 5px; }
    .login-subtitle { color: #64748b; margin-bottom: 20px; font-size: 0.85rem; }

    .input-group { position: relative; margin-bottom: 15px; text-align: left; }
    .input-group label { display: block; font-weight: 600; color: #475569; margin-bottom: 5px; font-size: 0.85rem; }
    .input-wrapper { position: relative; }
    .input-wrapper i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 0.9rem; }
    
    .form-control {
        width: 100%; padding: 10px 10px 10px 35px; /* ลด padding input */
        border: 1px solid #e2e8f0; border-radius: 10px;
        font-size: 0.95rem; transition: 0.3s; background: #f8fafc;
    }
    .form-control:focus { border-color: var(--primary); background: #fff; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); outline: none; }

    .btn-login {
        width: 100%; padding: 12px;
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        color: white; border: none; border-radius: 50px;
        font-size: 1rem; font-weight: 700; cursor: pointer;
        box-shadow: 0 8px 15px -5px rgba(37, 99, 235, 0.4);
        transition: 0.3s; margin-top: 10px;
    }
    .btn-login:hover { transform: translateY(-2px); box-shadow: 0 12px 20px -5px rgba(37, 99, 235, 0.5); }

    .forgot-link {
        display: block; text-align: right; margin-top: 5px;
        font-size: 0.8rem; color: #64748b; text-decoration: none; font-weight: 500;
        transition: 0.2s;
    }
    .forgot-link:hover { color: var(--primary); text-decoration: underline; }

    .btn-register {
        display: inline-block; width: 100%; padding: 10px;
        background: transparent; color: #64748b;
        border: 1px solid #cbd5e1; border-radius: 50px;
        font-size: 0.9rem; font-weight: 600; text-decoration: none;
        margin-top: 15px; transition: 0.3s;
    }
    .btn-register:hover { background: #f1f5f9; color: #334155; border-color: #94a3b8; transform: translateY(-2px); }
    
    .divider { margin: 15px 0; position: relative; text-align: center; }
    .divider::before { content: ''; position: absolute; top: 50%; left: 0; width: 100%; height: 1px; background: #e2e8f0; }
    .divider span { position: relative; background: rgba(255,255,255,0.9); padding: 0 10px; color: #94a3b8; font-size: 0.75rem; }

    .alert-box {
        background: #fef2f2; color: #dc2626; border: 1px solid #fecaca;
        padding: 10px; border-radius: 8px; margin-bottom: 15px; font-size: 0.85rem;
        display: flex; align-items: center; gap: 8px; justify-content: center;
    }
    
    .footer-text { margin-top: 20px; font-size: 0.75rem; color: #94a3b8; }
</style>

<div class="login-wrapper">
    <div class="login-card animate__animated animate__fadeInUp">
        
        <div class="logo-container">
            <img src="images/Logo67.png" alt="Logo">
        </div>
        
        <div class="login-title">เข้าสู่ระบบ</div>
        <p class="login-subtitle">ระบบแจ้งซ่อมและบริการ DLICT Repair</p>

        <?php if ($error_message): ?>
            <div class="alert-box animate__animated animate__shakeX">
                <i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="input-group">
                <label>ชื่อผู้ใช้งาน / อีเมล</label>
                <div class="input-wrapper">
                    <input type="text" name="username" class="form-control" required placeholder="ระบุ Username / Email..." autofocus>
                    <i class="fa-solid fa-user"></i>
                </div>
            </div>
            
            <div class="input-group">
                <label>รหัสผ่าน</label>
                <div class="input-wrapper">
                    <input type="password" name="password" class="form-control" required placeholder="ระบุรหัสผ่าน...">
                    <i class="fa-solid fa-lock"></i>
                </div>
                <a href="#" class="forgot-link" onclick="forgotPassword()">ลืมรหัสผ่าน?</a>
            </div>
            
            <button type="submit" class="btn-login">
                เข้าสู่ระบบ <i class="fa-solid fa-arrow-right" style="margin-left:5px;"></i>
            </button>
        </form>
        
        <div class="divider"><span>หรือ</span></div>
        
        <a href="register.php" class="btn-register">
            <i class="fa-solid fa-user-plus"></i> สมัครสมาชิกใหม่
        </a>
        
        <div class="footer-text">
            © <?php echo date('Y'); ?> DLICT Repair System
        </div>
    </div>
</div>

<script>
    // ฟังก์ชันจัดการลืมรหัสผ่าน (แก้ไขแล้ว)
    function forgotPassword() {
    Swal.fire({
        title: 'ลืมรหัสผ่าน?',
        text: "กรุณากรอกอีเมลของคุณเพื่อรับลิงก์เปลี่ยนรหัสผ่าน",
        input: 'email',
        inputPlaceholder: 'name@example.com',
        showCancelButton: true,
        confirmButtonText: 'ส่งลิงก์',
        cancelButtonText: 'ยกเลิก',
        confirmButtonColor: '#2563eb',
        showLoaderOnConfirm: true,
        preConfirm: (email) => {
            return fetch('send_reset_link.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email: email })
            })
            .then(response => response.json()) 
            .then(data => {
                if (data.status === 'error') {
                    throw new Error(data.message);
                }
                return data;
            })
            .catch(error => {
                Swal.showValidationMessage(`${error.message}`);
            })
        },
        allowOutsideClick: () => !Swal.isLoading()
    }).then((result) => {
        if (result.isConfirmed && result.value.status === 'success') {
            Swal.fire({
                icon: 'success',
                title: 'ส่งเรียบร้อย!',
                text: 'กรุณาตรวจสอบกล่องจดหมาย (หรือ Junk Mail) เพื่อเปลี่ยนรหัสผ่าน',
                confirmButtonColor: '#2563eb'
            });
        }
    })
}
</script>

<?php 
// include 'includes/footer.php'; 
?>
</body>
</html>