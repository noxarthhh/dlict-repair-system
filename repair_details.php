<?php
session_start();
include 'db_connect.php'; 

// 1. ตรวจสอบการ Login
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== TRUE) {
    header("Location: login.php");
    exit();
}

$current_staff_id = $_SESSION['staff_id'];
$user_role = $_SESSION['user_role'];
$is_tech_or_admin = ($user_role == 'technician' || $user_role == 'admin');
$request_id = $_GET['id'] ?? null;

if (!$request_id) {
    header("Location: " . ($is_tech_or_admin ? 'dashboard_tech.php' : 'tracking.php'));
    exit();
}

$page_title = 'รายละเอียดใบแจ้งซ่อม';

// 2. ดึงข้อมูล
$sql = "
    SELECT 
        rr.*, 
        a.asset_number, a.asset_type, a.location_group,
        s_req.full_name AS requester_name, s_req.position AS requester_position, s_req.group_name AS requester_group,
        s_tech.full_name AS technician_name, s_tech.position AS technician_position
    FROM repair_requests rr
    LEFT JOIN assets a ON rr.asset_id = a.asset_id
    LEFT JOIN staffs s_req ON rr.requester_id = s_req.staff_id
    LEFT JOIN staffs s_tech ON rr.technician_id = s_tech.staff_id
    WHERE rr.request_id = :request_id
";

$stmt = $pdo->prepare($sql);
$stmt->execute(['request_id' => $request_id]);
$request = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$request) { die("ไม่พบข้อมูล"); }
if ($user_role == 'requester' && $request['requester_id'] != $current_staff_id) { die("ไม่มีสิทธิ์เข้าถึง"); }

// Logic แสดงเลขทะเบียน
$display_asset_no = !empty($request['asset_number']) ? $request['asset_number'] : ($request['manual_asset'] ?: '-');
$display_asset_type = !empty($request['asset_type']) ? $request['asset_type'] : '-';

include 'includes/header.php'; 
?>

<style>
    /* สไตล์สำหรับหน้าเว็บปกติ (Screen) */
    .paper-header, .signature-section { display: none; }
    
    /* ========================================= */
    /* 🖨️ สไตล์สำหรับการพิมพ์ (Print - A4 One Page) */
    /* ========================================= */
    @media print {
        @page {
            size: A4;
            margin: 10mm; /* ขอบกระดาษแคบลงเพื่อให้เนื้อหาพอดี */
        }
        
        body * { visibility: hidden; }
        .site-header, .no-print, .btn-primary, footer, .alert { display: none !important; }
        
        #printable-area, #printable-area * { 
            visibility: visible; 
        }

        #printable-area {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            font-family: 'Sarabun', sans-serif;
            color: black;
            font-size: 11pt; /* ลดขนาดฟอนต์ให้พอดี */
            line-height: 1.3;
        }

        /* หัวกระดาษ */
        .paper-header { 
            display: block;
            text-align: center; 
            border-bottom: 2px solid #000; 
            padding-bottom: 10px; 
            margin-bottom: 15px; 
        }
        .paper-title { font-size: 14pt; font-weight: bold; margin: 0; }
        .paper-subtitle { font-size: 12pt; margin: 0; }
        .job-no { text-align: right; font-size: 10pt; margin-bottom: 5px; }

        /* การจัดวางเนื้อหา (Grid System สำหรับ Print) */
        .print-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            gap: 15px;
        }
        .print-col { flex: 1; }

        /* กล่องข้อมูล */
        .form-section { 
            border: 1px solid #ccc; 
            border-radius: 4px;
            padding: 8px; 
            margin-bottom: 10px;
            page-break-inside: avoid; /* ห้ามตัดกลางหน้า */
        }
        
        h3 { 
            font-size: 11pt; 
            font-weight: bold; 
            margin: 0 0 5px 0; 
            background-color: #eee; 
            padding: 2px 5px; 
            border-bottom: 1px solid #ccc;
        }
        
        p { margin: 2px 0; }

        /* จัดการรูปภาพไม่ให้กินที่ */
        .print-img-container {
            text-align: center;
            margin-top: 5px;
            height: 150px; /* บังคับความสูงพื้นที่รูป */
            overflow: hidden;
            border: 1px dashed #ccc;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .print-img-container img {
            max-height: 145px;
            max-width: 95%;
            object-fit: contain;
        }

        /* ส่วนลายเซ็น */
        .signature-section { 
            display: flex; 
            justify-content: space-between; 
            margin-top: 20px; 
            page-break-inside: avoid;
        }
        .sig-box { 
            text-align: center; 
            width: 45%; 
            border: 1px solid #fff; /* ใช้พื้นที่แต่ไม่เห็นขอบ */
        }
        .sig-line { 
            border-bottom: 1px dotted #000; 
            height: 1px; 
            width: 80%; 
            margin: 25px auto 5px auto; 
        }
    }
</style>

<div class="container" id="printable-area">
    
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px;" class="no-print">
        <a href="<?php echo $is_tech_or_admin ? 'dashboard_tech.php' : 'tracking.php'; ?>" class="btn-detail">⬅ กลับหน้าหลัก</a>
        <button onclick="window.print()" class="btn-primary" style="background:#6c757d; border-color:#6c757d;">🖨️ พิมพ์ใบแจ้งซ่อม (PDF)</button>
    </div>

    <div class="paper-header">
        <div class="job-no">เลขที่ใบงาน: <b><?php echo htmlspecialchars($request['request_no']); ?></b></div>
        <h2 class="paper-title">แบบฟอร์มการให้บริการกลุ่มส่งเสริมการศึกษาทางไกลฯ (DLICT)</h2>
        <p class="paper-subtitle">สำนักงานเขตพื้นที่การศึกษาประถมศึกษาชลบุรี เขต 2</p>
        <div style="border: 1px solid #000; display: inline-block; padding: 3px 15px; margin-top: 5px; font-weight: bold; font-size: 11pt;">
            งานบริการซ่อมบำรุง
        </div>
    </div>

    <div class="detail-content">
        
        <div class="print-row">
            <div class="print-col form-section">
                <h3>👤 ข้อมูลผู้แจ้ง</h3>
                <p><strong>ชื่อ-สกุล:</strong> <?php echo htmlspecialchars($request['requester_name']); ?></p>
                <p><strong>ตำแหน่ง:</strong> <?php echo htmlspecialchars($request['requester_position']); ?></p>
                <p><strong>กลุ่ม/ฝ่าย:</strong> <?php echo htmlspecialchars($request['requester_group']); ?></p>
                <p><strong>วันที่แจ้ง:</strong> <?php echo date('d/m/Y H:i', strtotime($request['request_date'])); ?></p>
            </div>
            
            <div class="print-col form-section">
                <h3>💻 ข้อมูลครุภัณฑ์</h3>
                <p><strong>ทะเบียน:</strong> <?php echo htmlspecialchars($display_asset_no); ?></p>
                <p><strong>ชนิด:</strong> <?php echo htmlspecialchars($display_asset_type); ?></p>
                <p><strong>สถานที่ตั้ง:</strong> <?php echo htmlspecialchars($request['location_group'] ?: '-'); ?></p>
                <p><strong>สถานะงาน:</strong> <?php echo $request['status']; ?></p>
            </div>
        </div>

        <div class="form-section">
            <h3>⚠️ อาการ/สาเหตุ (Issue)</h3>
            <div style="min-height: 40px;">
                <?php echo nl2br(htmlspecialchars($request['issue_details'])); ?>
            </div>
            
            <?php if (!empty($request['image_path']) && file_exists($request['image_path'])): ?>
            <div class="print-img-container">
                <img src="<?php echo htmlspecialchars($request['image_path']); ?>" alt="รูปภาพประกอบ">
            </div>
            <p style="text-align:center; font-size:9pt; color:#666; margin:0;" class="no-print">(รูปภาพประกอบ)</p>
            <?php endif; ?>
        </div>

        <?php if ($request['action_taken']): ?>
        <div class="form-section">
            <h3>🛠️ ผลการดำเนินการ (Action Taken)</h3>
            <p><strong>รายละเอียด:</strong> <?php echo nl2br(htmlspecialchars($request['action_taken'])); ?></p>
            <p><strong>ผู้ดำเนินการ:</strong> <?php echo htmlspecialchars($request['technician_name']); ?></p>
            <p><strong>วันที่เสร็จสิ้น:</strong> <?php echo date('d/m/Y H:i', strtotime($request['repair_completion_date'])); ?></p>
        </div>
        <?php endif; ?>
    </div>

    <div class="signature-section">
        <div class="sig-box">
            <div class="sig-line"></div>
            <p>( <?php echo htmlspecialchars($request['requester_name']); ?> )</p>
            <p>ผู้แจ้ง / ผู้รับผิดชอบเครื่อง</p>
            <p>วันที่ ......./......./.......</p>
        </div>
        <div class="sig-box">
            <div class="sig-line"></div>
            <p>( <?php echo htmlspecialchars($request['technician_name'] ?: '.......................................'); ?> )</p>
            <p>ผู้ดำเนินการ / ช่างซ่อม</p>
            <p>วันที่ ......./......./.......</p>
        </div>
    </div>

    <div class="no-print">
        <?php if ($is_tech_or_admin && $request['status'] == 'In Progress'): ?>
            <div class="card" style="margin-top: 30px; border-top: 4px solid var(--primary);">
                <h3>🛠️ บันทึกผลการซ่อม</h3>
                <form method="POST" action="submit_repair_action.php">
                    <input type="hidden" name="request_id" value="<?php echo htmlspecialchars($request['request_id']); ?>">
                    <div class="form-group">
                        <label>รายละเอียดการดำเนินการ:</label>
                        <textarea name="action_taken" rows="4" required placeholder="ระบุสิ่งที่ดำเนินการแก้ไข..."></textarea>
                    </div>
                    <button type="submit" name="action" value="complete" class="btn-primary">✅ บันทึกผลและปิดงาน</button>
                </form>
            </div>
        <?php endif; ?>
    </div>

</div>

<?php include 'includes/footer.php'; ?>