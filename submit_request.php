<?php
session_start();
include 'db_connect.php'; 

if (!isset($_SESSION['logged_in']) || $_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: login.php");
    exit();
}

$requester_id = $_SESSION['staff_id'];
$asset_number = trim($_POST['asset_number'] ?? '');
$issue_details = trim($_POST['issue_details'] ?? '');
$problem_type = trim($_POST['problem_type'] ?? ''); // 🌟 รับค่าชนิดอุปกรณ์

$asset_id = null;

// 1. ค้นหา Asset ID
if (!empty($asset_number)) {
    try {
        $stmt = $pdo->prepare("SELECT asset_id FROM assets WHERE asset_number = ? LIMIT 1");
        $stmt->execute([$asset_number]);
        $found = $stmt->fetchColumn();
        if ($found) { $asset_id = $found; }
    } catch (PDOException $e) { $asset_id = null; }
}

// 2. จัดการรูปภาพ
$image_path = null;
if (isset($_FILES['repair_image']) && $_FILES['repair_image']['error'] === UPLOAD_ERR_OK) {
    $ext = strtolower(pathinfo($_FILES['repair_image']['name'], PATHINFO_EXTENSION));
    if (in_array($ext, ['jpg', 'jpeg', 'png']) && $_FILES['repair_image']['size'] <= 5*1024*1024) {
        if (!is_dir('uploads')) mkdir('uploads', 0755, true);
        $new_name = uniqid('img_', true) . '.' . $ext;
        if (move_uploaded_file($_FILES['repair_image']['tmp_name'], 'uploads/' . $new_name)) {
            $image_path = 'uploads/' . $new_name;
        }
    }
}

try {
    // 3. สร้างเลขที่
    $prefix = "FIX-" . date("ym") . "-";
    $stmt = $pdo->prepare("SELECT request_no FROM repair_requests WHERE request_no LIKE ? ORDER BY request_no DESC LIMIT 1");
    $stmt->execute([$prefix . '%']);
    $last_req = $stmt->fetchColumn();
    $number = $last_req ? (int)substr($last_req, -3) + 1 : 1;
    $request_no = $prefix . str_pad($number, 3, '0', STR_PAD_LEFT);

    // 4. บันทึกข้อมูล (เพิ่ม problem_types)
    $sql = "INSERT INTO repair_requests 
            (request_no, requester_id, asset_id, manual_asset, issue_details, image_path, status, request_date, problem_types) 
            VALUES (?, ?, ?, ?, ?, ?, 'Pending', NOW(), ?)";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $request_no, $requester_id, $asset_id, $asset_number, 
        htmlspecialchars($issue_details), $image_path, $problem_type // 🌟 บันทึกชนิดตรงนี้
    ]);

    header("Location: new_request.php?status=success&no=" . $request_no);

} catch (PDOException $e) {
    // Log internal error and redirect with a generic message
    error_log(date('[Y-m-d H:i:s] ') . "submit_request.php: " . $e->getMessage() . PHP_EOL, 3, __DIR__ . '/logs/app_errors.log');
    header("Location: new_request.php?status=error&msg=" . urlencode("ระบบเกิดข้อผิดพลาด กรุณาลองใหม่"));
}
?>