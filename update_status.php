<?php
session_start();
include 'db_connect.php'; 

// ตรวจสอบ Login และสิทธิ์ (เฉพาะ Technician และ Admin เท่านั้น)
if (!isset($_SESSION['logged_in']) || ($_SESSION['user_role'] != 'technician' && $_SESSION['user_role'] != 'admin')) {
    header("Location: login.php");
    exit();
}

$request_id = $_GET['id'] ?? null;
$action = $_GET['action'] ?? null;
$technician_id = $_SESSION['staff_id']; // ช่างที่กำลัง Login คือผู้รับเรื่อง

if ($request_id && $action == 'accept') {
    try {
        // อัปเดตสถานะเป็น 'In Progress' และกำหนด Technician ID
        $sql = "
            UPDATE repair_requests 
            SET status = 'In Progress', 
                technician_id = :tech_id
            WHERE request_id = :id AND status = 'Pending'
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'tech_id' => $technician_id,
            'id' => $request_id
        ]);

        // Redirect กลับไป Dashboard พร้อมข้อความสำเร็จ
        header("Location: dashboard_tech.php?update=success&id={$request_id}");
        exit();

    } catch (PDOException $e) {
        // จัดการข้อผิดพลาด เช่น ถ้า request_id ไม่ถูกต้อง
        header("Location: dashboard_tech.php?error=db_update");
        exit();
    }
} else {
    // ถ้าไม่มี ID หรือ Action ไม่ถูกต้อง ให้กลับไป Dashboard
    header("Location: dashboard_tech.php");
    exit();
}