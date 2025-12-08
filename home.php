<?php
session_start();
include 'db_connect.php'; 

// 1. ตรวจสอบการ Login
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== TRUE) {
    header("Location: login.php");
    exit();
}

$page_title = 'หน้าหลัก - ระบบแจ้งซ่อม DLICT';
$full_name = $_SESSION['full_name'];
$user_role = $_SESSION['user_role'];

// --- ฟังก์ชันคำทักทายแบบน่ารัก ---
function get_greeting() {
    $hour = date('H');
    if ($hour < 12) return "สวัสดีตอนเช้าคร้าบ ⛅🌈✨";
    if ($hour < 16) return "สวัสดีตอนบ่ายจ้า ☀️🍦🥤";
    return "สวัสดีตอนเย็นครับ 🌙💤🌟";
}

include 'includes/header.php'; 
?>

<style>
    /* Welcome Banner - ปรับให้เด่นและน่ารัก */
    .welcome-section {
        background: linear-gradient(120deg, #2563eb 0%, #0ea5e9 50%, #3b82f6 100%);
        border-radius: var(--radius);
        padding: 50px 40px;
        color: white;
        margin-bottom: 40px;
        box-shadow: 0 10px 25px -5px rgba(37, 99, 235, 0.4);
        position: relative;
        overflow: hidden;
    }
    
    /* Decoration Circles */
    .welcome-section::before {
        content: ''; position: absolute; top: -50px; right: -50px;
        width: 200px; height: 200px; background: rgba(255,255,255,0.1); border-radius: 50%;
    }
    .welcome-section::after {
        content: ''; position: absolute; bottom: -30px; left: 20%;
        width: 100px; height: 100px; background: rgba(255,255,255,0.05); border-radius: 50%;
    }
    
    .welcome-text h1 { 
        color: white; margin: 10px 0 15px 0; 
        font-size: 2.8rem; font-weight: 800;
        text-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .welcome-text p { font-size: 1.3rem; opacity: 0.95; font-weight: 500; }
    
    .role-tag {
        background: rgba(255, 255, 255, 0.25);
        padding: 8px 16px;
        border-radius: 50px;
        font-size: 1rem;
        font-weight: 600;
        display: inline-block;
        margin-top: 15px;
        border: 1px solid rgba(255, 255, 255, 0.4);
        backdrop-filter: blur(4px);
    }

    /* Quick Actions Grid */
    .quick-menu-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 25px;
    }

    .menu-card {
        background: white;
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 30px;
        display: flex;
        align-items: center;
        gap: 20px;
        text-decoration: none;
        color: var(--text-main);
        transition: all 0.3s ease;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
    }

    .menu-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 15px 30px -5px rgba(0,0,0,0.1);
        border-color: var(--primary);
    }

    .menu-icon {
        width: 70px; height: 70px;
        border-radius: 16px;
        display: flex; align-items: center; justify-content: center;
        font-size: 2rem; flex-shrink: 0;
    }

    .menu-info h3 { margin: 0 0 8px 0; font-size: 1.2rem; color: var(--text-main); font-weight: 700; }
    .menu-info p { margin: 0; font-size: 0.95rem; color: var(--text-muted); }

    /* Colors */
    .bg-blue { background: #e0f2fe; color: #0284c7; }
    .bg-green { background: #dcfce7; color: #16a34a; }
    .bg-orange { background: #ffedd5; color: #ea580c; }
    .bg-purple { background: #f3e8ff; color: #9333ea; }
    .bg-red { background: #fee2e2; color: #dc2626; }
</style>

<div class="container">
    
    <div class="welcome-section">
        <div class="welcome-text">
            <p><?php echo get_greeting(); ?></p>
            <h1>คุณ<?php echo htmlspecialchars($full_name); ?> 🧸💖</h1>
            <p style="font-size: 1.1rem; opacity: 0.9;">ยินดีต้อนรับเข้าสู่ระบบ DLICT Repair ครับผม 🛠️✨</p>
            <span class="role-tag">
                <i class="fa-solid fa-user-tag"></i> สถานะ: <?php echo ucfirst($user_role); ?>
            </span>
        </div>
    </div>

    <h3 style="margin-bottom: 25px; color: var(--text-main); font-size: 1.3rem; border-left: 5px solid var(--primary); padding-left: 15px;">
        เมนูใช้งานด่วน 🚀
    </h3>

    <div class="quick-menu-grid">
        
        <a href="new_request.php" class="menu-card">
            <div class="menu-icon bg-blue">🔔</div>
            <div class="menu-info">
                <h3>แจ้งซ่อมใหม่</h3>
                <p>พบปัญหาการใช้งาน แจ้งได้ทันที</p>
            </div>
        </a>

        <?php if ($user_role == 'requester'): ?>
        <a href="tracking.php" class="menu-card">
            <div class="menu-icon bg-green">📋</div>
            <div class="menu-info">
                <h3>ติดตามงานซ่อม</h3>
                <p>ตรวจสอบสถานะรายการของคุณ</p>
            </div>
        </a>
        <?php endif; ?>

        <?php if ($user_role == 'technician' || $user_role == 'admin'): ?>
        <a href="dashboard_tech.php" class="menu-card">
            <div class="menu-icon bg-orange">🛠️</div>
            <div class="menu-info">
                <h3>จัดการงานซ่อม</h3>
                <p>รับงาน บันทึกผล และปิดงานซ่อม</p>
            </div>
        </a>
        <?php endif; ?>

        <?php if ($user_role == 'admin'): ?>
        <a href="admin_report.php" class="menu-card">
            <div class="menu-icon bg-purple">📊</div>
            <div class="menu-info">
                <h3>รายงานสรุป</h3>
                <p>ดูสถิติภาพรวมประจำเดือน</p>
            </div>
        </a>
        <a href="admin_add_user.php" class="menu-card">
            <div class="menu-icon bg-red">👤</div>
            <div class="menu-info">
                <h3>เพิ่มผู้ใช้งาน</h3>
                <p>สร้างบัญชีใหม่สำหรับเจ้าหน้าที่</p>
            </div>
        </a>
        <?php endif; ?>

    </div>
</div>

<?php include 'includes/footer.php'; ?>