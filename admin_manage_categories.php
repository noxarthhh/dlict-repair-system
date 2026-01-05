<?php
// admin_manage_categories.php - จัดการประเภทงานซ่อม
session_start();
include 'db_connect.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: home.php"); exit();
}

// 1. สร้างตารางถ้ายังไม่มี
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS repair_types (
        type_id INT AUTO_INCREMENT PRIMARY KEY,
        type_name VARCHAR(100) NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (PDOException $e) {}

// 2. ลบข้อมูล
if (isset($_GET['delete_id'])) {
    $stmt = $pdo->prepare("DELETE FROM repair_types WHERE type_id = ?");
    $stmt->execute([$_GET['delete_id']]);
    echo "<script>window.location='admin_manage_categories.php';</script>";
}

// 3. เพิ่มข้อมูล
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $type_name = trim($_POST['type_name']);
    if (!empty($type_name)) {
        $stmt = $pdo->prepare("INSERT INTO repair_types (type_name) VALUES (?)");
        $stmt->execute([$type_name]);
    }
}

$types = $pdo->query("SELECT * FROM repair_types ORDER BY type_id DESC")->fetchAll();
include 'includes/header.php';
?>

<style>
    body { background-color: #f8fafc; overflow: hidden; }
    .manage-wrapper { height: calc(100vh - 80px); display: flex; gap: 20px; padding: 20px; }
    .card-box { background: white; border-radius: 15px; padding: 25px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); }
    .left-box { flex: 1; }
    .right-box { flex: 1.5; overflow-y: auto; }
    
    .btn-add { width: 100%; padding: 12px; background: #4f46e5; color: white; border: none; border-radius: 10px; cursor: pointer; }
    .btn-del { padding: 5px 10px; background: #fee2e2; color: #dc2626; border: none; border-radius: 5px; cursor: pointer; }
    
    table { width: 100%; border-collapse: collapse; }
    td, th { padding: 12px; border-bottom: 1px solid #eee; text-align: left; }
</style>

<div class="manage-wrapper">
    <div class="card-box left-box">
        <h3><i class="fa-solid fa-list"></i> เพิ่มประเภทงานซ่อม</h3>
        <p style="color:#64748b; margin-bottom:20px;">เช่น คอมพิวเตอร์, ปริ้นเตอร์, เครือข่าย</p>
        
        <form method="POST">
            <div style="margin-bottom:15px;">
                <label style="display:block; margin-bottom:5px; font-weight:600;">ชื่อประเภทงาน</label>
                <input type="text" name="type_name" required class="form-control" 
                       style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px;"
                       placeholder="ระบุชื่อประเภท...">
            </div>
            <button type="submit" class="btn-add">บันทึก</button>
        </form>
    </div>

    <div class="card-box right-box">
        <h3>รายการทั้งหมด (<?php echo count($types); ?>)</h3>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>ชื่อประเภท</th>
                    <th>จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($types as $row): ?>
                <tr>
                    <td><?php echo $row['type_id']; ?></td>
                    <td><?php echo htmlspecialchars($row['type_name']); ?></td>
                    <td>
                        <a href="?delete_id=<?php echo $row['type_id']; ?>" onclick="return confirm('ยืนยันการลบ?');">
                            <button class="btn-del"><i class="fa-solid fa-trash"></i></button>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>