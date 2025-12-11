<?php
session_start();
include 'db_connect.php';

// Access Control
if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: home.php");
    exit();
}

$page_title = 'เพิ่มผู้ใช้งานใหม่';
$error = '';
$success = '';

// Process Form Submission (Same as your previous logic)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $full_name = trim($_POST['full_name'] ?? '');
    $group_name = trim($_POST['group_name'] ?? '');
    $position = trim($_POST['position'] ?? '');
    $role = $_POST['role'] ?? 'requester';

    if (empty($username) || empty($password) || empty($full_name)) {
        $error = "กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วน";
    } elseif (strlen($password) < 6) {
        $error = "รหัสผ่านต้องมีความยาวอย่างน้อย 6 ตัวอักษร";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM staffs WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetchColumn() > 0) {
                $error = "Username นี้ถูกใช้งานแล้ว";
            } else {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO staffs (username, password_hash, full_name, group_name, position, role) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$username, $password_hash, $full_name, $group_name, $position, $role]);
                $success = "เพิ่มผู้ใช้งานใหม่เรียบร้อย: " . htmlspecialchars($full_name);
            }
        } catch (PDOException $e) {
            $error = "เกิดข้อผิดพลาดในการบันทึกข้อมูล: " . $e->getMessage();
        }
    }
}

include 'includes/header.php';
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    /* Global No Scroll */
    body { overflow: hidden; background: linear-gradient(-45deg, #eef2ff, #f0fdf4, #fff7ed, #fdf4ff); background-size: 400% 400%; animation: gradientBG 15s ease infinite; }
    
    .add-user-wrapper {
        height: calc(100vh - 80px);
        display: flex; justify-content: center; align-items: center; padding: 20px;
        animation: zoomIn 0.6s;
    }

    /* Premium Form Card */
    .form-card-wide {
        width: 100%; max-width: 1000px; height: auto;
        background: rgba(255,255,255,0.9); backdrop-filter: blur(15px);
        border-radius: 20px; border: 1px solid rgba(255,255,255,0.7);
        box-shadow: 0 15px 40px rgba(0,0,0,0.1);
        padding: 40px;
    }
    
    .form-title { text-align: center; margin-bottom: 30px; }
    .form-title h1 { 
        font-size: 2rem; font-weight: 800; margin: 0;
        color: var(--primary); display: flex; align-items: center; justify-content: center; gap: 10px;
    }
    .form-title p { color: #64748b; margin-top: 5px; }

    /* Layout Grid */
    .form-grid {
        display: grid; grid-template-columns: 1fr 1fr; gap: 40px;
    }
    
    .section-head { 
        font-weight: 700; color: #4f46e5; margin-bottom: 20px;
        border-bottom: 1px dashed #e2e8f0; padding-bottom: 10px;
        display: flex; align-items: center; gap: 8px;
    }

    /* Input Styling (Same as New Request) */
    .input-grp { margin-bottom: 20px; }
    .input-grp label { font-weight: 700; color: #475569; margin-bottom: 5px; display: block; font-size: 0.9rem; }
    .input-wrapper { position: relative; }
    .input-wrapper i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #94a3b8; transition: 0.3s; }
    .form-control {
        width: 100%; padding: 12px 12px 12px 40px; border: 2px solid #e2e8f0; border-radius: 10px;
        background: #f8fafc; transition: 0.3s; font-size: 0.95rem;
    }
    .form-control:focus { border-color: #4f46e5; box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1); outline: none; }
    
    .btn-submit {
        width: 100%; padding: 14px; background: linear-gradient(135deg, #4f46e5, #3b82f6);
        color: white; border: none; border-radius: 50px; font-weight: 700; font-size: 1.1rem;
        box-shadow: 0 10px 20px rgba(79, 70, 229, 0.3); transition: 0.3s;
    }
    .btn-submit:hover { transform: translateY(-3px); }

    @media (max-width: 900px) {
        body { overflow: auto; }
        .add-user-wrapper { height: auto; display: block; padding-top: 30px; }
        .form-card-wide { padding: 25px; }
        .form-grid { grid-template-columns: 1fr; gap: 20px; }
    }
</style>

<div class="add-user-wrapper">
    <div class="form-card-wide">
        
        <div class="form-title">
            <h1><i class="fa-solid fa-user-plus"></i> เพิ่มผู้ใช้งานใหม่</h1>
            <p>สร้างบัญชีสำหรับเจ้าหน้าที่หรือผู้แจ้งซ่อม</p>
        </div>

        <form method="POST" action="admin_add_user.php">
            <div class="form-grid">
                
                <div>
                    <div class="section-head"><i class="fa-solid fa-lock"></i> ข้อมูลเข้าสู่ระบบ (Login)</div>
                    <div class="input-grp">
                        <label for="username">Username / Email *</label>
                        <div class="input-wrapper">
                            <input type="text" name="username" id="username" class="form-control" required placeholder="ตั้งชื่อผู้ใช้/อีเมล">
                            <i class="fa-solid fa-at"></i>
                        </div>
                    </div>
                    <div class="input-grp">
                        <label for="password">Password *</label>
                        <div class="input-wrapper">
                            <input type="password" name="password" id="password" class="form-control" required placeholder="ตั้งรหัสผ่านเริ่มต้น (อย่างน้อย 6 ตัวอักษร)">
                            <i class="fa-solid fa-key"></i>
                        </div>
                    </div>
                    <div class="input-grp">
                        <label for="role">สิทธิ์การใช้งาน *</label>
                        <div class="input-wrapper">
                            <i class="fa-solid fa-user-tag"></i>
                            <select name="role" id="role" class="form-control" style="cursor:pointer;">
                                <option value="requester">Requester (ผู้แจ้งซ่อมทั่วไป)</option>
                                <option value="technician">Technician (ช่างซ่อม)</option>
                                <option value="admin">Admin (ผู้ดูแลระบบ)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div>
                    <div class="section-head"><i class="fa-solid fa-id-card"></i> ข้อมูลส่วนตัว (Personal)</div>
                    <div class="input-grp">
                        <label for="full_name">ชื่อ-นามสกุล *</label>
                        <div class="input-wrapper">
                            <input type="text" name="full_name" id="full_name" class="form-control" required placeholder="เช่น นายสมชาย ใจดี">
                            <i class="fa-solid fa-signature"></i>
                        </div>
                    </div>
                    <div class="input-grp">
                        <label for="group_name">กลุ่ม/ฝ่ายงาน</label>
                        <div class="input-wrapper">
                            <input type="text" name="group_name" id="group_name" class="form-control" placeholder="เช่น ฝ่ายบริหารงานบุคคล">
                            <i class="fa-solid fa-building"></i>
                        </div>
                    </div>
                    <div class="input-grp">
                        <label for="position">ตำแหน่ง</label>
                        <div class="input-wrapper">
                            <input type="text" name="position" id="position" class="form-control" placeholder="เช่น นักวิชาการคอมพิวเตอร์">
                            <i class="fa-solid fa-briefcase"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div style="margin-top: 30px; text-align: center;">
                <button type="submit" class="btn-submit animate__animated animate__pulse" data-wow-iteration="infinite">
                    <i class="fa-solid fa-save"></i> บันทึกข้อมูลผู้ใช้
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // SweetAlert สำหรับแจ้งเตือนผลลัพธ์
    <?php if ($success): ?>
        Swal.fire({ icon: 'success', title: 'สำเร็จ!', text: '<?php echo htmlspecialchars($success); ?>', confirmButtonColor: '#10b981', customClass: { popup: 'swal-custom-font' } });
    <?php elseif ($error): ?>
        Swal.fire({ icon: 'error', title: 'ผิดพลาด!', text: '<?php echo htmlspecialchars($error); ?>', confirmButtonColor: '#ef4444', customClass: { popup: 'swal-custom-font' } });
    <?php endif; ?>
</script>

<?php 
// include 'includes/footer.php'; 
?>