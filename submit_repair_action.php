<?php
session_start();
include 'db_connect.php'; 

// ตรวจสอบ Login และสิทธิ์
if (!isset($_SESSION['logged_in']) || ($_SESSION['user_role'] != 'technician' && $_SESSION['user_role'] != 'admin')) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // รับค่าจากฟอร์ม
    $request_id = $_POST['request_id'] ?? null;
    $action_taken = $_POST['action_taken'] ?? ''; // รายละเอียดการซ่อม
    $completion_date = date('Y-m-d H:i:s'); // วันที่และเวลาที่ซ่อมเสร็จ

    if ($request_id && !empty($action_taken)) {
        try {
            // อัปเดตสถานะเป็น 'Completed' บันทึกรายละเอียด และวันที่ซ่อมเสร็จ
            $sql = "
                UPDATE repair_requests 
                SET status = 'Completed', 
                    action_taken = :action,
                    repair_completion_date = :date
                WHERE request_id = :id
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'action' => $action_taken,
                'date' => $completion_date,
                'id' => $request_id
            ]);

            // Redirect กลับไป Dashboard พร้อมข้อความสำเร็จ
            header("Location: dashboard_tech.php?update=success&id={$request_id}");
            exit();

        } catch (PDOException $e) {
            // จัดการข้อผิดพลาด
            header("Location: dashboard_tech.php?error=db_complete");
            exit();
        }
    } else {
        // ข้อมูลไม่ครบ
        header("Location: dashboard_tech.php?error=missing_data");
        exit();
    }
} else {
    // ไม่อนุญาตให้เข้าถึงโดยตรง
    header("Location: dashboard_tech.php");
    exit();
}