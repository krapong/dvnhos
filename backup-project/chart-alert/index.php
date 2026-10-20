<?php
require_once 'auth.php';
require_once 'db_config.php';

$session_doctor_code = $is_doctor ? $_SESSION['doctor_code'] : '';

// รับค่าจากฟอร์ม
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$ward_filter = isset($_GET['ward']) ? $_GET['ward'] : '';

// บังคับค่า doctor_code หากเป็นแพทย์
if ($is_doctor) {
    $doctor_code = $session_doctor_code;
} else {
    $doctor_code = isset($_GET['doctor_code']) ? $_GET['doctor_code'] : '';
}

// ดึงรายการตึก (Ward) สำหรับ Dropdown
$sql_wards = "SELECT ward, name FROM ward ORDER BY name";
$stmt_wards = $pdo->prepare($sql_wards);
$stmt_wards->execute();
$ward_list = $stmt_wards->fetchAll(PDO::FETCH_ASSOC);

// สร้าง Query หลัก
$sql = "
    SELECT 
        i.an,
        i.hn,
        CONCAT(IFNULL(p.pname,''), IFNULL(p.fname,''), ' ', IFNULL(p.lname,'')) AS ptname,
        i.regdate AS admdate,
        i.dchdate AS dchdate,
        DATEDIFF(i.dchdate, i.regdate) AS difdate,
        DATEDIFF(CURDATE(), i.dchdate) AS overdue_days,
        w.name AS ward_name,
        d.name AS doctor_name,
        'รอลงความเห็น/Diag หลัก' AS chart_status
    FROM ipt i
    LEFT JOIN patient p ON p.hn = i.hn
    LEFT JOIN ward w ON w.ward = i.ward
    LEFT JOIN doctor d ON d.code = i.admdoctor
    WHERE i.dchdate BETWEEN :start_date AND :end_date
      AND NOT EXISTS (
          SELECT 1 
          FROM ipt_doctor_diag id 
          WHERE id.an = i.an 
            AND id.diag_text IS NOT NULL 
            AND TRIM(id.diag_text) != ''
      )
";

$params = [
    ':start_date' => $start_date,
    ':end_date' => $end_date
];

if (!empty($search)) {
    $sql .= " AND (p.fname LIKE :search OR p.lname LIKE :search OR i.an LIKE :search OR i.hn LIKE :search) ";
    $params[':search'] = "%{$search}%";
}

if (!empty($ward_filter)) {
    $sql .= " AND i.ward = :ward ";
    $params[':ward'] = $ward_filter;
}

if (!empty($doctor_code)) {
    $sql .= " AND i.admdoctor = :doctor_code ";
    $params[':doctor_code'] = $doctor_code;
}

$sql .= " ORDER BY overdue_days DESC ";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// กำหนด Title ของหน้า
if ($is_doctor) {
    $page_title_text = "ชาร์ตค้างของฉัน (My Incomplete Charts)";
} else {
    $page_title_text = "รายละเอียดชาร์ตค้างทั้งหมด";
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_doctor ? "ชาร์ตค้างของฉัน" : "ระบบติดตามสรุปชาร์ตผู้ป่วยใน"; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="wrapper">
    <!-- Sidebar -->
    <nav id="sidebar" class="sidebar">
        <div class="sidebar-header">
            <i class="bi bi-hospital fs-1 d-block mb-2 text-warning"></i>
            ระบบติดตามชาร์ต<br>
            <span class="fs-6 fw-normal">รพ.เทพรัตนเวชชานุกูลฯ</span>
        </div>
        <div class="sidebar-menu">
            <?php if (!$is_doctor): ?>
            <a href="dashboard_summary.php" class="sidebar-link">
                <i class="bi bi-bar-chart-fill"></i> Dashboard ภาพรวม
            </a>
            <a href="disease_dashboard.php" class="sidebar-link">
                <i class="bi bi-graph-up-arrow"></i> แดชบอร์ด 10 อันดับโรค
            </a>
            <?php endif; ?>
            <a href="index.php" class="sidebar-link active">
                <i class="bi bi-card-list"></i> <?php echo $is_doctor ? "ชาร์ตค้างของฉัน (IPD)" : "รายละเอียดชาร์ต (IPD)"; ?>
            </a>
            <a href="opd_incomplete.php" class="sidebar-link">
                <i class="bi bi-people-fill"></i> รายการผู้ป่วยนอก (OPD)
            </a>
            <a href="photo_dashboard.php" class="sidebar-link">
                <i class="bi bi-camera-fill"></i> แดชบอร์ดการเก็บรูปภาพ
            </a>
            <?php if ($is_admin): ?>
            <a href="settings.php" class="sidebar-link">
                <i class="bi bi-gear-fill"></i> การตั้งค่า (Settings)
            </a>
            <a href="doctor_telegram_setup.php" class="sidebar-link">
                <i class="bi bi-clock-history"></i> ตั้งค่ารอบการแจ้งเตือน
            </a>
            <?php endif; ?>
        </div>
        
        <div class="mt-auto p-3 border-top border-secondary">
            <div class="text-white-50 small mb-2">
                <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($_SESSION['user_name'] ?? $_SESSION['user_id']); ?>
                <?php 
                    if($is_doctor) echo " <span class='badge bg-primary'>แพทย์</span>";
                    elseif($is_manager) echo " <span class='badge bg-info text-dark'>Manager</span>";
                    elseif($is_admin) echo " <span class='badge bg-danger'>Admin</span>";
                ?>
            </div>
            <a href="logout.php" class="btn btn-outline-light btn-sm w-100 text-start">
                <i class="bi bi-box-arrow-left"></i> ออกจากระบบ
            </a>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <header class="top-header">
            <button id="toggle-sidebar" class="btn-toggle-sidebar">
                <i class="bi bi-list"></i>
            </button>
            <h5 class="m-0 ms-3 fw-bold text-primary"><?php echo $page_title_text; ?></h5>
        </header>

        <div class="container-fluid main-container">
    
    <!-- Filter Form -->
    <form method="GET" class="filter-form d-flex flex-wrap align-items-center gap-3">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-funnel-fill text-primary fs-4"></i>
            <h5 class="m-0 me-2">กรองข้อมูล</h5>
        </div>
        <div class="d-flex align-items-center gap-2">
            <input type="text" name="search" class="form-control form-control-sm" placeholder="ค้นหา ชื่อ, AN, HN" value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <div class="d-flex align-items-center gap-2">
            <select name="ward" class="form-select form-select-sm">
                <option value="">-- ทุกตึก (All Wards) --</option>
                <?php foreach($ward_list as $w): ?>
                    <option value="<?php echo $w['ward']; ?>" <?php echo $ward_filter === $w['ward'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($w['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php if (!$is_doctor): ?>
            <!-- For admin, keep the doctor code if it was passed via drill-down -->
            <input type="hidden" name="doctor_code" value="<?php echo htmlspecialchars($doctor_code); ?>">
        <?php endif; ?>
        
        <div class="d-flex align-items-center gap-2">
            <label class="fw-bold mb-0">ตั้งแต่:</label>
            <input type="date" name="start_date" class="form-control form-control-sm" style="width: auto;" value="<?php echo htmlspecialchars($start_date); ?>" required>
        </div>
        <div class="d-flex align-items-center gap-2">
            <label class="fw-bold mb-0">ถึง:</label>
            <input type="date" name="end_date" class="form-control form-control-sm" style="width: auto;" value="<?php echo htmlspecialchars($end_date); ?>" required>
        </div>
        <div class="ms-auto">
            <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-search me-1"></i> ค้นหา</button>
            <a href="index.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-counterclockwise me-1"></i> รีเซ็ต</a>
        </div>
    </form>

    <!-- Data Table -->
    <div class="table-container">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="page-title text-primary mb-0"><i class="bi bi-files"></i> รายการชาร์ตผู้ป่วย (รอสรุป)</h5>
            <span class="text-muted small">ข้อมูลช่วง: <strong><?php echo $start_date; ?></strong> ถึง <strong><?php echo $end_date; ?></strong></span>
        </div>
        
        <div class="table-responsive">
            <table class="table table-hover align-middle text-center">
                <thead>
                    <tr>
                        <th width="5%">ลำดับ</th>
                        <th>AN</th>
                        <th>HN</th>
                        <th class="text-start">ชื่อ-สกุล ผู้ป่วย</th>
                        <th>วันที่ Admit</th>
                        <th>วันที่จำหน่าย</th>
                        <th>ค้าง (วัน)</th>
                        <th class="text-start">ตึกผู้ป่วย</th>
                        <?php if (!$is_doctor): ?>
                        <th class="text-start">แพทย์เจ้าของไข้</th>
                        <?php endif; ?>
                        <th>สถานะ</th>
                        <th>HIS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($results) > 0): ?>
                        <?php $i = 1; foreach ($results as $row): ?>
                            <tr>
                                <td><?php echo $i++; ?></td>
                                <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($row['an'] ?? ''); ?></span></td>
                                <td><?php echo htmlspecialchars($row['hn'] ?? ''); ?></td>
                                <td class="text-start text-nowrap fw-bold"><?php echo htmlspecialchars($row['ptname'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($row['admdate'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($row['dchdate'] ?? ''); ?></td>
                                <td>
                                    <?php 
                                        $od = $row['overdue_days'];
                                        if ($od > 30) {
                                            echo "<span class='badge bg-danger fs-6'>+$od วัน</span>";
                                        } elseif ($od > 7) {
                                            echo "<span class='badge bg-warning text-dark fs-6'>+$od วัน</span>";
                                        } else {
                                            echo "<span class='badge bg-success fs-6'>+$od วัน</span>";
                                        }
                                    ?>
                                </td>
                                <td class="text-start"><?php echo htmlspecialchars($row['ward_name'] ?? '-'); ?></td>
                                <?php if (!$is_doctor): ?>
                                <td class="text-start"><?php echo htmlspecialchars($row['doctor_name'] ?? '-'); ?></td>
                                <?php endif; ?>
                                <td><span class="text-danger small"><i class="bi bi-hourglass-split"></i> <?php echo $row['chart_status']; ?></span></td>
                                <td>
                                    <a href="#" class="btn btn-sm btn-outline-info" onclick="alert('ระบบจำลอง: กำลังส่งคำสั่งเปิด AN <?php echo $row['an']; ?> ใน HOSxP HIS...'); return false;">
                                        <i class="bi bi-box-arrow-up-right"></i> เปิดชาร์ต
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="<?php echo $is_doctor ? 10 : 11; ?>" class="py-5 text-success">
                                <i class="bi bi-check-circle-fill fs-1 d-block mb-2"></i>
                                <h5 class="fw-bold">ยอดเยี่ยม! ไม่พบชาร์ตค้างตามเงื่อนไขที่ค้นหา</h5>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('toggle-sidebar').addEventListener('click', function() {
    document.getElementById('sidebar').classList.toggle('toggled');
    if(window.innerWidth <= 768) {
        document.getElementById('sidebar').classList.toggle('show');
    }
});
</script>

    </div>
</div>
</body>
</html>