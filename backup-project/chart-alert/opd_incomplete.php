<?php
require_once 'auth.php';
require_once 'db_config.php';

$is_doctor = isset($_SESSION['is_doctor']) && $_SESSION['is_doctor'] === true;
$session_doctor_code = $is_doctor ? ($_SESSION['doctor_code'] ?? '') : '';

// กรองวันที่และอื่นๆ
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$dep_filter = isset($_GET['dep']) ? $_GET['dep'] : '';
$issue_filter = isset($_GET['issue']) ? $_GET['issue'] : '';

// ดึงรายชื่อแผนกสำหรับ Dropdown (กรองคำว่า "ไม่ใช้", "ยกเลิก" และ "รพ.สต" ออก)
$stmt_dep = $pdo->query("SELECT depcode, department as name FROM kskdepartment WHERE department IS NOT NULL AND department != '' AND department NOT LIKE '%ไม่ใช้%' AND department NOT LIKE '%ยกเลิก%' AND department NOT LIKE '%รพ.สต%' ORDER BY department");
$department_list = $stmt_dep->fetchAll(PDO::FETCH_ASSOC);

// สร้างเงื่อนไข
$where_clauses = ["v.vstdate BETWEEN :start_date AND :end_date"];
$params = [':start_date' => $start_date, ':end_date' => $end_date];

// เงื่อนไข: ประเภทปัญหาที่ค้าง
if ($issue_filter === 'no_dx') {
    $where_clauses[] = "(v.pdx IS NULL OR trim(v.pdx) = '')";
} elseif ($issue_filter === 'no_cc') {
    $where_clauses[] = "(os.cc IS NULL OR trim(os.cc) = '')";
} elseif ($issue_filter === 'both') {
    $where_clauses[] = "((v.pdx IS NULL OR trim(v.pdx) = '') AND (os.cc IS NULL OR trim(os.cc) = ''))";
} else {
    $where_clauses[] = "((v.pdx IS NULL OR trim(v.pdx) = '') OR (os.cc IS NULL OR trim(os.cc) = ''))";
}

if ($search !== '') {
    $where_clauses[] = "(v.hn LIKE :search OR p.fname LIKE :search OR p.lname LIKE :search OR o.an LIKE :search)";
    $params[':search'] = "%$search%";
}

if ($dep_filter !== '') {
    $where_clauses[] = "(o.main_dep = :dep OR o.cur_dep = :dep)";
    $params[':dep'] = $dep_filter;
}

if ($is_doctor && isset($_SESSION['doctor_code'])) {
    $where_clauses[] = "(v.dx_doctor = :doctor_code OR o.doctor = :doctor_code)";
    $params[':doctor_code'] = $_SESSION['doctor_code'];
}

$where_sql = implode(' AND ', $where_clauses);

$sql = "
    SELECT 
        o.vstdate,
        o.vsttime,
        v.hn,
        CONCAT(p.pname, p.fname, ' ', p.lname) as ptname,
        os.cc,
        v.pdx,
        k_main.department as main_dep_name,
        k_cur.department as cur_dep_name,
        o.an,
        p.cid,
        v.vn,
        CASE 
            WHEN (v.pdx IS NULL OR trim(v.pdx) = '') AND (os.cc IS NULL OR trim(os.cc) = '') THEN 'ไม่มี Dx. และ CC/PI'
            WHEN (v.pdx IS NULL OR trim(v.pdx) = '') THEN 'ไม่มี Dx.'
            WHEN (os.cc IS NULL OR trim(os.cc) = '') THEN 'ไม่มี CC/PI'
            ELSE ''
        END as remark
    FROM vn_stat v
    LEFT JOIN ovst o ON o.vn = v.vn
    LEFT JOIN opdscreen os ON os.vn = v.vn
    LEFT JOIN patient p ON p.hn = v.hn
    LEFT JOIN kskdepartment k_main ON k_main.depcode = o.main_dep
    LEFT JOIN kskdepartment k_cur ON k_cur.depcode = o.cur_dep
    WHERE $where_sql
    ORDER BY o.vstdate DESC, o.vsttime DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายการผู้ป่วยนอก (OPD) ค้างข้อมูล</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <!-- DataTables Buttons CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .table-responsive {
            max-height: 65vh;
            overflow-y: auto;
        }
        #opdTable thead th {
            position: sticky;
            top: 0;
            z-index: 10;
            background-color: #3b82f6; /* Soft blue */
            color: white;
            box-shadow: 0 2px 4px -1px rgba(0, 0, 0, 0.1);
        }
    </style>
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
            <a href="index.php" class="sidebar-link">
                <i class="bi bi-card-list"></i> <?php echo $is_doctor ? "ชาร์ตค้างของฉัน (IPD)" : "รายละเอียดชาร์ต (IPD)"; ?>
            </a>
            <a href="opd_incomplete.php" class="sidebar-link active">
                <i class="bi bi-people-fill"></i> รายการผู้ป่วยนอก (OPD)
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
            <h5 class="m-0 ms-3 fw-bold text-primary">รายการผู้ป่วยนอก (OPD) ค้างข้อมูล (ไม่มี Dx / ไม่มี CC)</h5>
        </header>

        <div class="container-fluid main-container">
    
    <!-- Filter Form -->
    <form method="GET" class="filter-form d-flex flex-wrap align-items-center gap-2 mb-3">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-funnel-fill text-primary fs-4"></i>
            <h5 class="m-0 me-2">กรองข้อมูล</h5>
        </div>
        
        <input type="text" name="search" class="form-control form-control-sm w-auto" placeholder="ค้นหา HN, ชื่อ, AN" value="<?php echo htmlspecialchars($search); ?>">
        
        <select name="dep" class="form-select form-select-sm w-auto">
            <option value="">-- ทุกแผนก --</option>
            <?php foreach($department_list as $d): ?>
                <option value="<?php echo $d['depcode']; ?>" <?php echo $dep_filter === $d['depcode'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($d['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="issue" class="form-select form-select-sm w-auto">
            <option value="">-- ปัญหาทั้งหมด --</option>
            <option value="no_dx" <?php echo $issue_filter === 'no_dx' ? 'selected' : ''; ?>>ไม่มี Dx.</option>
            <option value="no_cc" <?php echo $issue_filter === 'no_cc' ? 'selected' : ''; ?>>ไม่มี CC/PI</option>
            <option value="both" <?php echo $issue_filter === 'both' ? 'selected' : ''; ?>>ไม่มีทั้ง Dx. และ CC</option>
        </select>

        <div class="d-flex align-items-center gap-2 ms-2">
            <label class="fw-bold mb-0" style="font-size:0.9rem;">ตั้งแต่:</label>
            <input type="date" name="start_date" class="form-control form-control-sm" style="width: auto;" value="<?php echo htmlspecialchars($start_date); ?>" required>
        </div>
        
        <div class="d-flex align-items-center gap-2">
            <label class="fw-bold mb-0" style="font-size:0.9rem;">ถึง:</label>
            <input type="date" name="end_date" class="form-control form-control-sm" style="width: auto;" value="<?php echo htmlspecialchars($end_date); ?>" required>
        </div>
        
        <div class="ms-auto">
            <button type="submit" class="btn btn-primary btn-sm px-3"><i class="bi bi-search"></i> ค้นหา</button>
            <a href="opd_incomplete.php" class="btn btn-outline-secondary btn-sm px-3"><i class="bi bi-arrow-counterclockwise"></i> รีเซ็ต</a>
        </div>
    </form>

    <div class="table-container">
        <div class="table-responsive">
            <table id="opdTable" class="table table-hover align-middle table-sm" style="font-size: 0.9rem; width: 100%;">
                <thead class="text-center">
                    <tr>
                        <th width="4%">ที่</th>
                        <th width="10%">วัน-เวลา</th>
                        <th width="8%">HN</th>
                        <th width="8%">AN</th>
                        <th width="10%">เลขบัตรประชาชน</th>
                        <th width="8%">VN</th>
                        <th width="15%" class="text-start">ชื่อผู้ป่วย</th>
                        <th width="15%" class="text-start">อาการสำคัญ (CC)</th>
                        <th width="12%" class="text-start">Diag Text</th>
                        <th width="9%">แผนกแรกรับ</th>
                        <th width="9%">แผนกล่าสุด</th>
                        <th width="8%">หมายเหตุ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($records) > 0): ?>
                        <?php $i = 1; foreach($records as $r): 
                            $vstdate_th = date('d/m/', strtotime($r['vstdate'])) . (date('Y', strtotime($r['vstdate'])) + 543);
                            $vsttime = date('H:i', strtotime($r['vsttime']));
                        ?>
                            <tr>
                                <td class="text-center"><?php echo $i++; ?></td>
                                <td class="text-center"><?php echo $vstdate_th . ' <br><small class="text-muted">' . $vsttime . ' น.</small>'; ?></td>
                                <td class="text-center text-primary fw-bold"><?php echo htmlspecialchars($r['hn']); ?></td>
                                <td class="text-center text-danger"><?php echo htmlspecialchars($r['an'] ?? '-'); ?></td>
                                <td class="text-center"><?php echo htmlspecialchars($r['cid'] ?? '-'); ?></td>
                                <td class="text-center text-secondary"><?php echo htmlspecialchars($r['vn'] ?? '-'); ?></td>
                                <td class="text-start fw-bold"><?php echo htmlspecialchars($r['ptname']); ?></td>
                                <td class="text-start text-truncate" style="max-width: 150px;" title="<?php echo htmlspecialchars($r['cc']); ?>">
                                    <?php echo htmlspecialchars($r['cc']); ?>
                                </td>
                                <td class="text-start text-truncate" style="max-width: 150px;" title="<?php echo htmlspecialchars($r['pdx']); ?>">
                                    <?php echo htmlspecialchars($r['pdx']); ?>
                                </td>
                                <td class="text-center"><?php echo htmlspecialchars($r['main_dep_name'] ?? '-'); ?></td>
                                <td class="text-center text-primary"><?php echo htmlspecialchars($r['cur_dep_name'] ?? '-'); ?></td>
                                <td class="text-center">
                                    <span class="badge bg-danger rounded-pill px-2"><?php echo htmlspecialchars($r['remark']); ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="10" class="text-center py-4 text-success fw-bold">
                                <i class="bi bi-check-circle fs-3 d-block mb-2"></i> ไม่พบข้อมูลผู้ป่วยนอกค้างลงผล
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
        </div> <!-- End container-fluid -->
    </div> <!-- End main-content -->
</div> <!-- End wrapper -->

<!-- Bootstrap JS -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<!-- DataTables Buttons JS -->
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>

<!-- Initialize DataTables -->
<script>
$(document).ready(function() {
    $('#opdTable').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/th.json"
        },
        "paging": false,
        "info": true,
        "searching": true,
        "order": [], // ปิดการเรียงลำดับเริ่มต้น
        "columnDefs": [
            { "orderable": false, "targets": [11] } // คอลัมน์หมายเหตุ ไม่ต้องเรียง
        ],
        "dom": "<'row mb-2'<'col-sm-12 col-md-6'B><'col-sm-12 col-md-6'f>>" +
               "<'row'<'col-sm-12'tr>>" +
               "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
        "buttons": [
            {
                extend: 'excelHtml5',
                text: '<i class="bi bi-file-earmark-excel-fill"></i> ส่งออก Excel',
                className: 'btn btn-success btn-sm',
                title: 'รายงานผู้ป่วยนอกค้างข้อมูล_' + new Date().toISOString().split('T')[0]
            },
            {
                extend: 'print',
                text: '<i class="bi bi-file-earmark-pdf-fill"></i> พิมพ์ / PDF',
                className: 'btn btn-danger btn-sm',
                title: 'รายงานผู้ป่วยนอก (OPD) ค้างข้อมูล (ไม่มี Dx / ไม่มี CC)'
            }
        ]
    });
});
</script>

<!-- Sidebar Toggle Script -->
<script>
document.getElementById('toggle-sidebar').addEventListener('click', function() {
    document.getElementById('sidebar').classList.toggle('toggled');
    if(window.innerWidth <= 768) {
        document.getElementById('sidebar').classList.toggle('show');
    }
});
</script>
</body>
</html>
