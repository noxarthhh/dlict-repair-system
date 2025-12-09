<?php
if (session_status() == PHP_SESSION_NONE) session_start();

// อ่านค่าการตั้งค่าจาก Cookie (ถ้าไม่มีใช้ Default)
$theme = $_COOKIE['app_theme'] ?? 'default';
$lang_code = $_COOKIE['app_lang'] ?? 'th';

// ภาษา (Dictionary)
$lang = [
    'th' => [ 'title' => 'ระบบแจ้งซ่อม DLICT', 'home' => 'หน้าหลัก', 'dashboard' => 'รายการแจ้งซ่อม', 'report' => 'แดชบอร์ด', 'add_user' => 'เพิ่มผู้ใช้', 'new_request' => 'แจ้งซ่อม', 'tracking' => 'ติดตามงาน', 'login' => 'เข้าสู่ระบบ', 'logout' => 'ออกจากระบบ' ],
    'en' => [ 'title' => 'DLICT Repair System', 'home' => 'Home', 'dashboard' => 'Dashboard', 'report' => 'Report', 'add_user' => 'Add User', 'new_request' => 'New Request', 'tracking' => 'Tracking', 'login' => 'Login', 'logout' => 'Logout' ]
];
$L = $lang[$lang_code];

date_default_timezone_set('Asia/Bangkok');

if (!isset($page_title)) $page_title = $L['title'];

$logged_in = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === TRUE;
$user_role = $_SESSION['user_role'] ?? 'guest';
$full_name = $_SESSION['full_name'] ?? 'ผู้ใช้งาน';

// 🌟 ส่วนที่เพิ่ม: ดึงข้อมูลผู้ใช้ละเอียด (เพื่อโชว์ใน Popup)
$user_details = [];
if ($logged_in && isset($_SESSION['staff_id'])) {
    if (!isset($pdo)) include 'db_connect.php'; // เรียก connect ถ้ายังไม่มี
    try {
        $stmt_u = $pdo->prepare("SELECT username, full_name, group_name, position FROM staffs WHERE staff_id = ?");
        $stmt_u->execute([$_SESSION['staff_id']]);
        $user_details = $stmt_u->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) { }
}

// วันที่/เวลา
$thai_months = [1=>'ม.ค.',2=>'ก.พ.',3=>'มี.ค.',4=>'เม.ย.',5=>'พ.ค.',6=>'มิ.ย.',7=>'ก.ค.',8=>'ส.ค.',9=>'ก.ย.',10=>'ต.ค.',11=>'พ.ย.',12=>'ธ.ค.'];
$date_str = date('j') . ' ' . $thai_months[(int)date('n')] . ' ' . (date('Y')+543);
$time_str = date('H:i') . ' น.';
?>
<!DOCTYPE html>
<html lang="<?php echo $lang_code; ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
</head>
<body data-theme="<?php echo htmlspecialchars($theme); ?>">
    <header class="site-header">
        <div class="container header-inner">
            
            <a class="brand" href="<?php echo $logged_in ? 'home.php' : 'login.php'; ?>">
                <i class="fa-solid fa-screwdriver-wrench fa-bounce" style="--fa-animation-duration: 2s;"></i> 
                <span>DLICT Repair</span>
            </a>
            
            <nav class="main-nav desktop-nav">
                <?php if ($logged_in): ?>
                    <a href="home.php" class="nav-item"><i class="fa-solid fa-house"></i> <?php echo $L['home']; ?></a>
                    
                    <?php if ($user_role == 'technician' || $user_role == 'admin'): ?>
                        <a href="dashboard_tech.php" class="nav-item"><i class="fa-solid fa-gauge"></i> <?php echo $L['dashboard']; ?></a>
                    <?php endif; ?>

                    <?php if ($user_role == 'admin'): ?>
                        <a href="admin_report.php" class="nav-item"><i class="fa-solid fa-chart-pie"></i> <?php echo $L['report']; ?></a>
                        <a href="admin_add_user.php" class="nav-item"><i class="fa-solid fa-user-plus"></i> <?php echo $L['add_user']; ?></a> 
                    <?php endif; ?>
                    
                    <a href="new_request.php" class="nav-item"><i class="fa-solid fa-bell"></i> <?php echo $L['new_request']; ?></a>
                    
                    <?php if ($user_role == 'requester'): ?>
                        <a href="tracking.php" class="nav-item"><i class="fa-solid fa-list-check"></i> <?php echo $L['tracking']; ?></a>
                    <?php endif; ?>
                <?php endif; ?>
            </nav>

            <div class="user-panel">
                <div class="datetime-badge">
                    <span><i class="fa-regular fa-calendar"></i> <?php echo $date_str; ?></span>
                    <span class="time"><i class="fa-regular fa-clock"></i> <?php echo $time_str; ?></span>
                </div>

                <?php if ($logged_in): ?>
                    
                    <div class="user-info-box" onclick="showProfile()" title="คลิกดูข้อมูล">
                        <div class="user-avatar">
                            <i class="fa-solid fa-circle-user"></i>
                        </div>
                        
                        <div class="user-text">
                            <span class="name"><?php echo htmlspecialchars($full_name); ?></span>
                            <small class="role"><?php echo ucfirst($user_role); ?></small>
                        </div>
                        
                        <a class="btn-logout-icon animate__animated" href="#" onclick="confirmLogout(event)" title="<?php echo $L['logout']; ?>" 
                           onmouseover="this.classList.add('animate__pulse')" onmouseout="this.classList.remove('animate__pulse')">
                            <i class="fa-solid fa-power-off"></i>
                        </a>
                    </div>

                <?php else: ?>
                    <a href="login.php" class="btn-login-link"><?php echo $L['login']; ?></a>
                <?php endif; ?>
            </div>
        </div>
    </header>
    <main class="container">

    <script>
    function showProfile() {
        <?php if ($logged_in): ?>
        Swal.fire({
            title: '👤 ข้อมูลผู้ใช้งาน',
            html: `
                <div style="text-align: left; padding: 0 20px;">
                    <p style="margin-bottom: 8px;"><strong>ชื่อ-นามสกุล:</strong> <br><?php echo htmlspecialchars($user_details['full_name']); ?></p>
                    <p style="margin-bottom: 8px;"><strong>กลุ่ม/ฝ่าย:</strong> <br><?php echo htmlspecialchars($user_details['group_name']); ?></p>
                    <p style="margin-bottom: 8px;"><strong>ตำแหน่ง:</strong> <br><?php echo htmlspecialchars($user_details['position']); ?></p>
                    <p style="margin-bottom: 0;"><strong>Username:</strong> <span style="color:var(--primary); font-weight:bold;"><?php echo htmlspecialchars($user_details['username']); ?></span></p>
                </div>
            `,
            confirmButtonText: 'ปิด',
            confirmButtonColor: '#64748b',
            customClass: { popup: 'swal-custom-font' }
        });
        <?php endif; ?>
    }

    function confirmLogout(e) {
        e.stopPropagation(); e.preventDefault(); 
        Swal.fire({
            title: '<?php echo $L['logout']; ?>?', text: "ต้องการออกจากระบบใช่หรือไม่", icon: 'warning',
            showCancelButton: true, confirmButtonColor: '#ef4444', cancelButtonColor: '#6b7280',
            confirmButtonText: 'ใช่', cancelButtonText: 'ยกเลิก',
            customClass: { popup: 'swal-custom-font' }
        }).then((result) => { if (result.isConfirmed) { window.location.href = 'login.php?logout=1'; } })
    }
    </script>
    
    <style>
        .swal-custom-font { font-family: 'Sarabun', sans-serif !important; }
        
        .header-inner { display: flex; justify-content: space-between; align-items: center; gap: 20px; padding: 10px 20px; }
        
        /* Brand */
        .brand { 
            font-size: 1.6rem; font-weight: 900; text-decoration: none; 
            display: flex; align-items: center; gap: 10px;
            background: linear-gradient(45deg, var(--primary), #0ea5e9, #6366f1);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            transition: transform 0.3s;
        }
        .brand i { -webkit-text-fill-color: var(--primary); }
        .brand:hover { transform: scale(1.05) rotate(-2deg); }

        /* Menu */
        .main-nav { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
        .nav-item { 
            text-decoration: none; color: var(--text-muted); font-weight: 600; 
            padding: 8px 16px; border-radius: 50px; 
            transition: all 0.3s cubic-bezier(0.68, -0.55, 0.27, 1.55);
            font-size: 0.95rem; display: inline-flex; align-items: center; gap: 8px;
            border: 1px solid transparent;
        }
        .nav-item:hover { 
            background-color: var(--info-bg); color: var(--primary); 
            transform: translateY(-3px); box-shadow: 0 4px 10px rgba(0,0,0,0.05); border-color: var(--info);
        }
        .nav-item:active { transform: scale(0.95); }

        /* User Panel */
        .user-panel { display: flex; align-items: center; gap: 15px; flex-shrink: 0; }
        .datetime-badge { display: flex; flex-direction: column; align-items: flex-end; font-size: 0.8rem; color: var(--text-muted); line-height: 1.3; border-right: 1px solid var(--border); padding-right: 15px; }
        .datetime-badge .time { color: var(--primary); font-weight: 700; font-size: 0.9rem; }
        
        .user-info-box {
            display: flex; align-items: center; gap: 12px;
            background: rgba(0,0,0,0.02); padding: 6px 18px; border-radius: 50px; 
            border: 1px solid var(--border);
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .user-info-box:hover {
            background: #fff;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            border-color: var(--primary);
        }
        .user-avatar i { font-size: 34px; color: var(--primary); display: block; transition: 0.4s; }
        .user-info-box:hover .user-avatar i { transform: rotate(360deg); }

        .user-text { display: flex; flex-direction: column; line-height: 1.1; }
        .user-text .name { font-weight: 700; font-size: 0.95rem; color: var(--text-main); }
        .user-text .role { font-size: 0.75rem; color: var(--text-muted); }

        .btn-logout-icon { 
            color: #ef4444; font-size: 1.1rem; padding: 0; 
            width: 36px; height: 36px; border-radius: 50%; 
            background: var(--danger-bg); 
            display: flex; align-items: center; justify-content: center; 
            text-decoration: none; transition: all 0.4s ease; margin-left: 10px;
        }
        .btn-logout-icon:hover { 
            background: #ef4444; color: white; 
            transform: rotate(180deg) scale(1.1); 
            box-shadow: 0 0 10px rgba(239, 68, 68, 0.4); 
        }

        @media (max-width: 992px) {
            .header-inner { flex-direction: column; gap: 15px; padding: 15px; }
            .main-nav { justify-content: center; width: 100%; gap: 10px; }
            .user-panel { width: 100%; justify-content: space-between; border-top: 1px dashed var(--border); padding-top: 10px; }
            .datetime-badge { align-items: flex-start; border-right: none; padding-right: 0; }
        }
    </style>