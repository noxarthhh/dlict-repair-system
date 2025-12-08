<?php
// tools/reset_password.php
// Utility script to reset passwords for testing/debugging
// Usage: http://localhost/0/tools/reset_password.php?username=admin&password=123456
// WARNING: Only use this locally for testing. Delete in production!

// Only allow local requests for safety
if (!in_array($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1', ['127.0.0.1','::1','localhost'])) {
    header('HTTP/1.1 403 Forbidden');
    echo "Forbidden: run this locally only.";
    exit;
}

declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/../db_connect.php';

header('Content-Type: text/html; charset=utf-8');
echo '<meta name="viewport" content="width=device-width,initial-scale=1">';
echo '<style>body{font-family:Arial,Helvetica,sans-serif;padding:16px;max-width:800px;margin:0 auto}';
echo 'table{border-collapse:collapse;width:100%;margin:20px 0}';
echo 'th,td{border:1px solid #ddd;padding:8px;text-align:left}';
echo 'th{background-color:#f2f2f2}';
echo '.success{color:green}';
echo '.error{color:red}';
echo '.info{color:blue}';
echo '</style>';
echo '<h2>Password Reset Utility</h2>';

$username = $_GET['username'] ?? '';
$new_password = $_GET['password'] ?? '';

if ($username && $new_password) {
    try {
        // Find user
        $stmt = $pdo->prepare("SELECT staff_id, username, full_name, role FROM staffs WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            echo '<p class="error">ไม่พบผู้ใช้: ' . htmlspecialchars($username) . '</p>';
        } else {
            // Generate new hash
            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Update password
            $update = $pdo->prepare("UPDATE staffs SET password_hash = ? WHERE staff_id = ?");
            $update->execute([$new_hash, $user['staff_id']]);
            
            echo '<p class="success">✓ รีเซ็ตรหัสผ่านสำเร็จ!</p>';
            echo '<p><strong>ผู้ใช้:</strong> ' . htmlspecialchars($user['full_name']) . ' (' . htmlspecialchars($user['username']) . ')</p>';
            echo '<p><strong>บทบาท:</strong> ' . htmlspecialchars($user['role']) . '</p>';
            echo '<p><strong>รหัสผ่านใหม่:</strong> ' . htmlspecialchars($new_password) . '</p>';
            echo '<p><strong>Hash:</strong> <code>' . htmlspecialchars(substr($new_hash, 0, 30)) . '...</code></p>';
            echo '<p class="info">คุณสามารถใช้รหัสผ่านนี้เพื่อ login ได้แล้ว</p>';
        }
    } catch (PDOException $e) {
        echo '<p class="error">เกิดข้อผิดพลาด: ' . htmlspecialchars($e->getMessage()) . '</p>';
    }
} else {
    echo '<p>ใช้โดยการเพิ่ม parameters ใน URL:</p>';
    echo '<p><code>?username=admin&password=123456</code></p>';
    echo '<hr>';
    echo '<h3>รายชื่อผู้ใช้ทั้งหมด:</h3>';
    
    try {
        $stmt = $pdo->query("SELECT staff_id, username, full_name, role, 
                            CASE WHEN password_hash IS NULL OR password_hash = '' THEN 'ไม่มี' ELSE 'มี' END as has_password
                            FROM staffs ORDER BY staff_id");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($users) {
            echo '<table>';
            echo '<tr><th>ID</th><th>Username</th><th>ชื่อ-นามสกุล</th><th>บทบาท</th><th>มีรหัสผ่าน</th></tr>';
            foreach ($users as $u) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($u['staff_id']) . '</td>';
                echo '<td>' . htmlspecialchars($u['username']) . '</td>';
                echo '<td>' . htmlspecialchars($u['full_name']) . '</td>';
                echo '<td>' . htmlspecialchars($u['role']) . '</td>';
                echo '<td>' . htmlspecialchars($u['has_password']) . '</td>';
                echo '</tr>';
            }
            echo '</table>';
        } else {
            echo '<p>ไม่พบผู้ใช้</p>';
        }
    } catch (PDOException $e) {
        echo '<p class="error">เกิดข้อผิดพลาด: ' . htmlspecialchars($e->getMessage()) . '</p>';
    }
}

echo '<hr>';
echo '<p><strong>คำเตือน:</strong> ลบไฟล์นี้หลังจากใช้งานเสร็จแล้วเพื่อความปลอดภัย</p>';
?>

