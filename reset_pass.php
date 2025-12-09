<?php
require_once 'db_connect.php'; // เรียกไฟล์เชื่อมต่อฐานข้อมูล

// 1. กำหนด User ที่จะรีเซ็ต และรหัสผ่านใหม่
$target_username = 'user01';  // <-- เปลี่ยนชื่อ User ที่ต้องการแก้ตรงนี้
$new_password = '123456';    // <-- รหัสผ่านใหม่ที่ต้องการ

// 2. สร้าง Hash ใหม่จาก Server นี้โดยตรง
$new_hash = password_hash($new_password, PASSWORD_DEFAULT);

try {
    // 3. อัปเดตลงฐานข้อมูล
    $stmt = $pdo->prepare("UPDATE staffs SET password_hash = :hash WHERE username = :user");
    $stmt->execute([
        ':hash' => $new_hash,
        ':user' => $target_username
    ]);

    if ($stmt->rowCount() > 0) {
        echo "<h1>✅ รีเซ็ตรหัสผ่านสำเร็จ!</h1>";
        echo "<p>User: <b>$target_username</b></p>";
        echo "<p>Pass: <b>$new_password</b></p>";
        echo "<p>Hash ใหม่คือ: $new_hash</p>";
        echo "<br><a href='login.php'>กลับไปหน้า Login</a>";
    } else {
        echo "<h1>⚠️ ไม่พบ User ชื่อ '$target_username' หรือรหัสผ่านเป็นค่าเดิมอยู่แล้ว</h1>";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>