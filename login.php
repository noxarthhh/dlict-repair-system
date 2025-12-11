<?php
// =========================================================================
// 1. การตั้งค่าความปลอดภัยของ Session และการเริ่มต้น
// =========================================================================
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS'])); 
date_default_timezone_set('Asia/Bangkok');

session_start();
include 'db_connect.php'; 

// =========================================================================
// 2. Logout handler (จัดการการออกจากระบบ)
// =========================================================================
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    
    // ลบคุกกี้ Session ID
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    header('Location: login.php');
    exit();
}

// =========================================================================
// 3. ถ้า Login ค้างไว้ -> ไปหน้า home.php ทันที
// =========================================================================
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === TRUE) {
    header("Location: home.php");
    exit();
}

// =========================================================================
// 4. ฟังก์ชันความปลอดภัย (Brute Force & Logging)
// =========================================================================

function checkBruteForce($pdo, $username) {
    $max_attempts = 5;
    $lockout_time = 900; // 15 นาที
    
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as attempts 
        FROM login_attempts 
        WHERE username = ? 
        AND attempt_time > DATE_SUB(NOW(), INTERVAL ? SECOND)
        AND success = 0
    ");
    $stmt->execute([$username, $lockout_time]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return ($result['attempts'] >= $max_attempts);
}

function logLoginAttempt($pdo, $username, $success, $ip_address) {
    try {
        // สร้างตารางเก็บ Log ถ้ายังไม่มี
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS login_attempts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(40) NOT NULL,
                ip_address VARCHAR(45),
                success TINYINT(1) DEFAULT 0,
                attempt_time DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_username_time (username, attempt_time)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        
        $stmt = $pdo->prepare("INSERT INTO login_attempts (username, ip_address, success) VALUES (?, ?, ?)");
        $stmt->execute([$username, $ip_address, $success ? 1 : 0]);
    } catch (PDOException $e) { }
}

// =========================================================================
// 5. การประมวลผลฟอร์ม Login (POST)
// =========================================================================
$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';

    if (empty($username) || empty($password)) {
        $error_message = "กรุณากรอกชื่อผู้ใช้งานและรหัสผ่าน";
    } else {
        // กรองตัวอักษรแปลกปลอมเพื่อความปลอดภัย
        $u_safe = preg_replace('/[^A-Za-z0-9_@.\-ก-๙ ]/', '', $username);

        if (checkBruteForce($pdo, $username)) {
            $error_message = "บัญชีนี้ถูกระงับชั่วคราวเนื่องจากพยายามเข้าสู่ระบบผิดหลายครั้ง กรุณาลองใหม่ใน 15 นาที";
            logLoginAttempt($pdo, $username, false, $ip_address);
        } else {
            try {
                $stmt = $pdo->prepare("SELECT staff_id, full_name, password_hash, role FROM staffs WHERE username = ? LIMIT 1");
                $stmt->execute([$username]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user && !empty($user['password_hash']) && password_verify($password, trim($user['password_hash']))) {
                    // ✅ Login Success
                    session_regenerate_id(true);
                    
                    $_SESSION['logged_in'] = TRUE;
                    $_SESSION['staff_id'] = $user['staff_id'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['user_role'] = $user['role'];
                    $_SESSION['login_time'] = time();
                    
                    logLoginAttempt($pdo, $username, true, $ip_address);
                    
                    // Redirect ไปหน้า Home
                    header("Location: home.php");
                    exit();

                } else {
                    // ❌ Login Failed
                    $error_message = "ชื่อผู้ใช้งานหรือรหัสผ่านไม่ถูกต้อง";
                    logLoginAttempt($pdo, $username, false, $ip_address);
                }
            } catch (PDOException $e) {
                $error_message = "ระบบฐานข้อมูลขัดข้อง กรุณาลองใหม่อีกครั้ง";
            }
        }
    }
}

// =========================================================================
// 6. ส่วน HTML/Frontend (Design + Logo + Register Button)
// =========================================================================
$page_title = 'เข้าสู่ระบบ - ระบบแจ้งซ่อม DLICT';
include 'includes/header.php';
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

<style>
    /* พื้นหลังเคลื่อนไหว */
    body { 
        overflow: hidden; 
        background: linear-gradient(-45deg, #eef2ff, #f0fdf4, #fff7ed, #fdf4ff);
        background-size: 400% 400%;
        animation: gradientBG 15s ease infinite;
    }
    @keyframes gradientBG { 0% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } 100% { background-position: 0% 50%; } }

    /* Layout จัดกลาง */
    .login-wrapper {
        height: calc(100vh - 80px);
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 20px;
    }

    /* การ์ด Login กระจกฝ้า */
    .login-card {
        width: 100%;
        max-width: 450px;
        background: rgba(255,255,255,0.85);
        backdrop-filter: blur(20px);
        border-radius: 24px;
        padding: 40px;
        border: 1px solid rgba(255,255,255,0.6);
        box-shadow: 0 20px 50px rgba(0,0,0,0.1);
        text-align: center;
        animation: zoomIn 0.5s;
    }

    /* โลโก้ */
    .logo-container {
        width: 100px;
        height: 100px;
        margin: 0 auto 20px;
        background: #fff;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        animation: bounceIn 1s;
    }
    .logo-container img {
        width: 70%;
        height: auto;
    }

    /* ข้อความหัวข้อ */
    .login-title { font-size: 1.8rem; font-weight: 800; color: #1e293b; margin-bottom: 5px; }
    .login-subtitle { color: #64748b; margin-bottom: 30px; font-size: 0.95rem; }

    /* กล่อง Input */
    .input-group { position: relative; margin-bottom: 20px; text-align: left; }
    .input-group label { display: block; font-weight: 700; color: #475569; margin-bottom: 8px; font-size: 0.9rem; }
    .input-wrapper { position: relative; }
    .input-wrapper i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #94a3b8; }
    
    .form-control {
        width: 100%; padding: 12px 12px 12px 45px;
        border: 2px solid #e2e8f0; border-radius: 12px;
        font-size: 1rem; transition: 0.3s; background: #f8fafc;
    }
    .form-control:focus { border-color: var(--primary); background: #fff; box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1); outline: none; }

    /* ปุ่ม Login (สีหลัก) */
    .btn-login {
        width: 100%; padding: 14px;
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        color: white; border: none; border-radius: 50px;
        font-size: 1.1rem; font-weight: 700; cursor: pointer;
        box-shadow: 0 10px 20px -5px rgba(37, 99, 235, 0.4);
        transition: 0.3s; margin-top: 10px;
    }
    .btn-login:hover { transform: translateY(-3px); box-shadow: 0 15px 30px -5px rgba(37, 99, 235, 0.5); }

    /* 🌟 ปุ่ม Register (สีรอง/ขอบใส) */
    .btn-register {
        display: inline-block;
        width: 100%;
        padding: 12px;
        background: transparent;
        color: #64748b;
        border: 2px solid #cbd5e1;
        border-radius: 50px;
        font-size: 1rem;
        font-weight: 600;
        text-decoration: none;
        margin-top: 15px;
        transition: 0.3s;
    }
    .btn-register:hover {
        background: #f1f5f9;
        color: #334155;
        border-color: #94a3b8;
        transform: translateY(-2px);
    }
    
    /* เส้นคั่น (Divider) */
    .divider { margin: 20px 0; position: relative; text-align: center; }
    .divider::before { content: ''; position: absolute; top: 50%; left: 0; width: 100%; height: 1px; background: #e2e8f0; }
    .divider span { position: relative; background: rgba(255,255,255,0.9); padding: 0 10px; color: #94a3b8; font-size: 0.85rem; }

    /* Alert Box */
    .alert-box {
        background: #fef2f2; color: #dc2626; border: 1px solid #fecaca;
        padding: 12px; border-radius: 10px; margin-bottom: 20px; font-size: 0.9rem;
        display: flex; align-items: center; gap: 10px; justify-content: center;
    }
</style>

<div class="login-wrapper">
    <div class="login-card">
        
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
            </div>
            
            <button type="submit" class="btn-login">
                เข้าสู่ระบบ <i class="fa-solid fa-arrow-right" style="margin-left:5px;"></i>
            </button>
        </form>
        
        <div class="divider"><span>หรือ</span></div>
        
        <a href="register.php" class="btn-register">
            <i class="fa-solid fa-user-plus"></i> สมัครสมาชิกใหม่
        </a>
        
        <div style="margin-top: 25px; font-size: 0.85rem; color: #94a3b8;">
            © <?php echo date('Y'); ?> DLICT Repair System
        </div>
    </div>
</div>

<?php 
// include 'includes/footer.php'; 
?>