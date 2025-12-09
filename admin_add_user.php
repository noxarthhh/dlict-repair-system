<?php
session_start();
include 'db_connect.php';

// 1. ⛔ Access Control
if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: dashboard_tech.php");
    exit();
}

$page_title = 'เพิ่มผู้ใช้งานใหม่';
$message = '';
$msg_type = '';

// 2. Handle Form Submit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $full_name = trim($_POST['full_name']);
    $group_name = trim($_POST['group_name']);
    $position = trim($_POST['position']);
    $role = $_POST['role'];

    if (empty($username) || empty($password) || empty($full_name)) {
        $message = "กรุณากรอกข้อมูลที่จำเป็น (*) ให้ครบถ้วน"; $msg_type = 'danger';
    } else {
        try {
            // เช็ค Username ซ้ำ
            $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM staffs WHERE username = ?");
            $stmt_check->execute([$username]);
            if ($stmt_check->fetchColumn() > 0) {
                $message = "Username นี้มีผู้ใช้งานแล้ว"; $msg_type = 'warning';
            } else {
                // บันทึก
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $sql = "INSERT INTO staffs (username, password, full_name, group_name, position, role) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$username, $hashed_password, $full_name, $group_name, $position, $role]);
                $message = "เพิ่มผู้ใช้งานสำเร็จ!"; $msg_type = 'success';
            }
        } catch (PDOException $e) {
            $message = "เกิดข้อผิดพลาด: " . $e->getMessage(); $msg_type = 'danger';
        }
    }
}

include 'includes/header.php';
?>

<style>
    /* Layout & Background */
    body { overflow: hidden; background-color: #f0f4f8; }
    .form-wrapper {
        height: calc(100vh - 80px);
        display: flex; justify-content: center; align-items: center;
        padding: 20px;
        position: relative; overflow: hidden;
    }
    /* Background Decoration */
    .bg-deco { position: absolute; border-radius: 50%; filter: blur(60px); opacity: 0.15; z-index: -1; }
    .d1 { top: -10%; left: -10%; width: 40%; height: 40%; background: var(--primary); animation: float 10s infinite; }
    .d2 { bottom: -10%; right: -10%; width: 50%; height: 50%; background: var(--info); animation: float 15s infinite reverse; }
    @keyframes float { 0%,100% { transform: translate(0,0); } 50% { transform: translate(30px, 50px); } }

    /* Premium Form Card */
    .premium-card {
        width: 100%; max-width: 900px;
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(20px);
        border-radius: 24px;
        padding: 40px;
        box-shadow: 0 20px 50px -10px rgba(0,0,0,0.15);
        border: 1px solid rgba(255,255,255,0.5);
        animation: slideUp 0.6s ease-out;
    }
    @keyframes slideUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }

    .form-title { text-align: center; margin-bottom: 35px; }
    .form-title h1 { margin: 0; font-size: 2rem; font-weight: 800; color: var(--text-main); }
    .form-title p { color: var(--text-muted); margin-top: 5px; }

    /* Form Grid */
    .form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; }
    .section-head { font-weight: 700; color: var(--primary); margin-bottom: 15px; display: flex; align-items: center; gap: 8px; font-size: 1.1rem; }
    
    /* Input Styling */
    .form-group label { font-weight: 600; margin-bottom: 8px; color: var(--text-main); display: block; }
    .input-group { position: relative; }
    .input-icon { position: absolute; top: 50%; left: 15px; transform: translateY(-50%); color: var(--text-muted); z-index: 1; }
    .form-control-pl { padding-left: 45px !important; transition: 0.3s; border: 2px solid var(--border); background: #f8fafc; }
    .form-control-pl:focus { border-color: var(--primary); background: #fff; box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1); }

    /* Button */
    .btn-premium {
        width: 100%; padding: 15px;
        background: linear-gradient(135deg, var(--primary), var(--info));
        color: white; font-weight: 700; font-size: 1.1rem;
        border-radius: 50px; border: none; cursor: pointer;
        transition: 0.3s; box-shadow: 0 10px 20px -5px rgba(59, 130, 246, 0.4);
    }
    .btn-premium:hover { transform: translateY(-3px); box-shadow: 0 15px 30px -5px rgba(59, 130, 246, 0.5); filter: brightness(1.1); }

    @media (max-width: 768px) { .form-grid-2 { grid-template-columns: 1fr; gap: 15px; } .premium-card { padding: 25px; } }
</style>

<div class="form-wrapper">
    <div class="bg-deco d1"></div>
    <div class="bg-deco d2"></div>

    <div class="premium-card">
        <div class="form-title">
            <h1><i class="fa-solid fa-user-plus" style="color:var(--primary);"></i> เพิ่มผู้ใช้งานใหม่</h1>
            <p>สร้างบัญชีสำหรับเจ้าหน้าที่หรือช่างเทคนิค</p>
        </div>

        <?php if ($message): ?>
            <script>
                Swal.fire({
                    icon: '<?php echo $msg_type == "danger" ? "error" : $msg_type; ?>',
                    title: '<?php echo $msg_type == "success" ? "สำเร็จ!" : "แจ้งเตือน"; ?>',
                    text: '<?php echo $message; ?>',
                    confirmButtonText: 'ตกลง',
                    customClass: { popup: 'swal-custom-font' }
                });
            </script>
        <?php endif; ?>

        <form method="POST" action="admin_add_user.php">
            <div class="form-grid-2">
                <div>
                    <div class="section-head"><i class="fa-solid fa-shield-halved"></i> ข้อมูลเข้าระบบ</div>
                    <div class="form-group">
                        <label>Username *</label>
                        <div class="input-group">
                            <i class="fa-solid fa-user input-icon"></i>
                            <input type="text" name="username" class="form-control-pl" required placeholder="ระบุชื่อผู้ใช้">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Password *</label>
                        <div class="input-group">
                            <i class="fa-solid fa-lock input-icon"></i>
                            <input type="password" name="password" class="form-control-pl" required placeholder="ระบุรหัสผ่าน">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>สิทธิ์การใช้งาน *</label>
                        <div class="input-group">
                            <i class="fa-solid fa-user-tag input-icon" style="z-index:1;"></i>
                            <select name="role" class="form-control-pl" style="-webkit-appearance: none;">
                                <option value="requester">ผู้แจ้งซ่อม (Requester)</option>
                                <option value="technician">ช่างเทคนิค (Technician)</option>
                                <option value="admin">ผู้ดูแลระบบ (Admin)</option>
                            </select>
                            <i class="fa-solid fa-chevron-down" style="position:absolute; right:15px; top:50%; transform:translateY(-50%); color:var(--text-muted);"></i>
                        </div>
                    </div>
                </div>
                
                <div style="border-left: 1px dashed var(--border); padding-left: 25px;">
                    <div class="section-head"><i class="fa-solid fa-id-card"></i> ข้อมูลทั่วไป</div>
                    <div class="form-group">
                        <label>ชื่อ-นามสกุล *</label>
                        <div class="input-group">
                            <i class="fa-solid fa-signature input-icon"></i>
                            <input type="text" name="full_name" class="form-control-pl" required placeholder="เช่น นายใจดี มีสุข">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>กลุ่ม/ฝ่ายงาน</label>
                        <div class="input-group">
                            <i class="fa-solid fa-building input-icon"></i>
                            <input type="text" name="group_name" class="form-control-pl" placeholder="เช่น ฝ่ายเทคโนโลยีสารสนเทศ">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>ตำแหน่ง</label>
                        <div class="input-group">
                            <i class="fa-solid fa-briefcase input-icon"></i>
                            <input type="text" name="position" class="form-control-pl" placeholder="เช่น นักวิชาการคอมพิวเตอร์">
                        </div>
                    </div>
                </div>
            </div>

            <div style="margin-top: 35px;">
                <button type="submit" class="btn-premium">
                    <i class="fa-solid fa-check-circle"></i> บันทึกข้อมูล
                </button>
            </div>
        </form>
    </div>
</div>
<?php include 'includes/footer.php'; ?>