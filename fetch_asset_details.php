<?php
// เชื่อมต่อฐานข้อมูล
include 'db_connect.php'; 

// ตรวจสอบว่ามีการส่ง asset_id มาหรือไม่
if (isset($_GET['asset_id'])) {
    $asset_id = $_GET['asset_id'];

    // ดึงข้อมูลครุภัณฑ์และชื่อผู้รับผิดชอบ (JOIN 2 ตาราง)
    $sql = "SELECT 
                a.location_group,
                s.full_name AS responsible_person, 
                s.position 
            FROM assets a
            JOIN staffs s ON a.responsible_staff_id = s.staff_id
            WHERE a.asset_id = ?";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$asset_id]);
    $details = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // ตั้งค่า Header ให้เป็น JSON และส่งข้อมูลกลับไป
    header('Content-Type: application/json');
    if ($details) {
        echo json_encode($details);
    } else {
        // ส่งข้อมูลว่างถ้าไม่พบ
        echo json_encode(['responsible_person' => 'ไม่พบข้อมูล', 'position' => 'ไม่พบข้อมูล', 'location_group' => 'ไม่พบข้อมูล']);
    }
} else {
    // ส่งข้อผิดพลาดถ้าไม่มี ID
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Asset ID not provided']);
}
?>