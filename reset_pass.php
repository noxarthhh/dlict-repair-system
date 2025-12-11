<?php
// Restrict to local requests only
if (!in_array($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1', ['127.0.0.1','::1','localhost'])) {
    header('HTTP/1.1 403 Forbidden');
    echo "Forbidden: run this locally only.";
    exit;
}

require_once 'db_connect.php'; // เรียกไฟล์เชื่อมต่อฐานข้อมูล

// 1. กำหนด User ที่จะรีเซ็ต และรหัสผ่านใหม่ (แก้ได้ที่ไฟล์นี้โดยตรง)
$target_username = 'user01';  // <-- เปลี่ยนชื่อ User ที่ต้องการแก้ตรงนี้
$new_password = '123456';    // <-- รหัสผ่านใหม่ที่ต้องการ

// 2. สร้าง Hash ใหม่
$new_hash = password_hash($new_password, PASSWORD_DEFAULT);

try {
    $stmt = $pdo->prepare("UPDATE staffs SET password_hash = :hash WHERE username = :user");
    $stmt->execute([
        ':hash' => $new_hash,
        ':user' => $target_username
    ]);

    if ($stmt->rowCount() > 0) {
        echo "<h1>✅ รีเซ็ตรหัสผ่านสำเร็จ!</h1>";
        echo "<p>User: <b>" . htmlspecialchars($target_username) . "</b></p>";
        echo "<p>หมายเหตุ: รหัสผ่านใหม่ถูกตั้งไว้เรียบร้อยแล้ว (ไม่แสดงรหัสผ่านในหน้าเว็บเพื่อความปลอดภัย)</p>";
        echo "<p>โปรดแจ้งผู้ใช้ให้ทำการเปลี่ยนรหัสผ่านหลังจากเข้าสู่ระบบครั้งแรก</p>";
        echo "<br><a href='login.php'>กลับไปหน้า Login</a>";
    } else {
        echo "<h1>⚠️ ไม่พบ User ชื่อ '" . htmlspecialchars($target_username) . "' หรือการเปลี่ยนแปลงไม่เกิดขึ้น</h1>";
    }

} catch (PDOException $e) {
    error_log(date('[Y-m-d H:i:s] ') . "reset_pass.php: " . $e->getMessage() . PHP_EOL, 3, __DIR__ . '/logs/app_errors.log');
    echo "<p>เกิดข้อผิดพลาด โปรดตรวจสอบบันทึกหรือแจ้งผู้ดูแลระบบ</p>";
}
?>