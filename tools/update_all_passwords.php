<?php
/**
 * สคริปต์สำหรับอัปเดตรหัสผ่านทั้งหมดในฐานข้อมูล
 * รหัสผ่านเริ่มต้น: password123
 * 
 * วิธีใช้: เปิด URL นี้ในเบราว์เซอร์
 * http://localhost/fixrequest/tools/update_all_passwords.php
 */

header('Content-Type: text/html; charset=utf-8');
echo '<meta name="viewport" content="width=device-width,initial-scale=1">';
echo '<style>
    body { font-family: Arial, Helvetica, sans-serif; padding: 20px; max-width: 800px; margin: 0 auto; }
    .success { background: #d4edda; color: #155724; padding: 12px; border-radius: 4px; margin: 10px 0; }
    .error { background: #f8d7da; color: #721c24; padding: 12px; border-radius: 4px; margin: 10px 0; }
    .info { background: #d1ecf1; color: #0c5460; padding: 12px; border-radius: 4px; margin: 10px 0; }
    code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; }
    table { border-collapse: collapse; width: 100%; margin: 20px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
</style>';

// Restrict this utility to local requests only
if (!in_array($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1', ['127.0.0.1','::1','localhost'])) {
    header('HTTP/1.1 403 Forbidden');
    echo "Forbidden: run this locally only.";
    exit;
}

require_once __DIR__ . '/../db_connect.php';

$default_password = 'password123';
$new_hash = password_hash($default_password, PASSWORD_DEFAULT);

echo '<h2>อัปเดตรหัสผ่านทั้งหมดในระบบ</h2>';
echo '<div class="info">';
echo '<p><strong>รหัสผ่านเริ่มต้น:</strong> <code>' . htmlspecialchars($default_password) . '</code></p>';
echo '<p><strong>Hash ที่สร้าง:</strong> <code>' . htmlspecialchars($new_hash) . '</code></p>';
echo '</div>';

try {
    // สร้างตาราง login_attempts ถ้ายังไม่มี
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
    echo '<div class="success">✓ สร้างตาราง login_attempts สำเร็จ</div>';
    
    // ดึงข้อมูลผู้ใช้ทั้งหมด
    $stmt = $pdo->query("SELECT staff_id, username, full_name, role FROM staffs");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($users)) {
        echo '<div class="error">ไม่พบข้อมูลผู้ใช้ในระบบ</div>';
    } else {
        echo '<h3>รายชื่อผู้ใช้ที่จะอัปเดต:</h3>';
        echo '<table>';
        echo '<tr><th>ID</th><th>Username</th><th>ชื่อ-นามสกุล</th><th>บทบาท</th><th>สถานะ</th></tr>';
        
        $update_stmt = $pdo->prepare("UPDATE staffs SET password_hash = ? WHERE staff_id = ?");
        $success_count = 0;
        
        foreach ($users as $user) {
            $update_stmt->execute([$new_hash, $user['staff_id']]);
            $success_count++;
            echo '<tr>';
            echo '<td>' . htmlspecialchars($user['staff_id']) . '</td>';
            echo '<td><strong>' . htmlspecialchars($user['username']) . '</strong></td>';
            echo '<td>' . htmlspecialchars($user['full_name']) . '</td>';
            echo '<td>' . htmlspecialchars($user['role']) . '</td>';
            echo '<td class="success">✓ อัปเดตสำเร็จ</td>';
            echo '</tr>';
        }
        
        echo '</table>';
        echo '<div class="success">';
        echo '<h3>✓ อัปเดตรหัสผ่านสำเร็จ!</h3>';
        echo '<p>อัปเดตรหัสผ่านสำหรับ <strong>' . $success_count . '</strong> บัญชี</p>';
        echo '<p><strong>รหัสผ่านเริ่มต้นสำหรับทุกบัญชี:</strong> <code>' . htmlspecialchars($default_password) . '</code></p>';
        echo '<p class="error"><strong>คำเตือน:</strong> กรุณาเปลี่ยนรหัสผ่านทันทีหลังจาก login สำเร็จ</p>';
        echo '</div>';
    }
    
} catch (PDOException $e) {
    echo '<div class="error">เกิดข้อผิดพลาด: ' . htmlspecialchars($e->getMessage()) . '</div>';
}

echo '<hr>';
echo '<p><a href="../login.php">← กลับไปหน้า Login</a></p>';
?>

