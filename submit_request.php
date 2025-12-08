<?php
session_start();
include 'db_connect.php'; 

if (!isset($_SESSION['logged_in']) || $_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: login.php");
    exit();
}

$requester_id = $_SESSION['staff_id'];
$asset_number = trim($_POST['asset_number'] ?? ''); // สิ่งที่ user พิมพ์มา
$issue_details = trim($_POST['issue_details'] ?? '');
$asset_id = null;

// 1. ลองค้นหา Asset ID ในฐานข้อมูล (ถ้าเจอ)
if (!empty($asset_number)) {
    try {
        $stmt = $pdo->prepare("SELECT asset_id FROM assets WHERE asset_number = ? LIMIT 1");
        $stmt->execute([$asset_number]);
        $found = $stmt->fetchColumn();
        if ($found) {
            $asset_id = $found;
        }
    } catch (PDOException $e) {
        $asset_id = null;
    }
}

// 2. จัดการรูปภาพ (เหมือนเดิม)
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
    // 3. สร้างเลขรันนิ่ง FIX-YYMM-XXX
    $prefix = "FIX-" . date("ym") . "-";
    $stmt = $pdo->prepare("SELECT request_no FROM repair_requests WHERE request_no LIKE ? ORDER BY request_no DESC LIMIT 1");
    $stmt->execute([$prefix . '%']);
    $last_req = $stmt->fetchColumn();
    
    $number = 1;
    if ($last_req) {
        $number = (int)substr($last_req, -3) + 1;
    }
    $request_no = $prefix . str_pad($number, 3, '0', STR_PAD_LEFT);

    // 4. บันทึกข้อมูล (เพิ่ม manual_asset)
    // เราจะบันทึก $asset_number ที่ user พิมพ์ลงไปในช่อง manual_asset ด้วย
    $sql = "INSERT INTO repair_requests 
            (request_no, requester_id, asset_id, manual_asset, issue_details, image_path, status, request_date) 
            VALUES (?, ?, ?, ?, ?, ?, 'Pending', NOW())";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $request_no, 
        $requester_id, 
        $asset_id, 
        $asset_number, /* บันทึกข้อความที่ user พิมพ์กันเหนียวไว้ตรงนี้ */
        htmlspecialchars($issue_details), 
        $image_path
    ]);

    header("Location: new_request.php?status=success&no=" . $request_no);

} catch (PDOException $e) {
    header("Location: new_request.php?status=error&msg=" . urlencode($e->getMessage()));
}
?>