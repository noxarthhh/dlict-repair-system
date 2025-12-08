<?php
// hash_tester.php
// สคริปต์ทดสอบการสร้างและตรวจสอบ password hash
// Usage: http://localhost/0/hash_tester.php?password=123456

header('Content-Type: text/html; charset=utf-8');
echo '<meta name="viewport" content="width=device-width,initial-scale=1">';
echo '<style>body{font-family:Arial,Helvetica,sans-serif;padding:16px;max-width:800px;margin:0 auto}';
echo 'code{background:#f4f4f4;padding:2px 6px;border-radius:3px}';
echo '.info{background:#e7f3ff;padding:12px;border-left:4px solid #2196F3;margin:10px 0}';
echo '</style>';

$password_plain = $_GET['password'] ?? '123456';

echo '<h2>Password Hash Tester</h2>';
echo '<p>รหัสผ่านที่ทดสอบ: <strong>' . htmlspecialchars($password_plain) . '</strong></p>';

// สร้างรหัส Hash ใหม่
$hashed_password = password_hash($password_plain, PASSWORD_DEFAULT);

echo '<div class="info">';
echo '<h3>รหัส Hash ที่สร้าง:</h3>';
echo '<p><code style="word-break:break-all">' . htmlspecialchars($hashed_password) . '</code></p>';
echo '<p>ความยาว: ' . strlen($hashed_password) . ' ตัวอักษร</p>';
echo '</div>';

// ทดสอบการ verify
echo '<h3>ทดสอบการตรวจสอบ (password_verify):</h3>';
$test_passwords = [$password_plain, 'wrong_password', '123456', 'admin'];
foreach ($test_passwords as $test_pwd) {
    $result = password_verify($test_pwd, $hashed_password);
    $status = $result ? '✓ ถูกต้อง' : '✗ ไม่ถูกต้อง';
    $color = $result ? 'green' : 'red';
    echo '<p>ทดสอบ "' . htmlspecialchars($test_pwd) . '": <span style="color:' . $color . '">' . $status . '</span></p>';
}

echo '<hr>';
echo '<p><strong>วิธีใช้:</strong> เพิ่ม <code>?password=รหัสผ่านที่ต้องการ</code> ใน URL</p>';
?>