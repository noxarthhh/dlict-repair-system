<?php
session_start();
include 'db_connect.php'; 

if (!isset($_SESSION['logged_in'])) { header("Location: login.php"); exit(); }

$current_staff_id = $_SESSION['staff_id'];
$user_role = $_SESSION['user_role'];
$is_tech_or_admin = ($user_role == 'technician' || $user_role == 'admin');
$request_id = $_GET['id'] ?? null;

if (!$request_id) { header("Location: " . ($is_tech_or_admin ? 'dashboard_tech.php' : 'tracking.php')); exit(); }

$page_title = 'รายละเอียดงานซ่อม';

// SQL Query
$sql = "SELECT rr.*, a.asset_number, a.asset_type, a.location_group,
        s_req.full_name AS requester_name, s_req.position AS requester_position, s_req.group_name AS requester_group,
        s_tech.full_name AS technician_name 
        FROM repair_requests rr
        LEFT JOIN assets a ON rr.asset_id = a.asset_id
        LEFT JOIN staffs s_req ON rr.requester_id = s_req.staff_id
        LEFT JOIN staffs s_tech ON rr.technician_id = s_tech.staff_id
        WHERE rr.request_id = :request_id";
$stmt = $pdo->prepare($sql);
$stmt->execute(['request_id' => $request_id]);
$request = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$request) { die("ไม่พบข้อมูล"); }
if ($user_role == 'requester' && $request['requester_id'] != $current_staff_id) { die("ไม่มีสิทธิ์เข้าถึง"); }

$asset_show = !empty($request['asset_number']) ? $request['asset_number'] : ($request['manual_asset'] ?: '-');
$type_show = !empty($request['asset_type']) ? $request['asset_type'] : ($request['problem_types'] ?: '-');

include 'includes/header.php'; 
?>

<style>
    /* ---- Global Layout Lock (ปิดตาย Scrollbar หลัก) ---- */
    * { box-sizing: border-box; } /* สำคัญมาก! รวมขอบในการคำนวณ */
    html, body { height: 100%; margin: 0; padding: 0; overflow: hidden; background-color: var(--bg-body); }
    
    /* Wrapper หลัก */
    .single-view-wrapper {
        /* ความสูงจอ - (Header 80px + Padding บนล่าง 40px) = พื้นที่ที่เหลือ */
        height: calc(100vh - 80px); 
        width: 100%;
        max-width: 1600px;
        margin: 0 auto;
        padding: 15px 25px 25px 25px; /* Padding นี้รวมอยู่ใน height แล้วเพราะ box-sizing */
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    /* 1. Header Bar */
    .view-header {
        display: flex; justify-content: space-between; align-items: center;
        flex-shrink: 0; /* ห้ามหด */
        padding: 15px 25px;
        background: #fff;
        border-radius: 16px;
        box-shadow: var(--shadow);
        border: 1px solid var(--border);
    }
    .view-header h1 { margin: 0; font-size: 1.4rem; color: var(--text-main); font-weight: 800; display: flex; align-items: center; gap: 10px; }
    .job-badge { background: var(--info-bg); color: var(--info); padding: 5px 15px; border-radius: 50px; font-size: 0.9rem; font-weight: 700; border: 1px solid var(--info); }

    /* 2. Main Grid Layout */
    .view-grid {
        display: grid;
        grid-template-columns: 380px 1fr; /* ซ้าย 380px คงที่, ขวายืด */
        gap: 20px;
        flex-grow: 1;    /* ยืดเต็มความสูงที่เหลือ */
        min-height: 0;   /* ป้องกัน Grid ดันจนล้น */
        overflow: hidden; /* ห้าม Grid หลักเลื่อน */
    }

    /* --- คอลัมน์ซ้าย (ข้อมูล) --- */
    .col-sidebar {
        background: #fff;
        border-radius: 16px;
        border: 1px solid var(--border);
        box-shadow: var(--shadow);
        padding: 20px;
        overflow-y: auto; /* เลื่อนได้เฉพาะในกล่องนี้ */
        display: flex; flex-direction: column; gap: 20px;
        height: 100%; /* เต็มความสูง Grid */
    }
    
    /* ปรับแต่ง Scrollbar ให้สวยงาม (Chrome/Safari) */
    .col-sidebar::-webkit-scrollbar, .workspace-content::-webkit-scrollbar { width: 6px; }
    .col-sidebar::-webkit-scrollbar-thumb, .workspace-content::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    .col-sidebar::-webkit-scrollbar-track, .workspace-content::-webkit-scrollbar-track { background: transparent; }

    .sidebar-group-title {
        font-size: 0.85rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px;
        margin-bottom: 12px; border-bottom: 2px solid var(--bg-body); padding-bottom: 5px;
    }
    .info-item { margin-bottom: 10px; display: flex; justify-content: space-between; align-items: baseline; font-size: 0.95rem; }
    .info-label { color: var(--text-muted); font-size: 0.85rem; }
    .info-value { font-weight: 600; color: var(--text-main); text-align: right; }

    /* --- คอลัมน์ขวา (พื้นที่ทำงาน) --- */
    .col-workspace {
        display: flex;
        flex-direction: column;
        gap: 15px;
        height: 100%;
        overflow: hidden;
    }

    /* ส่วนบน: อาการ + รูปภาพ (ยืดหยุ่น) */
    .workspace-content {
        flex: 1; /* กินพื้นที่ที่เหลือทั้งหมด */
        min-height: 0; /* ยอมให้หดได้ */
        overflow-y: auto; /* เลื่อนได้ */
        display: flex; flex-direction: column; gap: 15px;
        padding-right: 5px; /* เผื่อที่ Scrollbar */
    }

    /* กล่องอาการเสีย */
    .issue-card {
        background: #fff; border-radius: 16px; padding: 20px; border: 1px solid var(--border); box-shadow: var(--shadow); flex-shrink: 0;
    }
    .issue-text { background: var(--input-bg); padding: 15px; border-radius: 10px; border: 1px solid var(--border); line-height: 1.6; }

    /* กล่องรูปภาพ */
    .image-card {
        background: #27272a;
        border-radius: 16px;
        display: flex; align-items: center; justify-content: center;
        overflow: hidden;
        position: relative;
        min-height: 200px; /* ความสูงขั้นต่ำ */
        box-shadow: var(--shadow);
        flex-shrink: 0; /* ห้ามหดจนหาย */
    }
    .image-card img {
        max-width: 100%; max-height: 350px; /* จำกัดความสูงรูป */
        object-fit: contain; cursor: zoom-in;
        transition: transform 0.3s;
    }
    .image-card:hover img { transform: scale(1.02); }

    /* ส่วนล่าง: ฟอร์มจัดการ (Sticky Bottom) */
    .action-bar {
        flex-shrink: 0; /* ห้ามหด */
        background: #fff;
        border-radius: 16px;
        padding: 15px 20px;
        border: 2px solid var(--primary);
        box-shadow: 0 -5px 20px rgba(0,0,0,0.05);
        z-index: 10;
        animation: slideUp 0.5s ease-out;
    }
    @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

    /* Print Styles */
    .paper-header, .signature-section { display: none; }
    @media print {
        body { overflow: auto; height: auto; background: white; }
        .site-header, .no-print, .btn-primary, .view-header, .action-bar { display: none !important; }
        .single-view-wrapper { height: auto; display: block; padding: 0; }
        .view-grid { display: block; }
        .col-sidebar, .col-workspace, .workspace-content { overflow: visible; height: auto; border: none; box-shadow: none; padding: 0; }
        .image-card { background: none; border: 1px solid #ccc; }
        .image-card img { max-height: 200px; }
        .paper-header, .signature-section { display: block; }
        .print-row { display: flex; gap: 20px; margin-bottom: 10px; }
        .print-col { flex: 1; border: 1px solid #ccc; padding: 10px; }
    }
    @media (max-width: 1024px) {
        body { overflow: auto; }
        .single-view-wrapper { height: auto; }
        .view-grid { grid-template-columns: 1fr; }
        .col-workspace { height: auto; }
    }
</style>

<div class="single-view-wrapper" id="printable-area">
    
    <div class="view-header no-print">
        <div>
            <h1><i class="fa-solid fa-file-invoice"></i> รายละเอียดงานซ่อม</h1>
        </div>
        <div style="display:flex; gap:10px; align-items:center;">
            <span class="job-badge">JOB: <?php echo htmlspecialchars($request['request_no']); ?></span>
            <div style="height:20px; width:1px; background:#e2e8f0; margin:0 5px;"></div>
            <a href="<?php echo $is_tech_or_admin ? 'dashboard_tech.php' : 'tracking.php'; ?>" class="btn-detail" style="border-radius:50px; padding:8px 20px;">
                <i class="fa-solid fa-arrow-left"></i> กลับ
            </a>
            <button onclick="window.print()" class="btn-detail" style="background:#475569; color:white; border:none; border-radius:50px; padding:8px 20px;">
                <i class="fa-solid fa-print"></i> พิมพ์
            </button>
        </div>
    </div>

    <div class="paper-header">
        <h2 style="text-align:center; margin:0;">ใบแจ้งซ่อมครุภัณฑ์คอมพิวเตอร์</h2>
        <div style="text-align:right; font-size:10pt;">เลขที่: <?php echo htmlspecialchars($request['request_no']); ?></div>
        <hr>
    </div>

    <div class="view-grid">
        
        <div class="col-sidebar">
            <div style="text-align:center; padding:15px; background:var(--input-bg); border-radius:12px;">
                <div style="font-size:0.8rem; color:var(--text-muted); margin-bottom:5px;">สถานะปัจจุบัน</div>
                <span class="status-badge status-<?php echo strtolower(str_replace(' ', '_', $request['status'])); ?>" style="font-size:1rem; padding:8px 20px;">
                    <?php echo $request['status']; ?>
                </span>
            </div>

            <div>
                <div class="sidebar-group-title">👤 ข้อมูลผู้แจ้ง</div>
                <div class="info-item"><span class="info-label">ชื่อ-สกุล</span><span class="info-value"><?php echo htmlspecialchars($request['requester_name']); ?></span></div>
                <div class="info-item"><span class="info-label">ตำแหน่ง</span><span class="info-value"><?php echo htmlspecialchars($request['requester_position'] ?: '-'); ?></span></div>
                <div class="info-item"><span class="info-label">กลุ่ม/ฝ่าย</span><span class="info-value"><?php echo htmlspecialchars($request['requester_group'] ?: '-'); ?></span></div>
                <div class="info-item"><span class="info-label">วันที่แจ้ง</span><span class="info-value"><?php echo date('d/m/Y H:i', strtotime($request['request_date'])); ?></span></div>
            </div>

            <div>
                <div class="sidebar-group-title">💻 ข้อมูลอุปกรณ์</div>
                <div class="info-item"><span class="info-label">ทะเบียน</span><span class="info-value"><?php echo htmlspecialchars($asset_show); ?></span></div>
                <div class="info-item"><span class="info-label">ชนิด</span><span class="info-value"><?php echo htmlspecialchars($type_show); ?></span></div>
            </div>

            <?php if ($request['action_taken']): ?>
            <div style="margin-top:auto; background:#f0fdf4; padding:15px; border-radius:12px; border:1px solid #bbf7d0;">
                <div style="font-weight:700; color:#15803d; margin-bottom:5px;">✅ ดำเนินการแล้ว</div>
                <div style="font-size:0.9rem; color:#1e293b;"><?php echo nl2br(htmlspecialchars($request['action_taken'])); ?></div>
                <div style="font-size:0.8rem; color:#64748b; margin-top:5px; text-align:right;">โดย: <?php echo htmlspecialchars($request['technician_name']); ?></div>
            </div>
            <?php endif; ?>
        </div>

        <div class="col-workspace">
            
            <div class="workspace-content">
                <div class="issue-card">
                    <h3 style="margin:0 0 10px 0; font-size:1.1rem; color:var(--danger); display:flex; align-items:center; gap:8px;">
                        <i class="fa-solid fa-triangle-exclamation"></i> อาการที่พบ
                    </h3>
                    <div class="issue-text"><?php echo nl2br(htmlspecialchars($request['issue_details'])); ?></div>
                </div>

                <div class="image-card">
                    <?php if (!empty($request['image_path']) && file_exists($request['image_path'])): ?>
                        <img src="<?php echo htmlspecialchars($request['image_path']); ?>" onclick="window.open(this.src)">
                        <div style="position:absolute; bottom:15px; right:15px; background:rgba(0,0,0,0.6); color:white; padding:5px 12px; border-radius:30px; font-size:0.8rem; backdrop-filter:blur(4px);">
                            <i class="fa-solid fa-expand"></i> ดูภาพขยาย
                        </div>
                    <?php else: ?>
                        <div style="color:rgba(255,255,255,0.4); text-align:center;">
                            <i class="fa-regular fa-image" style="font-size:3rem; margin-bottom:10px; display:block;"></i>
                            ไม่มีรูปภาพ
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="no-print">
            <?php if ($is_tech_or_admin): ?>
                
                <?php if (trim(strtolower($request['status'])) == 'in progress'): ?>
                    <div class="action-bar">
                        <div style="font-weight:700; color:var(--primary); margin-bottom:10px; display:flex; align-items:center; gap:8px;">
                            <i class="fa-solid fa-wrench"></i> บันทึกผลการซ่อม
                        </div>
                        <form id="repairForm" method="POST" action="submit_repair_action.php" style="display:flex; gap:15px;">
                            <input type="hidden" name="request_id" value="<?php echo htmlspecialchars($request['request_id']); ?>">
                            <input type="hidden" name="action" value="complete">
                            
                            <textarea name="action_taken" rows="2" required placeholder="ระบุสิ่งที่ดำเนินการแก้ไข..." 
                                      style="flex-grow:1; padding:12px; border-radius:10px; border:2px solid var(--border); font-size:1rem; resize:none; background:var(--input-bg); color:var(--text-main);"></textarea>
                            
                            <button type="button" onclick="confirmSave(event)" class="btn-primary" style="flex-shrink:0; padding:0 30px; border-radius:10px; font-size:1rem;">
                                <i class="fa-solid fa-check"></i><br>ปิดงาน
                            </button>
                        </form>
                    </div>

                <?php elseif (trim(strtolower($request['status'])) == 'pending'): ?>
                    <div class="action-bar" style="border-color:var(--warning); background:#fffbeb;">
                        <div style="display:flex; justify-content:space-between; align-items:center; width:100%;">
                            <div style="display:flex; align-items:center; gap:15px;">
                                <div style="font-size:2rem; color:var(--warning);">⚠️</div>
                                <div>
                                    <h3 style="margin:0; font-size:1rem; color:#92400e;">รอรับเรื่อง</h3>
                                    <p style="margin:0; font-size:0.9rem; color:#b45309;">กดปุ่มรับงานเพื่อเริ่มดำเนินการ</p>
                                </div>
                            </div>
                            <a href="#" onclick="confirmAccept(event, '<?php echo $request['request_id']; ?>');" class="btn-action" style="padding:12px 30px; border-radius:50px; font-size:1rem; box-shadow:0 4px 10px rgba(0,0,0,0.1);">
                                🚀 รับงานเดี๋ยวนี้
                            </a>
                        </div>
                    </div>
                <?php endif; ?>

            <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="signature-section" style="margin-top:30px;">
        <div style="display:flex; justify-content:space-between;">
            <div style="text-align:center; width:40%;">
                <div style="border-bottom:1px dotted #000; height:30px;"></div><p>ผู้แจ้ง</p>
            </div>
            <div style="text-align:center; width:40%;">
                <div style="border-bottom:1px dotted #000; height:30px;"></div><p>ผู้ดำเนินการ</p>
            </div>
        </div>
    </div>
</div>

<script>
function confirmSave(e) {
    e.preventDefault();
    Swal.fire({ title: 'ยืนยันปิดงาน?', text: "ตรวจสอบความถูกต้องแล้วใช่หรือไม่", icon: 'question', showCancelButton: true, confirmButtonText: 'ใช่, บันทึก', cancelButtonText: 'ยกเลิก', confirmButtonColor: '#2563eb' }).then((r) => { if (r.isConfirmed) document.getElementById('repairForm').submit(); });
}
function confirmAccept(e, id) {
    e.preventDefault();
    Swal.fire({ title: 'ยืนยันรับงาน?', text: "คุณต้องการรับงานนี้ใช่หรือไม่", icon: 'question', showCancelButton: true, confirmButtonText: 'ใช่, รับงาน', cancelButtonText: 'ยกเลิก', confirmButtonColor: '#3b82f6' }).then((r) => { if (r.isConfirmed) window.location.href = 'update_status.php?action=accept&id=' + id; });
}
</script>

<?php include 'includes/footer.php'; ?>