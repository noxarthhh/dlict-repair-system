<?php
// admin_add_user.php - เพิ่ม/ลบ/แก้ไข ผู้ใช้งาน (พร้อมคำนำหน้า)
session_start();
include 'db_connect.php';

// Access Control
if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: home.php");
    exit();
}

$page_title = 'จัดการผู้ใช้งาน';
$error = '';
$success = '';

// --- 1. จัดการลบผู้ใช้ ---
if (isset($_GET['delete_id'])) {
    $del_id = $_GET['delete_id'];
    if ($del_id == $_SESSION['staff_id']) {
        $error = "คุณไม่สามารถลบบัญชีของตัวเองได้";
    } else {
        try {
            $stmt = $pdo->prepare("DELETE FROM staffs WHERE staff_id = ?");
            if ($stmt->execute([$del_id])) {
                $success = "ลบผู้ใช้งานเรียบร้อยแล้ว";
                echo "<script>setTimeout(() => { window.location='admin_add_user.php'; }, 1500);</script>";
            }
        } catch (PDOException $e) {
            $error = "เกิดข้อผิดพลาดในการลบ: " . $e->getMessage();
        }
    }
}

// --- 2. จัดการบันทึกข้อมูล ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $staff_id = $_POST['staff_id'] ?? '';
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $email    = trim($_POST['email'] ?? '');
    
    // รับค่าคำนำหน้าและชื่อ
    $title           = $_POST['title'] ?? '';
    $full_name_input = trim($_POST['full_name'] ?? '');
    
    // รวมคำนำหน้า + ชื่อ (ถ้ามีการเลือกคำนำหน้า)
    $full_name = ($title) ? $title . $full_name_input : $full_name_input;

    $group_name = trim($_POST['group_name'] ?? '');
    $position   = trim($_POST['position'] ?? '');
    $role       = $_POST['role'] ?? 'requester';

    if (empty($username) || empty($full_name_input)) {
        $error = "กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วน";
    } elseif (empty($staff_id) && strlen($password) < 6) {
        $error = "รหัสผ่านต้องมีความยาวอย่างน้อย 6 ตัวอักษร";
    } else {
        try {
            // เช็คซ้ำ
            $sql_check = "SELECT COUNT(*) FROM staffs WHERE (username = ? OR email = ?)";
            $params_check = [$username, $email];
            if (!empty($staff_id)) {
                $sql_check .= " AND staff_id != ?";
                $params_check[] = $staff_id;
            }

            $stmt = $pdo->prepare($sql_check);
            $stmt->execute($params_check);

            if ($stmt->fetchColumn() > 0) {
                $error = "Username หรือ Email นี้ถูกใช้งานแล้ว";
            } else {
                if (!empty($staff_id)) {
                    // Update
                    $sql = "UPDATE staffs SET username=?, full_name=?, email=?, group_name=?, position=?, role=?";
                    $params = [$username, $full_name, $email, $group_name, $position, $role];
                    if (!empty($password)) {
                        $sql .= ", password_hash=?";
                        $params[] = password_hash($password, PASSWORD_DEFAULT);
                    }
                    $sql .= " WHERE staff_id=?";
                    $params[] = $staff_id;
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                    $success = "แก้ไขข้อมูลเรียบร้อย: " . htmlspecialchars($full_name);
                } else {
                    // Insert
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO staffs (username, password_hash, full_name, email, group_name, position, role) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$username, $password_hash, $full_name, $email, $group_name, $position, $role]);
                    $success = "เพิ่มผู้ใช้งานใหม่เรียบร้อย: " . htmlspecialchars($full_name);
                }
            }
        } catch (PDOException $e) {
            $error = "เกิดข้อผิดพลาด: " . $e->getMessage();
        }
    }
}

// ดึงข้อมูล
$stmt = $pdo->query("SELECT * FROM staffs ORDER BY staff_id DESC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    body { overflow: hidden; background-color: #f1f5f9; font-family: 'Sarabun', sans-serif; }
    .admin-wrapper { height: calc(100vh - 80px); display: flex; gap: 20px; padding: 20px; }
    .form-panel { flex: 1; background: white; border-radius: 20px; padding: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); overflow-y: auto; position: relative; transition: all 0.3s; }
    .form-panel.editing { border: 2px solid #f59e0b; }
    .list-panel { flex: 1.5; background: white; border-radius: 20px; padding: 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); display: flex; flex-direction: column; }
    .input-grp { margin-bottom: 15px; }
    .input-grp label { font-weight: 600; font-size: 0.9rem; color: #475569; margin-bottom: 5px; display: block; }
    .form-control { width: 100%; padding: 10px 15px; border: 1px solid #cbd5e1; border-radius: 10px; transition: 0.3s; font-size: 0.95rem; }
    .form-control:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); outline: none; }
    .btn-save { width: 100%; padding: 12px; background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; border: none; border-radius: 10px; font-weight: 700; cursor: pointer; transition: 0.3s; }
    .btn-save:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(37, 99, 235, 0.3); }
    .btn-cancel { width: 100%; padding: 10px; background: #e2e8f0; color: #475569; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; margin-top: 10px; display: none; }
    .btn-cancel:hover { background: #cbd5e1; }
    .table-container { flex: 1; overflow-y: auto; margin-top: 15px; border-radius: 10px; border: 1px solid #e2e8f0; }
    table { width: 100%; border-collapse: collapse; }
    th { position: sticky; top: 0; background: #f8fafc; padding: 15px; text-align: left; color: #475569; font-weight: 700; z-index: 1; }
    td { padding: 15px; border-bottom: 1px solid #f1f5f9; color: #334155; font-size: 0.95rem; }
    tr:hover { background-color: #f8fafc; }
    .badge-role { padding: 4px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
    .bg-admin { background: #fee2e2; color: #dc2626; }
    .bg-tech { background: #ffedd5; color: #ea580c; }
    .bg-req { background: #dbeafe; color: #2563eb; }
    .action-btn { padding: 6px 12px; border: none; border-radius: 8px; cursor: pointer; transition: 0.2s; font-size: 0.85rem; margin-right: 5px; }
    .btn-edit { background: #fef3c7; color: #d97706; }
    .btn-edit:hover { background: #d97706; color: white; }
    .btn-del { background: #fee2e2; color: #dc2626; }
    .btn-del:hover { background: #dc2626; color: white; }
    @media (max-width: 1000px) { .admin-wrapper { flex-direction: column; overflow-y: auto; height: auto; } .form-panel, .list-panel { flex: none; height: auto; } }
</style>

<div class="admin-wrapper">
    <div class="form-panel animate__animated animate__fadeInLeft" id="formPanel">
        <h3 id="formTitle" style="color:#1e293b; margin-bottom:20px; font-weight:800;">
            <i class="fa-solid fa-user-plus text-primary"></i> เพิ่มผู้ใช้ใหม่
        </h3>
        
        <form method="POST" id="userForm">
            <input type="hidden" name="staff_id" id="staff_id">

            <div class="input-grp">
                <label>Username (ชื่อเข้าใช้) *</label>
                <input type="text" name="username" id="username" class="form-control" required placeholder="User01">
            </div>
            <div class="input-grp">
                <label>Password (รหัสผ่าน)</label>
                <input type="password" name="password" id="password" class="form-control" placeholder="ขั้นต่ำ 6 ตัวอักษร">
                <small id="pwdHint" style="color:#94a3b8; font-size:0.8rem; display:none;">* กรอกเฉพาะเมื่อต้องการเปลี่ยนรหัสผ่านใหม่</small>
            </div>
            <div class="input-grp">
                <label>Email (อีเมล) *</label>
                <input type="email" name="email" id="email" class="form-control" required placeholder="email@example.com">
            </div>
            
            <div class="input-grp">
                <label>ชื่อ-นามสกุล *</label>
                <div style="display:flex; gap:10px;">
                    <select name="title" id="title" class="form-control" style="width: 120px; flex-shrink:0;">
                        <option value="">คำนำหน้า</option>
                        <option value="นาย">นาย</option>
                        <option value="นาง">นาง</option>
                        <option value="นางสาว">นางสาว</option>
                        <option value="ดร.">ดร.</option>
                    </select>
                    <input type="text" name="full_name" id="full_name" class="form-control" required placeholder="สมชาย ใจดี">
                </div>
            </div>

            <div class="input-grp">
                <label>ตำแหน่ง / สังกัด</label>
                <div style="display:flex; gap:10px;">
                    <input type="text" name="position" id="position" class="form-control" placeholder="ตำแหน่ง">
                    <input type="text" name="group_name" id="group_name" class="form-control" placeholder="สังกัด">
                </div>
            </div>
            <div class="input-grp">
                <label>สิทธิ์การใช้งาน</label>
                <select name="role" id="role" class="form-control">
                    <option value="requester">ผู้แจ้งซ่อม (Requester)</option>
                    <option value="technician">ช่างเทคนิค (Technician)</option>
                    <option value="admin">ผู้ดูแลระบบ (Admin)</option>
                </select>
            </div>
            
            <button type="submit" class="btn-save" id="saveBtn"><i class="fa-solid fa-save"></i> บันทึกข้อมูล</button>
            <button type="button" class="btn-cancel" id="cancelBtn" onclick="resetForm()">ยกเลิกการแก้ไข</button>
        </form>
    </div>

    <div class="list-panel animate__animated animate__fadeInRight">
        <h3 style="color:#1e293b; margin-bottom:15px; font-weight:800;">
            <i class="fa-solid fa-users text-success"></i> รายชื่อผู้ใช้ทั้งหมด (<?php echo count($users); ?>)
        </h3>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ชื่อ-นามสกุล</th>
                        <th>Username/Email</th>
                        <th>สิทธิ์</th>
                        <th style="text-align:center;">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($users as $u): ?>
                    <tr>
                        <td>
                            <div style="font-weight:600;"><?php echo htmlspecialchars($u['full_name']); ?></div>
                            <div style="font-size:0.85rem; color:#94a3b8;"><?php echo htmlspecialchars($u['position']); ?></div>
                        </td>
                        <td>
                            <div><?php echo htmlspecialchars($u['username']); ?></div>
                            <div style="font-size:0.85rem; color:#94a3b8;"><?php echo htmlspecialchars($u['email']); ?></div>
                        </td>
                        <td>
                            <?php 
                                if($u['role']=='admin') echo '<span class="badge-role bg-admin">Admin</span>';
                                elseif($u['role']=='technician') echo '<span class="badge-role bg-tech">Tech</span>';
                                else echo '<span class="badge-role bg-req">User</span>';
                            ?>
                        </td>
                        <td style="text-align:center; white-space:nowrap;">
                            <button onclick='editUser(<?php echo json_encode($u); ?>)' class="action-btn btn-edit"><i class="fa-solid fa-pen-to-square"></i> แก้ไข</button>
                            <button onclick="confirmDelete(<?php echo $u['staff_id']; ?>)" class="action-btn btn-del"><i class="fa-solid fa-trash"></i> ลบ</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    function editUser(user) {
        document.getElementById('staff_id').value = user.staff_id;
        document.getElementById('username').value = user.username;
        document.getElementById('email').value = user.email;
        document.getElementById('position').value = user.position;
        document.getElementById('group_name').value = user.group_name;
        document.getElementById('role').value = user.role;
        
        // ✅ ระบบแยกคำนำหน้าอัตโนมัติ
        let fullName = user.full_name;
        let title = "";
        
        // เช็คคำนำหน้า
        if(fullName.startsWith("นางสาว")) { title = "นางสาว"; fullName = fullName.substring(6); }
        else if(fullName.startsWith("นาง")) { title = "นาง"; fullName = fullName.substring(3); }
        else if(fullName.startsWith("นาย")) { title = "นาย"; fullName = fullName.substring(3); }
        else if(fullName.startsWith("ดร.")) { title = "ดร."; fullName = fullName.substring(3); }

        document.getElementById('title').value = title;
        document.getElementById('full_name').value = fullName.trim(); // ตัดช่องว่างส่วนเกิน

        // เคลียร์รหัสผ่าน
        document.getElementById('password').value = '';
        document.getElementById('password').required = false;
        document.getElementById('pwdHint').style.display = 'block';

        // ปรับ UI
        document.getElementById('formTitle').innerHTML = '<i class="fa-solid fa-pen-to-square text-warning"></i> แก้ไขข้อมูลผู้ใช้';
        document.getElementById('saveBtn').innerHTML = '<i class="fa-solid fa-save"></i> บันทึกการแก้ไข';
        document.getElementById('cancelBtn').style.display = 'block';
        document.getElementById('formPanel').classList.add('editing');

        if(window.innerWidth < 1000) document.getElementById('formPanel').scrollIntoView({behavior: 'smooth'});
    }

    function resetForm() {
        document.getElementById('userForm').reset();
        document.getElementById('staff_id').value = '';
        document.getElementById('password').required = true;
        document.getElementById('pwdHint').style.display = 'none';
        document.getElementById('formTitle').innerHTML = '<i class="fa-solid fa-user-plus text-primary"></i> เพิ่มผู้ใช้ใหม่';
        document.getElementById('saveBtn').innerHTML = '<i class="fa-solid fa-save"></i> บันทึกข้อมูล';
        document.getElementById('cancelBtn').style.display = 'none';
        document.getElementById('formPanel').classList.remove('editing');
    }

    function confirmDelete(id) {
        Swal.fire({
            title: 'ยืนยันการลบ?', text: "ข้อมูลผู้ใช้นี้จะหายไปถาวร!", icon: 'warning',
            showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#3085d6',
            confirmButtonText: 'ลบข้อมูล', cancelButtonText: 'ยกเลิก', customClass: { popup: 'swal-custom-font' }
        }).then((result) => { if (result.isConfirmed) { window.location.href = 'admin_add_user.php?delete_id=' + id; } })
    }

    <?php if ($success): ?>
        Swal.fire({ icon: 'success', title: 'สำเร็จ!', text: '<?php echo $success; ?>', timer: 1500, showConfirmButton: false, customClass: { popup: 'swal-custom-font' } });
    <?php elseif ($error): ?>
        Swal.fire({ icon: 'error', title: 'ผิดพลาด!', text: '<?php echo $error; ?>', customClass: { popup: 'swal-custom-font' } });
    <?php endif; ?>
</script>

<?php include 'includes/footer.php'; ?>