<?php
session_start();
include 'db_connect.php';

// 1. ⛔ Access Control: เฉพาะ Admin เท่านั้น
if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: home.php");
    exit();
}

$page_title = 'เพิ่มผู้ใช้งานใหม่';
$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 2. รับค่าและทำความสะอาดข้อมูล
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $full_name = trim($_POST['full_name']);
    $group_name = trim($_POST['group_name']);
    $position = trim($_POST['position']);
    $role = $_POST['role'];

    // ตรวจสอบข้อมูลเบื้องต้น
    if (empty($username) || empty($password) || empty($full_name)) {
        $error_msg = "กรุณากรอกข้อมูลที่จำเป็น (*) ให้ครบถ้วน";
    } else {
        try {
            // 3. ตรวจสอบว่า Username ซ้ำไหม
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM staffs WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetchColumn() > 0) {
                $error_msg = "ชื่อผู้ใช้งานนี้ (Username) มีอยู่ในระบบแล้ว กรุณาเปลี่ยนใหม่";
            } else {
                // 4. 🔑 สร้าง Hash รหัสผ่าน (สำคัญมาก!)
                $password_hash = password_hash($password, PASSWORD_DEFAULT);

                // 5. บันทึกลงฐานข้อมูล
                $sql = "INSERT INTO staffs (username, password_hash, full_name, group_name, position, role) 
                        VALUES (:username, :pass, :fname, :gname, :pos, :role)";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    'username' => $username,
                    'pass' => $password_hash,
                    'fname' => $full_name,
                    'gname' => $group_name,
                    'pos' => $position,
                    'role' => $role
                ]);

                $success_msg = "✅ เพิ่มผู้ใช้งาน \"$full_name\" เรียบร้อยแล้ว!";
            }
        } catch (PDOException $e) {
            $error_msg = "เกิดข้อผิดพลาด: " . $e->getMessage();
        }
    }
}

include 'includes/header.php';
?>

<style>
    /* บังคับให้ Container ไม่เกินหน้าจอ */
    .dashboard-wrapper {
        max-width: 900px;
        margin: 0 auto;
        /* ใช้ height 100vh ลบด้วย header/footer เพื่อให้อยู่ในหน้าเดียว */
        min-height: calc(100vh - 150px); 
        display: flex;
        flex-direction: column;
        justify-content: center;
    }
    
    /* ปรับ form-card ให้กระชับ */
    .form-card {
        padding: 20px 30px; /* ลด padding */
        margin: 0 auto;
        width: 100%;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    }

    h1 { font-size: 1.8rem; margin-bottom: 5px; }
    h3 { font-size: 1.1rem; margin-bottom: 15px; margin-top: 15px; }
    p { margin-bottom: 15px; }

    /* ปรับ input ให้เล็กลงนิดนึง */
    input, select { padding: 8px 12px; font-size: 0.95rem; }
    .form-group { margin-bottom: 15px; } /* ลดระยะห่าง */
    
    /* Responsive ให้เลื่อนได้ถ้าจอเล็กจริงๆ */
    @media (max-height: 800px) {
        .dashboard-wrapper { justify-content: flex-start; padding-top: 20px; }
    }
</style>

<div class="dashboard-wrapper">
    <div style="text-align: center; margin-bottom: 10px;">
        <h1>👤 เพิ่มผู้ใช้งานใหม่</h1>
        <p class="small-muted">สร้างบัญชีผู้ใช้งานสำหรับเจ้าหน้าที่ หรือผู้แจ้งซ่อม</p>
    </div>

    <?php if ($success_msg): ?>
        <div class="alert alert-success"><?php echo $success_msg; ?></div>
    <?php endif; ?>
    <?php if ($error_msg): ?>
        <div class="alert alert-danger"><?php echo $error_msg; ?></div>
    <?php endif; ?>

    <div class="card form-card">
        <form method="POST" action="admin_add_user.php">
            
            <div class="two-column-layout" style="margin-bottom: 0; gap: 20px;">
                <div>
                    <h3 style="color: var(--primary); border-bottom: 1px solid #eee; padding-bottom: 5px;">🔐 ข้อมูลเข้าระบบ</h3>
                    <div class="form-group">
                        <label>Username / Email <span style="color:red">*</span></label>
                        <input type="text" name="username" required placeholder="เช่น user01" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label>รหัสผ่าน <span style="color:red">*</span></label>
                        <input type="text" name="password" required placeholder="ตั้งรหัสผ่าน">
                    </div>
                    <div class="form-group">
                        <label>สิทธิ์การใช้งาน (Role) <span style="color:red">*</span></label>
                        <select name="role" required>
                            <option value="requester">👤 Requester (ผู้แจ้ง)</option>
                            <option value="technician">🛠️ Technician (ช่าง)</option>
                            <option value="admin">👑 Admin (ผู้ดูแล)</option>
                        </select>
                    </div>
                </div>

                <div>
                    <h3 style="color: var(--info); border-bottom: 1px solid #eee; padding-bottom: 5px;">📝 ข้อมูลส่วนตัว</h3>
                    <div class="form-group">
                        <label>ชื่อ-นามสกุล <span style="color:red">*</span></label>
                        <input type="text" name="full_name" required placeholder="เช่น นายสมชาย ใจดี">
                    </div>
                    <div class="form-group">
                        <label>กลุ่ม/ฝ่าย</label>
                        <input type="text" name="group_name" placeholder="เช่น บริหารงานบุคคล">
                    </div>
                    <div class="form-group">
                        <label>ตำแหน่ง</label>
                        <input type="text" name="position" placeholder="เช่น นักวิชาการ">
                    </div>
                </div>
            </div>

            <div style="text-align: center; margin-top: 20px; padding-top: 10px; border-top: 1px dashed #eee;">
                <button type="submit" class="btn-primary" style="width: 200px;">
                    💾 บันทึกข้อมูล
                </button>
            </div>

        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>