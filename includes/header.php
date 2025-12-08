<?php
if (session_status() == PHP_SESSION_NONE) session_start();

date_default_timezone_set('Asia/Bangkok');

if (!isset($page_title)) $page_title = 'ระบบแจ้งซ่อม DLICT';

$logged_in = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === TRUE;
$user_role = $_SESSION['user_role'] ?? 'guest';
$full_name = $_SESSION['full_name'] ?? 'ผู้ใช้งาน';

// วันที่และเวลาไทย
$thai_months = [1=>'ม.ค.',2=>'ก.พ.',3=>'มี.ค.',4=>'เม.ย.',5=>'พ.ค.',6=>'มิ.ย.',7=>'ก.ค.',8=>'ส.ค.',9=>'ก.ย.',10=>'ต.ค.',11=>'พ.ย.',12=>'ธ.ค.'];
$date_str = date('j') . ' ' . $thai_months[(int)date('n')] . ' ' . (date('Y')+543);
$time_str = date('H:i') . ' น.';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    
    <link rel="stylesheet" href="assets/css/style.css">
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <header class="site-header">
        <div class="container header-inner">
            
            <a class="brand" href="<?php echo $logged_in ? 'home.php' : 'login.php'; ?>">
                <i class="fa-solid fa-screwdriver-wrench"></i> DLICT Repair
            </a>
            
            <nav class="main-nav desktop-nav">
                <?php if ($logged_in): ?>
                    <a href="home.php" class="<?php echo basename($_SERVER['PHP_SELF'])=='home.php'?'active':''; ?>">
                        <i class="fa-solid fa-house"></i> หน้าหลัก
                    </a>
                    
                    <?php if ($user_role == 'technician' || $user_role == 'admin'): ?>
                        <a href="dashboard_tech.php" class="<?php echo basename($_SERVER['PHP_SELF'])=='dashboard_tech.php'?'active':''; ?>">
                            <i class="fa-solid fa-gauge"></i> Dashboard
                        </a>
                    <?php endif; ?>

                    <?php if ($user_role == 'admin'): ?>
                        <a href="admin_report.php" class="<?php echo basename($_SERVER['PHP_SELF'])=='admin_report.php'?'active':''; ?>">
                            <i class="fa-solid fa-chart-pie"></i> รายงาน
                        </a>
                        <a href="admin_add_user.php" class="<?php echo basename($_SERVER['PHP_SELF'])=='admin_add_user.php'?'active':''; ?>">
                            <i class="fa-solid fa-user-plus"></i> เพิ่มผู้ใช้
                        </a> 
                    <?php endif; ?>
                    
                    <a href="new_request.php" class="<?php echo basename($_SERVER['PHP_SELF'])=='new_request.php'?'active':''; ?>">
                        <i class="fa-solid fa-bell"></i> แจ้งซ่อม
                    </a>
                    
                    <?php if ($user_role == 'requester'): ?>
                        <a href="tracking.php" class="<?php echo basename($_SERVER['PHP_SELF'])=='tracking.php'?'active':''; ?>">
                            <i class="fa-solid fa-list-check"></i> ติดตามงาน
                        </a>
                    <?php endif; ?>
                <?php endif; ?>
            </nav>

            <div class="user-panel">
                <div class="datetime-badge">
                    <span><i class="fa-regular fa-calendar"></i> <?php echo $date_str; ?></span>
                    <span class="time"><i class="fa-regular fa-clock"></i> <?php echo $time_str; ?></span>
                </div>

                <?php if ($logged_in): ?>
                    <div class="user-info">
                        <span class="user-name">
                            <i class="fa-solid fa-circle-user"></i> 
                            <?php echo htmlspecialchars($full_name); ?> 
                            <small>(<?php echo ucfirst($user_role); ?>)</small>
                        </span>
                        <a class="btn-logout-icon" href="#" onclick="confirmLogout(event)" title="ออกจากระบบ">
                            <i class="fa-solid fa-right-from-bracket"></i>
                        </a>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="btn-login-link">เข้าสู่ระบบ</a>
                <?php endif; ?>
            </div>
        </div>
    </header>
    <main class="container">

    <script>
    function confirmLogout(e) {
        e.preventDefault(); 
        Swal.fire({
            title: 'ออกจากระบบ?',
            text: "คุณต้องการออกจากระบบใช่หรือไม่",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'ออกจากระบบ',
            cancelButtonText: 'ยกเลิก',
            customClass: { popup: 'swal-custom-font' }
        }).then((result) => {
            if (result.isConfirmed) { window.location.href = 'login.php?logout=1'; }
        })
    }
    </script>
    
    <style>
        .swal-custom-font { font-family: 'Sarabun', sans-serif !important; }
        
        /* Layout จัดวาง */
        .header-inner {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            padding: 10px 20px;
        }

        /* Brand Logo */
        .brand {
            font-size: 1.4rem;
            font-weight: 800;
            color: var(--primary);
            text-decoration: none;
            flex-shrink: 0;
            display: flex; align-items: center; gap: 8px;
        }

        /* Navigation Menu */
        .main-nav {
            display: flex;
            gap: 5px;
            align-items: center;
            flex-wrap: wrap; /* ยอมให้ตกบรรทัดถ้าที่เต็ม */
        }
        .main-nav a {
            text-decoration: none;
            color: #64748b;
            font-weight: 600;
            padding: 8px 12px;
            border-radius: 8px;
            transition: 0.2s;
            font-size: 0.95rem;
            display: inline-flex; align-items: center; gap: 6px;
        }
        .main-nav a:hover, .main-nav a.active {
            background-color: #eff6ff;
            color: var(--primary);
        }

        /* User Panel (Right Side) */
        .user-panel {
            display: flex;
            align-items: center;
            gap: 15px;
            flex-shrink: 0;
        }

        /* Date Time Badge */
        .datetime-badge {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            font-size: 0.8rem;
            color: #94a3b8;
            line-height: 1.3;
            border-right: 1px solid #e2e8f0;
            padding-right: 15px;
        }
        .datetime-badge .time {
            color: var(--primary);
            font-weight: 700;
            font-size: 0.9rem;
        }

        /* User Info */
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .user-name {
            font-weight: 600;
            color: #334155;
            font-size: 0.95rem;
            display: flex; flex-direction: column; align-items: flex-end; line-height: 1.2;
        }
        .user-name small {
            font-weight: 400; color: #94a3b8; font-size: 0.8rem;
        }

        /* Logout Button */
        .btn-logout-icon {
            color: #ef4444;
            font-size: 1.2rem;
            padding: 8px;
            border-radius: 50%;
            background: #fee2e2;
            transition: 0.2s;
            display: flex; align-items: center; justify-content: center;
        }
        .btn-logout-icon:hover {
            background: #ef4444;
            color: white;
            transform: scale(1.1);
        }

        /* Responsive Mobile */
        @media (max-width: 992px) {
            .header-inner { flex-direction: column; gap: 15px; padding: 15px; }
            .main-nav { justify-content: center; width: 100%; gap: 10px; }
            .user-panel { width: 100%; justify-content: space-between; border-top: 1px dashed #e2e8f0; padding-top: 10px; }
            .datetime-badge { align-items: flex-start; border-right: none; padding-right: 0; }
            .user-name { align-items: flex-start; }
        }
    </style>