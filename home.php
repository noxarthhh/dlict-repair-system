<?php
session_start();
include 'db_connect.php'; 

// 🌟 ตั้งค่าเวลาไทยทันที (สำคัญมาก ใส่ก่อนเรียก date)
date_default_timezone_set('Asia/Bangkok');

// 1. ตรวจสอบการ Login
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== TRUE) {
    header("Location: login.php");
    exit();
}

$page_title = 'หน้าหลัก';
$full_name = $_SESSION['full_name'];
$user_role = $_SESSION['user_role'];

// คำทักทาย (คำนวณจากเวลาไทยที่ตั้งไว้แล้ว)
$h = date('H');
$greeting = ($h < 12) ? "อรุณสวัสดิ์ ☀️" : (($h < 16) ? "สวัสดีตอนบ่าย 🌤️" : "สวัสดีตอนเย็น 🌙");

include 'includes/header.php'; 
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

<style>
    body { overflow: hidden; }

    /* Wrapper หลัก */
    .home-wrapper {
        height: calc(100vh - 80px);
        display: flex;
        flex-direction: column;
        justify-content: flex-start;
        align-items: center;
        padding: 20px;
        overflow-y: auto; /* ให้ Scroll ได้ถ้าเนื้อหาเยอะ */
        gap: 20px;
    }

    /* 1. ส่วนต้อนรับ (Banner) */
    .welcome-section {
        width: 100%;
        max-width: 1000px;
        background: linear-gradient(120deg, #2563eb 0%, #0ea5e9 50%, #3b82f6 100%);
        border-radius: 20px;
        padding: 20px 30px; 
        color: white;
        box-shadow: 0 5px 15px rgba(37, 99, 235, 0.3);
        position: relative;
        overflow: hidden;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-shrink: 0;
    }
    
    /* Decoration */
    .welcome-section::before { content: ''; position: absolute; top: -50px; right: -50px; width: 150px; height: 150px; background: rgba(255,255,255,0.1); border-radius: 50%; }
    .welcome-section h1 { margin: 5px 0; font-size: 1.8rem; font-weight: 800; text-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .role-tag { background: rgba(255, 255, 255, 0.25); padding: 5px 15px; border-radius: 50px; font-size: 0.85rem; font-weight: 600; display: inline-block; }

    /* 2. สไลด์โชว์ (ขยายใหญ่!) */
    .slider-container {
        width: 100%;
        max-width: 1000px;
        height: 400px; 
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 10px 25px rgba(0,0,0,0.15); 
        flex-shrink: 0;
        background: #eee;
    }
    .swiper { width: 100%; height: 100%; }
    .swiper-slide img { width: 100%; height: 100%; object-fit: cover; }
    
    /* ปรับปุ่มเลื่อน */
    .swiper-button-next, .swiper-button-prev { color: white; background: rgba(0,0,0,0.4); width: 50px; height: 50px; border-radius: 50%; transition:0.2s; }
    .swiper-button-next:hover, .swiper-button-prev:hover { background: rgba(0,0,0,0.7); }
    .swiper-button-next:after, .swiper-button-prev:after { font-size: 1.2rem; font-weight: bold; }

    /* 3. เมนู (ล่างสุด) */
    .quick-menu-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        width: 100%;
        max-width: 1000px;
    }

    .menu-card {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 15px;
        display: flex;
        align-items: center;
        gap: 15px;
        text-decoration: none;
        color: var(--text-main);
        transition: all 0.2s ease;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }
    .menu-card:hover { transform: translateY(-3px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); border-color: var(--primary); }
    .menu-icon { width: 45px; height: 45px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; flex-shrink: 0; }
    .menu-info h3 { margin: 0; font-size: 1rem; color: #1e293b; font-weight: 700; }
    .menu-info p { margin: 0; font-size: 0.8rem; color: #64748b; }

    /* Colors */
    .bg-blue { background: #e0f2fe; color: #0284c7; } .bg-green { background: #dcfce7; color: #16a34a; }
    .bg-orange { background: #ffedd5; color: #ea580c; } .bg-purple { background: #f3e8ff; color: #9333ea; }
    .bg-red { background: #fee2e2; color: #dc2626; }
    
    @media (max-width: 768px) {
        .home-wrapper { padding-top: 20px; justify-content: flex-start; }
        .welcome-section { flex-direction: column; text-align: center; gap: 10px; }
        .slider-container { height: 250px; }
    }
</style>

<div class="home-wrapper">
    
    <div class="welcome-section">
        <div>
            <div style="font-size: 1rem; opacity: 0.9; margin-bottom: 2px;"><?php echo $greeting; ?></div>
            <h1>คุณ<?php echo htmlspecialchars($full_name); ?> 🧸</h1>
        </div>
        <span class="role-tag">
            <i class="fa-solid fa-user-tag"></i> สถานะ: <?php echo ucfirst($user_role); ?>
        </span>
    </div>

    <div class="slider-container">
        <div class="swiper mySwiper">
            <div class="swiper-wrapper">
                <div class="swiper-slide"><img src="slides/1.jpg" alt="Slide 1"></div>
                <div class="swiper-slide"><img src="slides/2.jpg" alt="Slide 2"></div>
                <div class="swiper-slide"><img src="slides/3.jpg" alt="Slide 3"></div>
                <div class="swiper-slide"><img src="slides/4.jpg" alt="Slide 4"></div>
                <div class="swiper-slide"><img src="slides/5.jpg" alt="Slide 5"></div>
                <div class="swiper-slide"><img src="slides/6.jpg" alt="Slide 6"></div>
            </div>
            <div class="swiper-button-next"></div>
            <div class="swiper-button-prev"></div>
            <div class="swiper-pagination"></div>
        </div>
    </div>

    <div style="width: 100%; max-width: 1000px;">
        <div style="margin-bottom: 10px; font-weight: 600; color: #64748b; font-size: 0.95rem; border-left: 4px solid var(--primary); padding-left: 10px;">
            เมนูใช้งานด่วน
        </div>
        
        <div class="quick-menu-grid">
            <a href="new_request.php" class="menu-card">
                <div class="menu-icon bg-blue">🔔</div>
                <div class="menu-info"><h3>แจ้งซ่อมใหม่</h3><p>แจ้งปัญหาการใช้งาน</p></div>
            </a>

            <?php if ($user_role == 'requester'): ?>
            <a href="tracking.php" class="menu-card">
                <div class="menu-icon bg-green">📋</div>
                <div class="menu-info"><h3>ติดตามงานซ่อม</h3><p>เช็คสถานะรายการ</p></div>
            </a>
            <?php endif; ?>

            <?php if ($user_role == 'technician' || $user_role == 'admin'): ?>
            <a href="dashboard_tech.php" class="menu-card">
                <div class="menu-icon bg-orange">🛠️</div>
                <div class="menu-info"><h3>จัดการงานซ่อม</h3><p>Dashboard ช่าง</p></div>
            </a>
            <?php endif; ?>

            <?php if ($user_role == 'admin'): ?>
            <a href="admin_report.php" class="menu-card">
                <div class="menu-icon bg-purple">📊</div>
                <div class="menu-info"><h3>รายงานสรุป</h3><p>สถิติภาพรวม</p></div>
            </a>
            <a href="admin_add_user.php" class="menu-card">
                <div class="menu-icon bg-red">👤</div>
                <div class="menu-info"><h3>เพิ่มผู้ใช้งาน</h3><p>จัดการบัญชี</p></div>
            </a>
            <?php endif; ?>
        </div>
    </div>

</div>

<script>
    var swiper = new Swiper(".mySwiper", {
        spaceBetween: 0,
        centeredSlides: true,
        autoplay: { delay: 4000, disableOnInteraction: false },
        pagination: { el: ".swiper-pagination", clickable: true },
        navigation: { nextEl: ".swiper-button-next", prevEl: ".swiper-button-prev" },
        loop: true,
    });
</script>

<?php 
// ไม่ต้องใส่ Footer
// include 'includes/footer.php'; 
?>
</body>
</html>