<?php
// send_reset_link.php - แก้ไข Syntax Error (ใช้ Full Namespace)

// 1. ตั้งค่า Header ให้เป็น JSON
header('Content-Type: application/json; charset=utf-8');

// ปิด Error Output หน้าเว็บ
error_reporting(E_ALL);
ini_set('display_errors', 0);

$response = [];

try {
    session_start();

    // ----------------------------------------------------
    // 🔍 1. เช็คไฟล์ Database
    // ----------------------------------------------------
    if (!file_exists('db_connect.php')) {
        throw new Exception("ไม่พบไฟล์ db_connect.php");
    }
    require_once 'db_connect.php';

    // ----------------------------------------------------
    // 🔍 2. เช็คไฟล์ PHPMailer และ Include
    // ----------------------------------------------------
    $phpmailer_path = __DIR__ . '/PHPMailer/src/';

    if (!file_exists($phpmailer_path . 'PHPMailer.php')) {
        throw new Exception("ไม่พบโฟลเดอร์ PHPMailer (เช็คว่ามี folder 'PHPMailer/src' หรือไม่)");
    }

    require_once $phpmailer_path . 'Exception.php';
    require_once $phpmailer_path . 'PHPMailer.php';
    require_once $phpmailer_path . 'SMTP.php';

    // ----------------------------------------------------
    // 🏁 3. เริ่มตรวจสอบข้อมูล
    // ----------------------------------------------------
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("อนุญาตเฉพาะ Method POST เท่านั้น");
    }

    // รับค่า JSON
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    $email = $data['email'] ?? '';

    if (empty($email)) {
        throw new Exception("กรุณาระบุอีเมล");
    }

    // เช็ค Database
    $stmt = $pdo->prepare("SELECT staff_id FROM staffs WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->rowCount() == 0) {
        throw new Exception("ไม่พบอีเมลนี้ในระบบ");
    }

    // สร้าง Token
    $token = bin2hex(random_bytes(32));
    $expires_at = date("Y-m-d H:i:s", strtotime('+1 hour'));

    // ลบ Token เก่า -> บันทึกใหม่
    $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email]);
    
    $sql = "INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    if (!$stmt->execute([$email, $token, $expires_at])) {
        throw new Exception("Database Error: บันทึก Token ไม่สำเร็จ");
    }

    // ----------------------------------------------------
    // 📧 4. ส่งเมล (ใช้ชื่อเต็ม Class แทนการใช้ use)
    // ----------------------------------------------------
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    
    // Server settings
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'panpitakppt@gmail.com';  // ✅ อีเมลของคุณ
    $mail->Password   = 'flch jzrf nook oskh';    // ✅ รหัสผ่าน App Password
    $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    $mail->CharSet    = 'UTF-8';

    // Recipients
    $mail->setFrom('panpitakppt@gmail.com', 'DLICT Repair System');
    $mail->addAddress($email);

    // Content
    $resetLink = "http://localhost/dlict-repair-system/reset_password.php?token=" . $token;
    
    $mail->isHTML(true);
    $mail->Subject = 'เปลี่ยนรหัสผ่านใหม่ - DLICT Repair';
    $mail->Body    = "
        <div style='font-family: Sarabun, sans-serif;'>
            <h3>แจ้งเตือนการรีเซ็ตรหัสผ่าน</h3>
            <p>คุณได้ทำการร้องขอเปลี่ยนรหัสผ่านในระบบแจ้งซ่อม กรุณาคลิกลิงก์ด้านล่างเพื่อตั้งรหัสผ่านใหม่</p>
            <p>
                <a href='$resetLink' style='background-color:#2563eb; color:white; padding:10px 20px; text-decoration:none; border-radius:5px; display:inline-block;'>
                    เปลี่ยนรหัสผ่าน
                </a>
            </p>
            <p style='color:#666; font-size:0.9em;'>หรือคลิกที่ลิงก์นี้: <a href='$resetLink'>$resetLink</a></p>
            <p style='color:red;'>* ลิงก์นี้จะหมดอายุใน 1 ชั่วโมง</p>
        </div>
    ";

    $mail->send();
    
    $response = ['status' => 'success', 'message' => 'ส่งลิงก์ไปยังอีเมลเรียบร้อยแล้ว'];

} catch (Exception $e) {
    http_response_code(400); 
    $response = ['status' => 'error', 'message' => $e->getMessage()];
} catch (\PHPMailer\PHPMailer\Exception $e) {
    http_response_code(500);
    $response = ['status' => 'error', 'message' => 'ส่งอีเมลไม่สำเร็จ: ' . $e->getMessage()];
}

// ส่ง JSON กลับไป
echo json_encode($response);
?>