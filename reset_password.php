<?php
// reset_password.php
session_start();
require 'db_connect.php';

$message = '';
$token = $_GET['token'] ?? '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $token = $_POST['token'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password !== $confirm_password) {
        $message = "รหัสผ่านไม่ตรงกัน";
    } else {
        // ตรวจสอบ Token
        $current_time = date("Y-m-d H:i:s");
        $stmt = $pdo->prepare("SELECT email FROM password_resets WHERE token = ? AND expires_at >= ?");
        $stmt->execute([$token, $current_time]);
        $email = $stmt->fetchColumn();

        if ($email) {
            // เปลี่ยนรหัสผ่านในตาราง staffs (หรือ users)
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE staffs SET password_hash = ? WHERE email = ?"); // 🔴 แก้ชื่อตารางให้ตรง
            if ($stmt->execute([$hashed_password, $email])) {
                // ลบ Token ทิ้ง
                $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email]);
                
                echo "<script>
                    alert('เปลี่ยนรหัสผ่านสำเร็จ! กรุณาเข้าสู่ระบบใหม่');
                    window.location.href = 'login.php';
                </script>";
                exit;
            }
        } else {
            $message = "ลิงก์นี้หมดอายุหรือใช้งานไปแล้ว";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ตั้งรหัสผ่านใหม่</title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Sarabun', sans-serif; background: #f0f4f8; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .card { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); width: 100%; max-width: 400px; text-align: center; }
        input { width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box; }
        button { width: 100%; padding: 12px; background: #2563eb; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 1rem; }
        button:hover { background: #1d4ed8; }
        .error { color: red; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="card">
        <h2>ตั้งรหัสผ่านใหม่</h2>
        <?php if ($message): ?>
            <div class="error"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            <input type="password" name="new_password" placeholder="รหัสผ่านใหม่" required>
            <input type="password" name="confirm_password" placeholder="ยืนยันรหัสผ่านใหม่" required>
            <button type="submit">บันทึกรหัสผ่าน</button>
        </form>
    </div>
</body>
</html>