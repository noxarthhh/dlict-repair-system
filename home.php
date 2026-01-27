<?php
// home.php
session_start();
include 'db_connect.php'; 

// 1. Check Login
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== TRUE) {
    header("Location: login.php");
    exit();
}

$page_title = 'หน้าหลัก';
$full_name = $_SESSION['full_name'];
$user_role = $_SESSION['user_role'];

// 2. Greeting Logic (Time Based)
date_default_timezone_set('Asia/Bangkok');
$h = date('H');
if ($h < 11) $time_greeting = "ยามเช้า";
elseif ($h < 13) $time_greeting = "ยามสาย"; // ปรับช่วงเวลาได้ตามชอบ
elseif ($h < 16) $time_greeting = "ยามบ่าย";
else $time_greeting = "ยามเย็น";

$welcome_msg = "สวัสดี {$time_greeting} คุณ{$full_name}";
$welcome_sub = "ยินดีต้อนรับเข้าสู่ระบบบริหารจัดการและซ่อมบำรุงคอมพิวเตอร์ สพป.ชลบุรี เขต 2 ขอให้เป็นวันที่ดีครับ 👋";

// 3. Check Pop-up Flag
$show_popup = false;
if (!isset($_SESSION['welcome_shown'])) {
    $show_popup = true;
    $_SESSION['welcome_shown'] = true; // Set flag to prevent showing again
}

include 'includes/header.php'; 
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    /* Base Setup */
    body { 
        background-color: #f8fafc; 
        font-family: 'Sarabun', sans-serif; 
        overflow-x: hidden;
    }

    /* Main Wrapper */
    .home-wrapper {
        min-height: calc(100vh - 80px);
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 30px 20px;
        gap: 35px;
        background: radial-gradient(circle at top center, rgba(59, 130, 246, 0.08) 0%, transparent 60%);
    }

    /* (ลบ .welcome-card เดิมออก เพราะจะใช้ Popup แทน หรือจะเก็บไว้เป็น Banner นิ่งๆ ก็ได้) */
    /* ถ้าต้องการเก็บ Banner นิ่งๆ ไว้ด้านบน Slider ก็ใช้ Style เดิมได้เลยครับ */
    /* แต่ในที่นี้ผมจะซ่อน .welcome-card เพื่อไม่ให้ซ้ำซ้อนกับ Popup ตามโจทย์ */

    /* 2. Image Slider */
    .slider-section {
        width: 100%; max-width: 1100px;
        height: 420px; /* เพิ่มความสูงนิดหน่อยให้เด่นขึ้น */
        border-radius: 24px;
        overflow: hidden;
        box-shadow: 0 15px 35px rgba(0,0,0,0.08);
        position: relative;
        margin-top: 20px; /* ขยับลงมาหน่อยเพราะไม่มี welcome-card แล้ว */
    }
    .swiper { width: 100%; height: 100%; }
    .swiper-slide img { width: 100%; height: 100%; object-fit: cover; transition: transform 6s ease; }
    .swiper-slide-active img { transform: scale(1.05); }

    .swiper-btn {
        width: 45px; height: 45px; background: rgba(255,255,255,0.9);
        border-radius: 50%; color: #333; transition: 0.3s;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }
    .swiper-btn:after { font-size: 1.2rem; font-weight: bold; }
    .swiper-btn:hover { background: var(--primary); color: white; transform: scale(1.1); }

    /* 3. Quick Menu */
    .menu-section { width: 100%; max-width: 1100px; }
    .section-head { 
        margin-bottom: 25px; font-weight: 700; color: #334155; font-size: 1.3rem;
        display: flex; align-items: center; gap: 12px;
    }
    .section-head i { color: var(--primary); }

    .menu-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 25px;
    }

    .menu-item {
        background: white;
        border-radius: 20px;
        padding: 30px;
        display: flex;
        align-items: center;
        gap: 20px;
        text-decoration: none;
        color: #1e293b;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        border: 1px solid #f1f5f9;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);
        position: relative;
        overflow: hidden;
    }

    .menu-item:hover {
        transform: translateY(-8px);
        box-shadow: 0 20px 30px -5px rgba(0, 0, 0, 0.08);
        border-color: #cbd5e1;
    }

    .icon-box {
        width: 65px; height: 65px;
        border-radius: 18px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.8rem;
        transition: 0.4s;
    }
    
    .menu-item:hover .icon-box { transform: scale(1.1) rotate(10deg); }

    .text-box h3 { margin: 0; font-size: 1.15rem; font-weight: 700; color: #0f172a; }
    .text-box p { margin: 5px 0 0; font-size: 0.9rem; color: #64748b; }

    /* Colors */
    .theme-blue { background: #eff6ff; color: #2563eb; }
    .theme-green { background: #f0fdf4; color: #16a34a; }
    .theme-orange { background: #fff7ed; color: #ea580c; }
    .theme-purple { background: #faf5ff; color: #9333ea; }
    .theme-red { background: #fef2f2; color: #dc2626; }

    /* Custom SweetAlert Font */
    .swal2-popup { font-family: 'Sarabun', sans-serif !important; }
    .swal2-title { font-size: 1.6rem !important; color: #1e293b !important; }
    .swal2-html-container { font-size: 1.1rem !important; color: #475569 !important; line-height: 1.6 !important; }

    /* Responsive */
    @media (max-width: 992px) {
        .slider-section { height: 280px; }
    }
</style>

<div class="home-wrapper">
    
    <div class="slider-section animate__animated animate__fadeInUp">
        <div class="swiper mySwiper">
            <div class="swiper-wrapper">
                <div class="swiper-slide"><img src="https://images.unsplash.com/photo-1581092921461-eab62e97a780?q=80&w=1200" alt="Repair"></div>
                <div class="swiper-slide"><img src="https://images.unsplash.com/photo-1591799264318-7e6ef8ddb7ea?q=80&w=1200" alt="Computer"></div>
                <div class="swiper-slide"><img src="https://images.unsplash.com/photo-1597733336794-12d05021d510?q=80&w=1200" alt="Tech"></div>
            </div>
            <div class="swiper-button-next swiper-btn"></div>
            <div class="swiper-button-prev swiper-btn"></div>
            <div class="swiper-pagination"></div>
        </div>
    </div>

    <div class="menu-section animate__animated animate__fadeInUp animate__delay-1s">
        <div class="section-head"><i class="fa-solid fa-layer-group"></i> เมนูใช้งานด่วน</div>
        
        <div class="menu-grid">
            
            <a href="new_request.php" class="menu-item">
                <div class="icon-box theme-blue"><i class="fa-solid fa-wrench"></i></div>
                <div class="text-box">
                    <h3>แจ้งซ่อมใหม่</h3>
                    <p>แจ้งปัญหาอุปกรณ์ขัดข้อง</p>
                </div>
            </a>

            <?php if ($user_role == 'requester'): ?>
            <a href="tracking.php" class="menu-item">
                <div class="icon-box theme-green"><i class="fa-solid fa-magnifying-glass-location"></i></div>
                <div class="text-box">
                    <h3>ติดตามสถานะ</h3>
                    <p>เช็คความคืบหน้างานซ่อม</p>
                </div>
            </a>
            <?php endif; ?>

            <?php if ($user_role == 'technician' || $user_role == 'admin'): ?>
            <a href="dashboard_tech.php" class="menu-item">
                <div class="icon-box theme-orange"><i class="fa-solid fa-screwdriver-wrench"></i></div>
                <div class="text-box">
                    <h3>จัดการงานซ่อม</h3>
                    <p>ระบบสำหรับช่างเทคนิค</p>
                </div>
            </a>
            <?php endif; ?>

            <?php if ($user_role == 'admin'): ?>
            <a href="admin_report.php" class="menu-item">
                <div class="icon-box theme-purple"><i class="fa-solid fa-chart-pie"></i></div>
                <div class="text-box">
                    <h3>รายงานสรุป</h3>
                    <p>สถิติการซ่อมและงบประมาณ</p>
                </div>
            </a>
            <a href="admin_add_user.php" class="menu-item">
                <div class="icon-box theme-red"><i class="fa-solid fa-users-gear"></i></div>
                <div class="text-box">
                    <h3>จัดการผู้ใช้</h3>
                    <p>เพิ่ม/ลบ บัญชีในระบบ</p>
                </div>
            </a>
            <?php endif; ?>

        </div>
    </div>

</div>

<script>
    // 1. Swiper Init
    var swiper = new Swiper(".mySwiper", {
        spaceBetween: 0,
        effect: "fade",
        autoplay: { delay: 5000, disableOnInteraction: false },
        pagination: { el: ".swiper-pagination", clickable: true },
        navigation: { nextEl: ".swiper-button-next", prevEl: ".swiper-button-prev" },
        loop: true,
    });

    // 2. Greeting Popup Logic
    <?php if ($show_popup): ?>
    document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
            title: '<?php echo $welcome_msg; ?>',
            html: '<?php echo $welcome_sub; ?>',
            icon: 'success', // หรือใช้ 'info' ตามชอบ
            imageUrl: 'images/welcome_icon.png', // ถ้ามีรูปไอคอนน่ารักๆ ใส่ตรงนี้ได้ (optional)
            imageWidth: 100,
            imageHeight: 100,
            imageAlt: 'Welcome Image',
            confirmButtonText: 'เข้าสู่ระบบงาน',
            confirmButtonColor: '#3b82f6',
            timer: 5000, // ปิดเองใน 5 วิ (optional)
            timerProgressBar: true,
            backdrop: `
                rgba(0,0,123,0.4)
                url("images/nyan-cat.gif") 
                left top
                no-repeat
            ` // อันนี้เป็นลูกเล่น Backdrop (ลบออกได้ถ้าชอบแบบเรียบๆ)
        });
    });
    <?php endif; ?>
</script>

<?php // include 'includes/footer.php'; ?>
</body>
</html>