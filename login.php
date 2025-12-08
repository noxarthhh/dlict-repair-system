<?php
// =========================================================================
// 1. การตั้งค่าความปลอดภัยของ Session และการเริ่มต้น
// =========================================================================
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS'])); 

// 🌟 ตั้งค่าเวลาเป็นประเทศไทย
date_default_timezone_set('Asia/Bangkok');

session_start();
include 'db_connect.php'; 

// =========================================================================
// 2. Logout handler (รวมไว้จุดเดียวที่นี่)
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
    header("Location: home.php"); // ✅ จุดที่ 1: ไปหน้า Home
    exit();
}

// =========================================================================
// 4. ฟังก์ชันความปลอดภัย (Brute Force & Logging)
// =========================================================================

function checkBruteForce($pdo, $username) {
    $max_attempts = 5;
    $lockout_time = 900; 
    
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
        $logDir = __DIR__ . '/logs';
        if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
        $logFile = $logDir . '/auth_debug.log';
        $now = date('Y-m-d H:i:s'); // เวลาไทย (จากบรรทัดที่ 10)
        $u_safe = preg_replace('/[^A-Za-z0-9_@.\-ก-๙ ]/', '', $username);

        if (checkBruteForce($pdo, $username)) {
            $error_message = "บัญชีนี้ถูกระงับชั่วคราวเนื่องจากพยายามเข้าสู่ระบบผิดหลายครั้ง กรุณาลองใหม่ใน 15 นาที";
            file_put_contents($logFile, "[$now] Brute force blocked: $u_safe\n", FILE_APPEND | LOCK_EX);
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
                    
                    file_put_contents($logFile, "[$now] Login SUCCESS: $u_safe\n", FILE_APPEND | LOCK_EX);
                    logLoginAttempt($pdo, $username, true, $ip_address);
                    
                    // Redirect ทุกคนไปหน้า Home
                    header("Location: home.php"); // ✅ จุดที่ 2: ไปหน้า Home
                    exit();

                } else {
                    $error_message = "ชื่อผู้ใช้งานหรือรหัสผ่านไม่ถูกต้อง";
                    file_put_contents($logFile, "[$now] Login FAILED: $u_safe\n", FILE_APPEND | LOCK_EX);
                    logLoginAttempt($pdo, $username, false, $ip_address);
                }
            } catch (PDOException $e) {
                $error_message = "ระบบฐานข้อมูลขัดข้อง กรุณาลองใหม่อีกครั้ง";
                file_put_contents($logFile, "[$now] DB Error: " . $e->getMessage() . "\n", FILE_APPEND | LOCK_EX);
            }
        }
    }
}

// =========================================================================
// 6. ส่วน HTML/Frontend
// =========================================================================
$page_title = 'เข้าสู่ระบบ - ระบบแจ้งซ่อม DLICT';
include 'includes/header.php';
?>

<div id="login-container" class="container">
    <div class="card" style="max-width: 450px; margin: 40px auto; padding: 40px;">
        <h2 style="text-align:center; color: var(--primary); margin-bottom: 30px;">เข้าสู่ระบบ</h2>
        
        <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <form id="login-form" method="POST" action="login.php">
            <div class="form-group">
                <label for="username">ชื่อผู้ใช้งาน</label>
                <input type="text" id="username" name="username" class="form-control" required placeholder="กรอก Username หรือ Email" autofocus>
            </div>
            
            <div class="form-group">
                <label for="password">รหัสผ่าน</label>
                <input type="password" id="password" name="password" class="form-control" required placeholder="กรอกรหัสผ่าน">
            </div>
            
            <button type="submit" class="btn-primary" style="width: 100%; margin-top: 20px;">
                เข้าสู่ระบบ
            </button>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>