<?php
// home.php - Premium Modern Design (Repair System)
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

// Greeting Logic
$h = date('H');
$greeting = ($h < 12) ? "อรุณสวัสดิ์ ☀️" : (($h < 16) ? "สวัสดีตอนบ่าย 🌤️" : "สวัสดีตอนเย็น 🌙");

include 'includes/header.php'; 
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

<style>
    /* Base Setup */
    body { 
        background-color: #f8fafc; 
        font-family: 'Sarabun', sans-serif; 
        overflow-x: hidden; /* กัน Scroll แนวนอน */
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

    /* 1. Welcome Banner (Premium Gradient) */
    .welcome-card {
        width: 100%;
        max-width: 1100px;
        background: linear-gradient(120deg, #2563eb, #4f46e5, #7c3aed);
        background-size: 200% 200%;
        animation: gradientMove 6s ease infinite;
        border-radius: 20px;
        padding: 35px 50px;
        color: white;
        box-shadow: 0 20px 40px -10px rgba(67, 56, 202, 0.4);
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: relative;
        overflow: hidden;
    }
    
    @keyframes gradientMove { 0% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } 100% { background-position: 0% 50%; } }

    /* Floating Shapes Decoration */
    .shape { position: absolute; background: rgba(255,255,255,0.1); border-radius: 50%; backdrop-filter: blur(5px); }
    .s1 { width: 150px; height: 150px; top: -50px; right: -20px; }
    .s2 { width: 80px; height: 80px; bottom: 20px; left: 40px; }

    .welcome-content { z-index: 2; }
    .welcome-content h1 { margin: 0; font-size: 2rem; font-weight: 800; letter-spacing: -0.5px; }
    .welcome-content p { margin: 5px 0 0; font-size: 1.1rem; opacity: 0.9; font-weight: 500; }

    .role-badge {
        background: rgba(255, 255, 255, 0.25);
        padding: 8px 20px;
        border-radius: 50px;
        font-weight: 600;
        font-size: 0.9rem;
        border: 1px solid rgba(255,255,255,0.4);
        backdrop-filter: blur(10px);
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        z-index: 2;
        display: flex; align-items: center; gap: 8px;
    }

    /* 2. Image Slider (Clean Look) */
    .slider-section {
        width: 100%; max-width: 1100px;
        height: 380px;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        position: relative;
    }
    .swiper { width: 100%; height: 100%; }
    .swiper-slide img { width: 100%; height: 100%; object-fit: cover; transition: transform 6s ease; }
    .swiper-slide-active img { transform: scale(1.05); }

    /* Custom Nav Buttons */
    .swiper-btn {
        width: 45px; height: 45px; background: rgba(255,255,255,0.9);
        border-radius: 50%; color: #333; transition: 0.3s;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }
    .swiper-btn:after { font-size: 1.2rem; font-weight: bold; }
    .swiper-btn:hover { background: var(--primary); color: white; transform: scale(1.1); }

    /* 3. Quick Menu (Grid Layout) */
    .menu-section { width: 100%; max-width: 1100px; }
    .section-head { 
        margin-bottom: 20px; font-weight: 700; color: #334155; font-size: 1.2rem;
        display: flex; align-items: center; gap: 10px;
    }
    .section-head i { color: var(--primary); }

    .menu-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 25px;
    }

    .menu-item {
        background: white;
        border-radius: 16px;
        padding: 25px;
        display: flex;
        align-items: center;
        gap: 20px;
        text-decoration: none;
        color: #1e293b;
        transition: all 0.3s ease;
        border: 1px solid #f1f5f9;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
        position: relative;
        overflow: hidden;
    }

    .menu-item:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
        border-color: #cbd5e1;
    }

    .icon-box {
        width: 60px; height: 60px;
        border-radius: 14px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.8rem;
        transition: 0.3s;
    }
    
    .menu-item:hover .icon-box { transform: scale(1.1) rotate(5deg); }

    .text-box h3 { margin: 0; font-size: 1.1rem; font-weight: 700; color: #0f172a; }
    .text-box p { margin: 4px 0 0; font-size: 0.85rem; color: #64748b; }

    /* Colors */
    .theme-blue { background: #eff6ff; color: #2563eb; }
    .theme-green { background: #f0fdf4; color: #16a34a; }
    .theme-orange { background: #fff7ed; color: #ea580c; }
    .theme-purple { background: #faf5ff; color: #9333ea; }
    .theme-red { background: #fef2f2; color: #dc2626; }

    /* Responsive */
    @media (max-width: 768px) {
        .welcome-card { flex-direction: column; text-align: center; gap: 15px; padding: 30px; }
        .slider-section { height: 250px; }
        .menu-item { padding: 20px; }
    }
</style>

<div class="home-wrapper">
    
    <div class="welcome-card">
        <div class="shape s1"></div>
        <div class="shape s2"></div>
        
        <div class="welcome-content">
            <p><?php echo $greeting; ?></p>
            <h1>คุณ<?php echo htmlspecialchars($full_name); ?> 👋</h1>
        </div>
        
        <div class="role-badge">
            <i class="fa-solid fa-id-badge"></i>
            <span>สถานะ: <?php echo ucfirst($user_role); ?></span>
        </div>
    </div>

    <div class="slider-section">
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

    <div class="menu-section">
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
    var swiper = new Swiper(".mySwiper", {
        spaceBetween: 0,
        effect: "fade",
        autoplay: { delay: 5000, disableOnInteraction: false },
        pagination: { el: ".swiper-pagination", clickable: true },
        navigation: { nextEl: ".swiper-button-next", prevEl: ".swiper-button-prev" },
        loop: true,
    });
</script>

<?php // include 'includes/footer.php'; ?>
</body>
</html>