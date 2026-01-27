<?php
// includes/header.php

// 1. ตรวจสอบ Session และค่าเริ่มต้น
if (session_status() == PHP_SESSION_NONE) session_start();

$theme = $_COOKIE['app_theme'] ?? 'default';
$lang_code = $_COOKIE['app_lang'] ?? 'th';

// Dictionary ภาษา
$lang = [
    'th' => [ 
        'title' => 'ระบบบริหารจัดการและซ่อมบำรุงคอมพิวเตอร์ สพป.ชลบุรี เขต 2', 
        'home' => 'หน้าหลัก', 
        'dashboard' => 'รายการแจ้งซ่อม', 
        'report' => 'แดชบอร์ด', 
        'add_user' => 'จัดการผู้ใช้', 
        'manage_cat' => 'จัดการประเภทงาน',
        'new_request' => 'แจ้งซ่อม', 
        'tracking' => 'ติดตามงาน', 
        'login' => 'เข้าสู่ระบบ', 
        'logout' => 'ออกจากระบบ' 
    ],
    'en' => [ 
        'title' => 'IT Maintenance and Service Management System (Chonburi Primary Educational Service Area Office 2)', 
        'home' => 'Home', 
        'dashboard' => 'Dashboard', 
        'report' => 'Report', 
        'add_user' => 'Add User', 
        'manage_cat' => 'Manage Categories', 
        'new_request' => 'New Request', 
        'tracking' => 'Tracking', 
        'login' => 'Login', 
        'logout' => 'Logout' 
    ]
];
$L = $lang[$lang_code];

date_default_timezone_set('Asia/Bangkok');
if (!isset($page_title)) $page_title = $L['title'];

// 2. ดึงข้อมูลผู้ใช้
$logged_in = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === TRUE;
$user_role = $_SESSION['user_role'] ?? 'guest';
$full_name = $_SESSION['full_name'] ?? 'ผู้ใช้งาน';

$user_details = [];
if ($logged_in && isset($_SESSION['staff_id'])) {
    if (!isset($pdo)) include 'db_connect.php';
    try {
        $stmt_u = $pdo->prepare("SELECT username, full_name, group_name, position FROM staffs WHERE staff_id = ?");
        $stmt_u->execute([$_SESSION['staff_id']]);
        $user_details = $stmt_u->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) { }
}

// 3. เตรียมวันที่/เวลา
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
    
    <style>
        .swal-custom-font { font-family: 'Sarabun', sans-serif !important; }
        
        /* --- Header Layout (Grid System for Balance) --- */
        .site-header {
            background-color: #fff;
            box-shadow: 0 4px 20px rgba(0,0,0,0.03);
            position: sticky;
            top: 0;
            z-index: 1000;
            height: 70px;
        }

        .header-inner { 
            height: 100%;
            display: grid;
            /* แบ่งเป็น 3 ส่วน: โลโก้(auto) | เมนู(ขยายเต็มที่) | ผู้ใช้(auto) */
            /* หรือถ้าอยากให้เมนูกลางเป๊ะๆ ใช้ 1fr auto 1fr แต่จะกินที่โลโก้ */
            grid-template-columns: auto 1fr auto; 
            align-items: center;
            gap: 20px;
            padding: 0 25px;
            max-width: 1600px; /* จำกัดความกว้างไม่ให้ห่างกันเกินไปบนจอใหญ่ */
            margin: 0 auto;
        }
        
        /* --- 1. Brand / Logo --- */
        .brand { 
            text-decoration: none; 
            display: flex; align-items: center; gap: 12px;
            min-width: max-content; /* ไม่ให้โลโก้หด */
        }
        
        .brand i { 
            font-size: 1.8rem; 
            background: linear-gradient(45deg, var(--primary), #0ea5e9);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }

        .brand-text { display: flex; flex-direction: column; justify-content: center; }
        .brand-main {
            font-size: 1.2rem; font-weight: 800;
            color: #1e293b;
            line-height: 1;
        }
        .brand-sub { 
            font-size: 0.75rem; color: #64748b; font-weight: 500; 
            margin-top: 2px;
        }

        /* --- 2. Navigation Menu (Centered) --- */
        .main-nav { 
            display: flex; 
            align-items: center; 
            justify-content: center; /* จัดกึ่งกลางพื้นที่ */
            gap: 4px; 
            height: 100%;
        }

        .nav-item {
            display: flex; align-items: center; gap: 6px;
            padding: 8px 12px;
            color: #475569;
            text-decoration: none;
            font-size: 0.85rem; /* ลดขนาดลงนิดนึงให้ดูคลีน */
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.2s;
            white-space: nowrap;
        }

        .nav-item:hover {
            background-color: var(--info-bg);
            color: var(--primary);
        }
        
        .nav-item i { font-size: 0.9rem; }

        /* --- 3. User Panel --- */
        .user-panel { 
            display: flex; align-items: center; gap: 12px; 
            justify-content: flex-end;
            min-width: max-content;
        }
        
        .datetime-badge { 
            display: flex; flex-direction: column; align-items: flex-end; 
            font-size: 0.75rem; color: #64748b; 
            border-right: 1px solid #e2e8f0; padding-right: 15px; 
        }
        .datetime-badge .time { color: var(--primary); font-weight: 700; }
        
        .user-info-box {
            display: flex; align-items: center; gap: 10px;
            background: #f8fafc; padding: 4px 6px 4px 12px; 
            border-radius: 50px; 
            border: 1px solid #e2e8f0; transition: all 0.2s; cursor: pointer;
        }
        .user-info-box:hover {
            background: #fff; box-shadow: 0 4px 12px rgba(0,0,0,0.05); border-color: var(--primary);
        }
        .user-avatar i { font-size: 28px; color: var(--primary); }

        .user-text { display: flex; flex-direction: column; line-height: 1.1; text-align: left; }
        .user-text .name { font-weight: 700; font-size: 0.85rem; color: #334155; }
        .user-text .role { font-size: 0.7rem; color: #94a3b8; }

        .btn-logout-icon { 
            color: #ef4444; width: 28px; height: 28px; border-radius: 50%; 
            background: #fee2e2; display: flex; align-items: center; justify-content: center; 
            text-decoration: none; transition: all 0.2s; margin-left: 8px; font-size: 0.8rem;
        }
        .btn-logout-icon:hover { background: #ef4444; color: white; transform: scale(1.1); }

        /* --- Responsive Logic (แก้ปัญหาซ้อนกัน) --- */
        
        /* Step 1: เมื่อจอเริ่มเล็กลง (Laptop ทั่วไป) -> ซ่อนคำอธิบายไทย */
        @media (max-width: 1450px) {
            .brand-sub { display: none; }
        }

        /* Step 2: เมื่อจอเล็กลงอีก (Tablet Pro) -> ซ่อนวันที่ */
        @media (max-width: 1100px) {
            .datetime-badge { display: none; }
            .header-inner { padding: 0 15px; gap: 10px; }
        }

        /* Step 3: เมื่อจอเล็กมาก (Tablet/Mobile) -> เปลี่ยน Layout */
        @media (max-width: 900px) {
            .site-header { height: auto; padding: 10px 0; }
            .header-inner { display: flex; flex-wrap: wrap; gap: 10px; }
            
            .brand { width: 100%; justify-content: center; margin-bottom: 5px; }
            
            .main-nav { 
                width: 100%; 
                order: 3; 
                justify-content: flex-start;
                overflow-x: auto; 
                padding-bottom: 5px; 
                border-top: 1px dashed #e2e8f0; 
                padding-top: 10px;
            }
            
            .user-panel { width: 100%; justify-content: center; }
            .user-info-box { width: 100%; justify-content: space-between; }
        }
    </style>
</head>
<body data-theme="<?php echo htmlspecialchars($theme); ?>">
    
    <header class="site-header">
        <div class="header-inner">
            
            <a class="brand" href="<?php echo $logged_in ? 'home.php' : 'login.php'; ?>">
                <i class="fa-solid fa-screwdriver-wrench"></i> 
                <div class="brand-text">
                    <span class="brand-main">IT Maintenance System</span>
                    <span class="brand-sub">ระบบบริหารจัดการและซ่อมบำรุงคอมพิวเตอร์ สพป.ชลบุรี เขต 2</span>
                </div>
            </a>
            
            <?php if ($logged_in): ?>
            <nav class="main-nav">
                <a href="home.php" class="nav-item">
                    <i class="fa-solid fa-house"></i> <?php echo $L['home']; ?>
                </a>
                
                <?php if ($user_role == 'technician' || $user_role == 'admin'): ?>
                    <a href="dashboard_tech.php" class="nav-item">
                        <i class="fa-solid fa-gauge"></i> <?php echo $L['dashboard']; ?>
                    </a>
                <?php endif; ?>

                <?php if ($user_role == 'admin'): ?>
                    <a href="admin_report.php" class="nav-item">
                        <i class="fa-solid fa-chart-pie"></i> <?php echo $L['report']; ?>
                    </a>
                    <a href="admin_add_user.php" class="nav-item">
                        <i class="fa-solid fa-user-plus"></i> <?php echo $L['add_user']; ?>
                    </a>
                    <a href="admin_manage_categories.php" class="nav-item">
                        <i class="fa-solid fa-list"></i> <?php echo $L['manage_cat']; ?>
                    </a>
                <?php endif; ?>
                
                <a href="new_request.php" class="nav-item">
                    <i class="fa-solid fa-bell"></i> <?php echo $L['new_request']; ?>
                </a>
                
                <?php if ($user_role == 'requester'): ?>
                    <a href="tracking.php" class="nav-item">
                        <i class="fa-solid fa-list-check"></i> <?php echo $L['tracking']; ?>
                    </a>
                <?php endif; ?>
            </nav>
            <?php else: ?>
            <div></div> <?php endif; ?>

            <div class="user-panel">
                <div class="datetime-badge">
                    <span><i class="fa-regular fa-calendar"></i> <?php echo $date_str; ?></span>
                    <span class="time"><i class="fa-regular fa-clock"></i> <?php echo $time_str; ?></span>
                </div>

                <?php if ($logged_in): ?>
                    <div class="user-info-box" onclick="showProfile()" title="คลิกดูข้อมูล">
                        <div style="display:flex; align-items:center; gap:8px;">
                            <div class="user-avatar">
                                <i class="fa-solid fa-circle-user"></i>
                            </div>
                            <div class="user-text">
                                <span class="name"><?php echo htmlspecialchars($full_name); ?></span>
                                <small class="role"><?php echo ucfirst($user_role); ?></small>
                            </div>
                        </div>
                        <a class="btn-logout-icon animate__animated" href="#" onclick="confirmLogout(event)" 
                           title="<?php echo $L['logout']; ?>" 
                           onmouseover="this.classList.add('animate__pulse')" 
                           onmouseout="this.classList.remove('animate__pulse')">
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