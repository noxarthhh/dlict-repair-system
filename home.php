<?php
session_start();
include 'db_connect.php'; 

// 1. ตรวจสอบการ Login
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== TRUE) {
    header("Location: login.php");
    exit();
}

$page_title = 'หน้าหลัก';
$full_name = $_SESSION['full_name'];
$user_role = $_SESSION['user_role'];

// คำทักทาย
$h = date('H');
$greeting = ($h < 12) ? "อรุณสวัสดิ์ ☀️" : (($h < 16) ? "สวัสดีตอนบ่าย 🌤️" : "สวัสดีตอนเย็น 🌙");

include 'includes/header.php'; 
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

<style>
    /* บังคับหน้าจอไม่เลื่อน */
    body { overflow: hidden; background-color: #f0f4f8; }

    /* Wrapper หลัก */
    .home-wrapper {
        height: calc(100vh - 80px);
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 20px;
        overflow-y: auto;
        gap: 25px;
        /* พื้นหลังเคลื่อนไหวจางๆ */
        background: radial-gradient(circle at 50% 50%, rgba(37, 99, 235, 0.05) 0%, transparent 70%);
    }

    /* 1. ส่วนต้อนรับ (Premium Banner) */
    .welcome-section {
        width: 100%;
        max-width: 1000px;
        background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);
        border-radius: 24px;
        padding: 25px 40px;
        color: white;
        box-shadow: 0 20px 40px -10px rgba(79, 70, 229, 0.4);
        position: relative;
        overflow: hidden;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-shrink: 0;
        transition: transform 0.3s;
    }
    .welcome-section:hover { transform: scale(1.01); }
    
    /* Decoration Circles (Animated) */
    .circle-deco { position: absolute; border-radius: 50%; background: rgba(255,255,255,0.1); animation: float 6s infinite ease-in-out; }
    .c1 { width: 200px; height: 200px; top: -80px; right: -50px; }
    .c2 { width: 100px; height: 100px; bottom: -20px; left: 30px; animation-delay: 1s; }
    
    @keyframes float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-20px); } }

    .welcome-text h1 { margin: 5px 0; font-size: 2.2rem; font-weight: 800; letter-spacing: -0.5px; text-shadow: 0 2px 10px rgba(0,0,0,0.2); }
    .role-tag { 
        background: rgba(255, 255, 255, 0.2); 
        padding: 8px 20px; border-radius: 50px; 
        font-size: 0.95rem; font-weight: 600; 
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255,255,255,0.3);
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }

    /* 2. สไลด์โชว์ (Glass Frame) */
    .slider-container {
        width: 100%; max-width: 1000px; height: 350px;
        border-radius: 20px; overflow: hidden;
        box-shadow: 0 15px 35px rgba(0,0,0,0.15);
        flex-shrink: 0;
        border: 4px solid white; /* กรอบขาว */
    }
    .swiper { width: 100%; height: 100%; }
    .swiper-slide img { width: 100%; height: 100%; object-fit: cover; transition: transform 5s; }
    .swiper-slide-active img { transform: scale(1.1); } /* ซูมเข้าช้าๆ */
    
    .swiper-button-next, .swiper-button-prev { color: white; background: rgba(0,0,0,0.3); width: 45px; height: 45px; border-radius: 50%; backdrop-filter: blur(5px); border: 1px solid rgba(255,255,255,0.2); transition: 0.2s; }
    .swiper-button-next:hover, .swiper-button-prev:hover { background: var(--primary); border-color: var(--primary); transform: scale(1.1); }

    /* 3. เมนู (Glassmorphism Grid) */
    .menu-section { width: 100%; max-width: 1000px; animation: slideUp 0.8s ease-out; }
    @keyframes slideUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }

    .menu-title { 
        margin-bottom: 15px; font-weight: 800; color: #475569; font-size: 1.1rem; 
        display: flex; align-items: center; gap: 10px;
    }
    .menu-title::before { content:''; display:block; width: 5px; height: 25px; background: var(--primary); border-radius: 10px; }

    .quick-menu-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 20px;
    }

    .menu-card {
        background: rgba(255, 255, 255, 0.8);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255,255,255,0.5);
        border-radius: 20px;
        padding: 20px;
        display: flex; align-items: center; gap: 15px;
        text-decoration: none; color: var(--text-main);
        transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); /* เด้งดึ๋ง */
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
        position: relative; overflow: hidden;
    }
    
    /* เอฟเฟกต์แสงวิ่งผ่าน */
    .menu-card::after {
        content: ''; position: absolute; top: 0; left: -100%; width: 50%; height: 100%;
        background: linear-gradient(to right, transparent, rgba(255,255,255,0.8), transparent);
        transform: skewX(-25deg); transition: 0.5s; pointer-events: none;
    }
    .menu-card:hover::after { left: 150%; transition: 0.7s; }

    .menu-card:hover { 
        transform: translateY(-8px) scale(1.02); 
        box-shadow: 0 20px 40px -5px rgba(37, 99, 235, 0.2); 
        border-color: var(--primary); 
    }
    
    .menu-icon { 
        width: 55px; height: 55px; border-radius: 14px; 
        display: flex; align-items: center; justify-content: center; 
        font-size: 1.8rem; flex-shrink: 0; 
        box-shadow: 0 8px 15px rgba(0,0,0,0.1);
        transition: 0.3s;
    }
    .menu-card:hover .menu-icon { transform: rotate(10deg) scale(1.1); }

    .menu-info h3 { margin: 0 0 4px 0; font-size: 1.1rem; color: #1e293b; font-weight: 800; }
    .menu-info p { margin: 0; font-size: 0.85rem; color: #64748b; font-weight: 500; }

    /* Icon Colors */
    .bg-blue { background: linear-gradient(135deg, #e0f2fe 0%, #bae6fd 100%); color: #0284c7; }
    .bg-green { background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%); color: #16a34a; }
    .bg-orange { background: linear-gradient(135deg, #ffedd5 0%, #fed7aa 100%); color: #ea580c; }
    .bg-purple { background: linear-gradient(135deg, #f3e8ff 0%, #e9d5ff 100%); color: #9333ea; }
    .bg-red { background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%); color: #dc2626; }
    
    @media (max-width: 768px) {
        .home-wrapper { padding-top: 15px; justify-content: flex-start; }
        .welcome-section { flex-direction: column; text-align: center; gap: 15px; padding: 30px 20px; }
        .slider-container { height: 220px; }
        .menu-card { padding: 15px; }
    }
</style>

<div class="home-wrapper">
    
    <div class="welcome-section">
        <div class="circle-deco c1"></div>
        <div class="circle-deco c2"></div>
        <div class="welcome-text" style="z-index:1;">
            <div style="font-size: 1.1rem; opacity: 0.9; margin-bottom: 5px; font-weight:500;"><?php echo $greeting; ?></div>
            <h1>คุณ<?php echo htmlspecialchars($full_name); ?> 🧸</h1>
        </div>
        <span class="role-tag" style="z-index:1;">
            <i class="fa-solid fa-user-tag"></i> สถานะ: <?php echo ucfirst($user_role); ?>
        </span>
    </div>

    <div class="slider-container">
        <div class="swiper mySwiper">
            <div class="swiper-wrapper">
                <div class="swiper-slide"><img src="https://images.unsplash.com/photo-1498050108023-c5249f4df085?q=80&w=1200" alt="Slide 1"></div>
                <div class="swiper-slide"><img src="https://images.unsplash.com/photo-1550751827-4bd374c3f58b?q=80&w=1200" alt="Slide 2"></div>
                <div class="swiper-slide"><img src="https://images.unsplash.com/photo-1518770660439-4636190af475?q=80&w=1200" alt="Slide 3"></div>
                <div class="swiper-slide"><img src="https://images.unsplash.com/photo-1531297461136-82088dfd355c?q=80&w=1200" alt="Slide 4"></div>
                <div class="swiper-slide"><img src="https://images.unsplash.com/photo-1581091226825-a6a2a5aee158?q=80&w=1200" alt="Slide 5"></div>
                <div class="swiper-slide"><img src="https://images.unsplash.com/photo-1488590528505-98d2b5aba04b?q=80&w=1200" alt="Slide 6"></div>
                <div class="swiper-slide"><img src="https://images.unsplash.com/photo-1526374965328-7f61d4dc18c5?q=80&w=1200" alt="Slide 7"></div>
                <div class="swiper-slide"><img src="https://images.unsplash.com/photo-1550041473-d296a1a8ec52?q=80&w=1200" alt="Slide 8"></div>
                <div class="swiper-slide"><img src="https://images.unsplash.com/photo-1544197150-b99a580bb7f8?q=80&w=1200" alt="Slide 9"></div>
            </div>
            <div class="swiper-button-next"></div>
            <div class="swiper-button-prev"></div>
            <div class="swiper-pagination"></div>
        </div>
    </div>

    <div class="menu-section">
        <div class="menu-title">เมนูใช้งานด่วน</div>
        
        <div class="quick-menu-grid">
            <a href="new_request.php" class="menu-card">
                <div class="menu-icon bg-blue"><i class="fa-solid fa-bell"></i></div>
                <div class="menu-info"><h3>แจ้งซ่อมใหม่</h3><p>แจ้งปัญหาการใช้งาน</p></div>
            </a>

            <?php if ($user_role == 'requester'): ?>
            <a href="tracking.php" class="menu-card">
                <div class="menu-icon bg-green"><i class="fa-solid fa-list-check"></i></div>
                <div class="menu-info"><h3>ติดตามงานซ่อม</h3><p>เช็คสถานะรายการ</p></div>
            </a>
            <?php endif; ?>

            <?php if ($user_role == 'technician' || $user_role == 'admin'): ?>
            <a href="dashboard_tech.php" class="menu-card">
                <div class="menu-icon bg-orange"><i class="fa-solid fa-screwdriver-wrench"></i></div>
                <div class="menu-info"><h3>จัดการงานซ่อม</h3><p>Dashboard ช่าง</p></div>
            </a>
            <?php endif; ?>

            <?php if ($user_role == 'admin'): ?>
            <a href="admin_report.php" class="menu-card">
                <div class="menu-icon bg-purple"><i class="fa-solid fa-chart-pie"></i></div>
                <div class="menu-info"><h3>รายงานสรุป</h3><p>สถิติภาพรวม</p></div>
            </a>
            <a href="admin_add_user.php" class="menu-card">
                <div class="menu-icon bg-red"><i class="fa-solid fa-user-plus"></i></div>
                <div class="menu-info"><h3>เพิ่มผู้ใช้งาน</h3><p>จัดการบัญชี</p></div>
            </a>
            <?php endif; ?>
        </div>
    </div>

</div>

<script>
    var swiper = new Swiper(".mySwiper", {
        spaceBetween: 0,
        effect: "fade", /* เปลี่ยนรูปแบบเป็น Fade */
        centeredSlides: true,
        autoplay: { delay: 4000, disableOnInteraction: false },
        pagination: { el: ".swiper-pagination", clickable: true },
        navigation: { nextEl: ".swiper-button-next", prevEl: ".swiper-button-prev" },
        loop: true,
    });
</script>

<?php 
// include 'includes/footer.php'; 
?>
</body>
</html>