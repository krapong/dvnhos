<?php
require_once 'auth.php';
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$upload_success = false;
$error_msg = '';
$upload_date = '';

// Initialize SQLite connection
$local_pdo = new PDO('sqlite:local_security.sqlite');
$local_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$tabs_data = [
    'summary' => ['name' => 'สรุป', 'headers' => [], 'rows' => [], 'chart_data' => []],
    'ipd' => ['name' => 'IPD', 'headers' => [], 'rows' => []],
    'op' => ['name' => 'OP', 'headers' => [], 'rows' => []],
    'ha' => ['name' => 'HA', 'rows' => []],
    'top10_ipd' => ['name' => '10 อันดับโรค IPD', 'data' => []],
    'top10_opd' => ['name' => '10 อันดับโรค OPD', 'data' => []]
];

// Handle File Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel_file'])) {
    $file = $_FILES['excel_file'];
    if ($file['error'] === UPLOAD_ERR_OK) {
        $tmp_name = $file['tmp_name'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (in_array($ext, ['xls', 'xlsx'])) {
            try {
                $reader = IOFactory::createReader(ucfirst($ext));
                $spreadsheet = $reader->load($tmp_name);
                
                foreach ($spreadsheet->getSheetNames() as $index => $sheetName) {
                    $sheet = $spreadsheet->getSheet($index);
                    $sheetData = $sheet->toArray(null, true, true, true);
                    $cleanName = trim(strtolower($sheetName));
                    
                    if ($cleanName === 'สรุป') {
                        $started = false;
                        foreach ($sheetData as $row) {
                            if (trim((string)$row['A']) === 'ลำดับ') { 
                                $started = true; 
                                $tabs_data['summary']['headers'] = array_filter($row, function($v){return trim((string)$v) !== '';});
                                continue; 
                            }
                            if ($started && is_numeric($row['A'])) {
                                $tabs_data['summary']['rows'][] = $row;
                                $tabs_data['summary']['chart_data'][] = [
                                    'year' => trim((string)$row['B']),
                                    'people' => (int)str_replace(',', '', $row['C']),
                                    'visits' => (int)str_replace(',', '', $row['D']),
                                    'opd_visits' => (int)str_replace(',', '', $row['E']),
                                    'ipd_visits' => (int)str_replace(',', '', $row['F'])
                                ];
                            }
                        }
                    } elseif ($cleanName === 'ipd') {
                        $started = false;
                        foreach ($sheetData as $row) {
                            if (trim((string)$row['A']) === 'ลำดับ') { 
                                $started = true; 
                                $tabs_data['ipd']['headers'] = array_filter($row, function($v){return trim((string)$v) !== '';});
                                continue; 
                            }
                            if ($started && is_numeric($row['A'])) {
                                $tabs_data['ipd']['rows'][] = $row;
                            }
                        }
                    } elseif ($cleanName === 'op') {
                        $started = false;
                        foreach ($sheetData as $row) {
                            if (trim((string)$row['A']) === 'ลำดับ') { 
                                $started = true; 
                                $tabs_data['op']['headers'] = array_filter($row, function($v){return trim((string)$v) !== '';});
                                continue; 
                            }
                            if ($started && is_numeric($row['A'])) {
                                $tabs_data['op']['rows'][] = $row;
                            }
                        }
                    } elseif ($cleanName === 'ha') {
                        $started = false;
                        foreach ($sheetData as $row) {
                            if (!$started && strpos(trim((string)$row['A']), 'สถิติ') !== false) {
                                $started = true;
                            }
                            if ($started) {
                                $filtered = array_filter($row, function($v) { return trim((string)$v) !== ''; });
                                if (count($filtered) > 0) {
                                    $tabs_data['ha']['rows'][] = $row;
                                }
                            }
                        }
                    } elseif (strpos($cleanName, '10 อันโรค') !== false || strpos($cleanName, '10 อันดับโรค') !== false) {
                        $is_ipd = strpos($cleanName, 'ipd') !== false;
                        $key = $is_ipd ? 'top10_ipd' : 'top10_opd';
                        if (!isset($tabs_data[$key]['data']) || !is_array($tabs_data[$key]['data'])) {
                            $tabs_data[$key]['data'] = [];
                        }
                        
                        $current_year = 'ล่าสุด';
                        foreach ($sheetData as $row) {
                            $colA = trim((string)$row['A']);
                            
                            // Check if row contains year header
                            if (strpos($colA, '10 อันดับ') !== false && strpos($colA, 'ปี') !== false) {
                                preg_match('/ปี.*?(\d{4})/', $colA, $matches);
                                if (!empty($matches[1])) {
                                    $current_year = $matches[1];
                                }
                                if (!isset($tabs_data[$key]['data'][$current_year])) {
                                    $tabs_data[$key]['data'][$current_year] = [];
                                }
                                continue;
                            }
                            
                            if ($colA === 'ลำดับที่' || trim((string)$row['B']) === 'PDX') { continue; }
                            
                            if (is_numeric($colA) && !empty(trim((string)$row['B']))) {
                                if (!isset($tabs_data[$key]['data'][$current_year])) {
                                    $tabs_data[$key]['data'][$current_year] = [];
                                }
                                $tabs_data[$key]['data'][$current_year][] = [
                                    'rank' => $colA,
                                    'pdx' => trim((string)$row['B']),
                                    'name_en' => trim((string)$row['C']),
                                    'name_th' => trim((string)$row['D']),
                                    'people' => (int)str_replace(',', '', $row['E']),
                                    'visits' => (int)str_replace(',', '', $row['F'])
                                ];
                            }
                        }
                    }
                }
                
                // Save to database
                $stmt = $local_pdo->prepare("INSERT INTO dashboard_data (json_data) VALUES (:json)");
                $stmt->execute([':json' => json_encode($tabs_data)]);
                
                $upload_success = true;
                $upload_date = date('Y-m-d H:i:s');
                
                // Redirect to avoid form resubmission
                header("Location: disease_dashboard.php?success=1");
                exit;
            } catch (Exception $e) {
                $error_msg = 'เกิดข้อผิดพลาดในการอ่านไฟล์: ' . $e->getMessage();
            }
        } else {
            $error_msg = 'กรุณาอัปโหลดไฟล์ Excel (.xls หรือ .xlsx) เท่านั้น';
        }
    } else {
        $error_msg = 'เกิดข้อผิดพลาดในการอัปโหลดไฟล์';
    }
}

// Check if we have data in DB
if ($_SERVER['REQUEST_METHOD'] === 'GET' && !$upload_success) {
    $stmt = $local_pdo->query("SELECT * FROM dashboard_data ORDER BY id DESC LIMIT 1");
    $latest = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($latest) {
        $tabs_data = json_decode($latest['json_data'], true);
        $upload_success = true;
        $upload_date = $latest['upload_date'];
    }
}

// SVG Sparkline Generator (Smooth curves)
function renderSparkline($dataArray) {
    if (count($dataArray) < 2) return '';
    $width = 100; $height = 30;
    $min = min($dataArray); $max = max($dataArray);
    $range = $max - $min;
    if ($range == 0) $range = 1;
    
    $stepX = $width / (count($dataArray) - 1);
    $points = [];
    foreach (array_values($dataArray) as $i => $val) {
        $x = $i * $stepX;
        $y = $height - ((($val - $min) / $range) * $height);
        $y = max(2, min($height - 2, $y)); // padding
        $points[] = "$x,$y";
    }
    
    // Create smooth bezier curve path
    $path = "M " . explode(',', $points[0])[0] . " " . explode(',', $points[0])[1];
    for ($i = 1; $i < count($points); $i++) {
        $prev = explode(',', $points[$i-1]);
        $curr = explode(',', $points[$i]);
        $cp1x = $prev[0] + ($curr[0] - $prev[0]) / 2;
        $cp1y = $prev[1];
        $cp2x = $prev[0] + ($curr[0] - $prev[0]) / 2;
        $cp2y = $curr[1];
        $path .= " C $cp1x $cp1y, $cp2x $cp2y, {$curr[0]} {$curr[1]}";
    }
    
    $color = ($dataArray[count($dataArray)-1] > $dataArray[0]) ? '#e74c3c' : '#2ecc71'; // Red if up, Green if down
    $lastPoint = explode(',', end($points));
    
    return '<svg width="'.$width.'" height="'.$height.'" class="sparkline"><path d="'.$path.'" fill="none" stroke="'.$color.'" stroke-width="2.5" stroke-linecap="round"/><circle cx="'.$lastPoint[0].'" cy="'.$lastPoint[1].'" r="3.5" fill="'.$color.'"/></svg>';
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Premium Dashboard - Chart Alert</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        body { font-family: 'Prompt', sans-serif; background-color: #f4f7fa; }
        .dashboard-card { background: #fff; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); border: none; transition: transform 0.3s ease, box-shadow 0.3s ease; margin-bottom: 24px; overflow: hidden;}
        .dashboard-card:hover { transform: translateY(-5px); box-shadow: 0 15px 35px rgba(0,0,0,0.08); }
        .card-header { background: transparent; border-bottom: 1px solid rgba(0,0,0,0.05); padding: 20px; }
        .file-upload-box { border: 2px dashed #b9c2d1; border-radius: 16px; padding: 50px; text-align: center; background: rgba(255,255,255,0.5); cursor: pointer; transition: all 0.3s; }
        .file-upload-box:hover { border-color: #0d6efd; background: #fff; box-shadow: 0 10px 25px rgba(13,110,253,0.1); }
        .file-upload-box i { font-size: 3.5rem; color: #a1aab8; transition: color 0.3s; }
        .file-upload-box:hover i { color: #0d6efd; }
        .nav-pills .nav-link { border-radius: 12px; margin-right: 8px; font-weight: 500; padding: 10px 20px; color: #555; transition: all 0.3s; margin-bottom: 10px;}
        .nav-pills .nav-link:hover { background-color: #e9ecef; }
        .nav-pills .nav-link.active { background: linear-gradient(135deg, #0d6efd, #0a58ca); color: #fff; box-shadow: 0 6px 15px rgba(13, 110, 253, 0.3); }
        .table th { white-space: nowrap; font-weight: 600; color: #444; }
        .sparkline { filter: drop-shadow(0px 2px 2px rgba(0,0,0,0.1)); }
        
        /* Print Styles */
        @media print {
            .sidebar, .top-header, .nav-pills, .btn { display: none !important; }
            .main-content { margin-left: 0 !important; width: 100% !important; padding: 0 !important; }
            .dashboard-card { box-shadow: none !important; border: 1px solid #ddd; break-inside: avoid; }
            body { background: #fff; }
            .tab-pane { display: block !important; opacity: 1 !important; page-break-after: always; }
        }
    </style>
</head>
<body>
<div class="wrapper">
    <nav id="sidebar" class="sidebar">
        <div class="sidebar-header">
            <i class="bi bi-hospital fs-1 d-block mb-2 text-warning"></i>
            ระบบติดตามชาร์ต<br><span class="fs-6 fw-normal">รพ.เทพรัตนเวชชานุกูลฯ</span>
        </div>
        <div class="sidebar-menu">
            <?php if (!$is_doctor): ?>
            <a href="dashboard_summary.php" class="sidebar-link"><i class="bi bi-bar-chart-fill"></i> Dashboard ภาพรวม</a>
            <?php endif; ?>
            <a href="disease_dashboard.php" class="sidebar-link active"><i class="bi bi-graph-up-arrow"></i> แดชบอร์ด 10 อันดับโรค</a>
            <a href="index.php" class="sidebar-link"><i class="bi bi-card-list"></i> <?php echo $is_doctor ? "ชาร์ตค้างของฉัน (IPD)" : "รายละเอียดชาร์ต (IPD)"; ?></a>
            <a href="opd_incomplete.php" class="sidebar-link"><i class="bi bi-people-fill"></i> รายการผู้ป่วยนอก (OPD)</a>
            <a href="photo_dashboard.php" class="sidebar-link"><i class="bi bi-camera-fill"></i> แดชบอร์ดการเก็บรูปภาพ</a>
            <?php if ($is_admin): ?>
            <a href="settings.php" class="sidebar-link"><i class="bi bi-gear-fill"></i> การตั้งค่า (Settings)</a>
            <a href="doctor_telegram_setup.php" class="sidebar-link"><i class="bi bi-clock-history"></i> ตั้งค่ารอบการแจ้งเตือน</a>
            <?php endif; ?>
        </div>
        <div class="mt-auto p-3 border-top border-secondary">
            <div class="text-white-50 small mb-2">
                <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($_SESSION['user_name'] ?? $_SESSION['user_id'] ?? 'User'); ?>
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

    <div class="main-content" id="print-area">
        <header class="top-header d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <button id="toggle-sidebar" class="btn-toggle-sidebar"><i class="bi bi-list"></i></button>
                <h5 class="m-0 ms-3 fw-bold text-primary">Executive Dashboard</h5>
            </div>
        </header>

        <div class="container-fluid main-container pt-4">
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i> อัปโหลดและบันทึกข้อมูลสำเร็จ!
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            <?php if (!empty($error_msg)): ?>
                <div class="alert alert-danger shadow-sm"><i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error_msg; ?></div>
            <?php endif; ?>

            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                <div>
                    <h3 class="fw-bold mb-1 text-dark"><i class="bi bi-pie-chart-fill text-primary"></i> รายงานสถิติโรงพยาบาล</h3>
                    <?php if ($upload_success): ?>
                    <span class="text-muted small"><i class="bi bi-clock-history"></i> อัปเดตข้อมูลล่าสุด: <?php echo htmlspecialchars($upload_date); ?></span>
                    <?php endif; ?>
                </div>
                <div class="d-flex gap-2">
                    <?php if ($upload_success): ?>
                    <button onclick="exportToPDF()" class="btn btn-outline-danger shadow-sm rounded-pill px-3">
                        <i class="bi bi-file-earmark-pdf-fill"></i> ดาวน์โหลด PDF
                    </button>
                    <?php endif; ?>
                    <button type="button" class="btn btn-primary shadow-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#uploadModal">
                        <i class="bi bi-cloud-arrow-up-fill"></i> อัปเดตไฟล์ Excel
                    </button>
                </div>
            </div>

            <!-- Upload Modal -->
            <div class="modal fade" id="uploadModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
                        <div class="modal-header border-0 pb-0">
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-5 pt-2">
                            <h4 class="text-center mb-4 text-primary fw-bold">อัปเดตข้อมูลแดชบอร์ด</h4>
                            <form action="disease_dashboard.php" method="POST" enctype="multipart/form-data">
                                <div class="file-upload-box mb-4" onclick="document.getElementById('excel_file').click()">
                                    <i class="bi bi-file-earmark-excel"></i>
                                    <h5 class="mt-3 text-dark fw-bold">คลิกเพื่อเลือกไฟล์ .xls</h5>
                                    <p class="text-muted small">ข้อมูลจะถูกบันทึกและแสดงผลทันที</p>
                                </div>
                                <input type="file" name="excel_file" id="excel_file" class="d-none" accept=".xls, .xlsx" onchange="document.getElementById('fileName').textContent = this.files[0].name; document.getElementById('submitBtn').classList.remove('d-none');">
                                <div class="text-center mb-3 text-primary fw-bold" id="fileName"></div>
                                <div class="text-center">
                                    <button type="submit" class="btn btn-primary px-5 py-2 rounded-pill shadow d-none w-100" id="submitBtn">ประมวลผลข้อมูล <i class="bi bi-arrow-right"></i></button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (!$upload_success): ?>
                <div class="alert alert-info text-center shadow-sm" style="border-radius: 16px; padding: 40px;">
                    <i class="bi bi-info-circle fs-1 d-block mb-3 text-primary"></i>
                    <h5 class="fw-bold">ยังไม่มีข้อมูลในระบบ</h5>
                    <p class="text-muted">กรุณาอัปโหลดไฟล์ Excel เพื่อเริ่มต้นใช้งานแดชบอร์ด</p>
                    <button type="button" class="btn btn-primary rounded-pill px-4 mt-2" data-bs-toggle="modal" data-bs-target="#uploadModal">อัปโหลดไฟล์แรก</button>
                </div>
            <?php else: ?>

                <!-- Tabs Menu -->
                <ul class="nav nav-pills mb-4" id="dashboardTabs" role="tablist">
                    <li class="nav-item"><button class="nav-link active" data-bs-toggle="pill" data-bs-target="#tab-sum" type="button"><i class="bi bi-graph-up"></i> ภาพรวม (Summary)</button></li>
                    <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-ha" type="button"><i class="bi bi-activity"></i> สถิติบริการ (HA) <span class="badge bg-danger ms-1">Trends</span></button></li>
                    <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-topopd" type="button"><i class="bi bi-list-ol"></i> 10 อันดับโรค OPD</button></li>
                    <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-topipd" type="button"><i class="bi bi-list-ol"></i> 10 อันดับโรค IPD</button></li>
                    <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-op" type="button"><i class="bi bi-people"></i> ข้อมูลผู้ป่วยนอก (OP)</button></li>
                    <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-ipd" type="button"><i class="bi bi-hospital"></i> ข้อมูลผู้ป่วยใน (IPD)</button></li>
                </ul>

                <div class="tab-content" id="dashboardTabsContent">
                    
                    <!-- TAB 1: สรุป -->
                    <div class="tab-pane fade show active" id="tab-sum" role="tabpanel">
                        <div class="row">
                            <div class="col-lg-7 mb-4">
                                <div class="card dashboard-card h-100">
                                    <div class="card-header"><h5 class="mb-0 fw-bold text-primary">อัตราส่วนคนไข้และครั้งที่รับบริการ</h5></div>
                                    <div class="card-body" style="height: 350px; position: relative;">
                                        <canvas id="summaryBarChart"></canvas>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-5 mb-4">
                                <div class="card dashboard-card h-100">
                                    <div class="card-header"><h5 class="mb-0 fw-bold text-success">สัดส่วนผู้ป่วยนอก / ผู้ป่วยใน</h5></div>
                                    <div class="card-body d-flex justify-content-center" style="height: 350px; position: relative;">
                                        <canvas id="summaryDoughnutChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card dashboard-card">
                            <div class="card-header"><h5 class="mb-0 fw-bold text-dark"><i class="bi bi-table"></i> ตารางสรุปภาพรวม</h5></div>
                            <div class="card-body p-0">
                                <?php renderGenericTable($tabs_data['summary']['headers'], $tabs_data['summary']['rows']); ?>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 4: HA -->
                    <div class="tab-pane fade" id="tab-ha" role="tabpanel">
                        <div class="card dashboard-card">
                            <div class="card-header"><h5 class="mb-0 fw-bold text-dark"><i class="bi bi-graph-up-arrow text-danger"></i> สถิติผู้มารับบริการย้อนหลัง พร้อมแนวโน้ม (Trends)</h5></div>
                            <div class="card-body p-0">
                                <?php if(empty($tabs_data['ha']['rows'])): ?>
                                    <div class="alert alert-warning m-3">ไม่พบข้อมูล</div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover table-bordered align-middle mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th class="py-3 text-secondary text-center" style="width: 5%;">ลำดับ</th>
                                                    <th class="py-3 text-secondary">ชื่อโรค/หัตถการ</th>
                                                    <th class="text-end py-3 text-secondary">2569 (มิ.ย.)</th>
                                                    <th class="text-end py-3 text-secondary">2568</th>
                                                    <th class="text-end py-3 text-secondary">2567</th>
                                                    <th class="text-end py-3 text-secondary">2566</th>
                                                    <th class="text-end py-3 text-secondary">2565</th>
                                                    <th class="text-center py-3 text-secondary">แนวโน้ม (Trend)</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach($tabs_data['ha']['rows'] as $r): 
                                                    $name = trim((string)($r['C'] ?? ''));
                                                    if (empty($name) || $name === 'ชื่อโรค/หัตถการ') continue;
                                                    
                                                    $cat = trim((string)($r['A'] ?? ''));
                                                    if (!empty($cat) && !is_numeric($cat) && strpos($cat, 'สถิติ') === false) {
                                                        echo "<tr><td colspan='8' class='bg-light fw-bold text-primary'>".htmlspecialchars($cat)."</td></tr>";
                                                    }
                                                    
                                                    $rank = trim((string)($r['B'] ?? ''));
                                                    $d = trim((string)($r['D'] ?? ''));
                                                    $e = trim((string)($r['E'] ?? ''));
                                                    $f = trim((string)($r['F'] ?? ''));
                                                    $g = trim((string)($r['G'] ?? ''));
                                                    $h = trim((string)($r['H'] ?? ''));
                                                    
                                                    if (empty($d) && empty($e) && empty($f) && empty($g) && empty($h)) continue; 
                                                    
                                                    $trendData = [];
                                                    $years = [$h, $g, $f, $e, $d];
                                                    $hasValidData = false;
                                                    foreach ($years as $val) {
                                                        $clean = str_replace(',', '', $val);
                                                        if (is_numeric($clean) && $clean !== '') {
                                                            $trendData[] = (float)$clean;
                                                            $hasValidData = true;
                                                        } else {
                                                            $trendData[] = count($trendData) > 0 ? end($trendData) : 0;
                                                        }
                                                    }
                                                ?>
                                                <tr>
                                                    <td class="text-center text-muted"><?php echo htmlspecialchars($rank); ?></td>
                                                    <td class="fw-bold text-dark"><?php echo htmlspecialchars($name); ?></td>
                                                    <td class="text-end text-primary fw-bold"><?php echo htmlspecialchars($d); ?></td>
                                                    <td class="text-end"><?php echo htmlspecialchars($e); ?></td>
                                                    <td class="text-end"><?php echo htmlspecialchars($f); ?></td>
                                                    <td class="text-end"><?php echo htmlspecialchars($g); ?></td>
                                                    <td class="text-end"><?php echo htmlspecialchars($h); ?></td>
                                                    <td class="text-center align-middle" style="width: 140px; padding: 5px;">
                                                        <?php echo $hasValidData ? renderSparkline($trendData) : ''; ?>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 6: 10 อันดับโรค OPD -->
                    <div class="tab-pane fade" id="tab-topopd" role="tabpanel">
                        <?php 
                        $opdYears = array_keys($tabs_data['top10_opd']['data'] ?? []);
                        rsort($opdYears);
                        if (!empty($opdYears)): 
                        ?>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="fw-bold text-dark m-0"><i class="bi bi-list-ol text-primary"></i> 10 อันดับโรคผู้ป่วยนอก (OPD)</h4>
                            <div class="d-flex align-items-center bg-white rounded-pill shadow-sm px-3 py-1">
                                <i class="bi bi-calendar-event text-primary me-2"></i>
                                <select class="form-select form-select-sm border-0 fw-bold text-dark" id="yearSelectOpd" onchange="changeTop10Year('opd', this.value)" style="box-shadow: none; cursor: pointer; min-width: 120px;">
                                    <?php foreach($opdYears as $y): ?>
                                        <option value="<?php echo htmlspecialchars($y); ?>">ปีงบประมาณ <?php echo htmlspecialchars($y); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-6 mb-4">
                                <div class="card dashboard-card"><div class="card-body" style="height:350px"><canvas id="opdPeopleChart"></canvas></div></div>
                            </div>
                            <div class="col-lg-6 mb-4">
                                <div class="card dashboard-card"><div class="card-body" style="height:350px"><canvas id="opdVisitsChart"></canvas></div></div>
                            </div>
                        </div>
                        <div class="card dashboard-card">
                            <div class="card-body p-0">
                                <?php foreach($opdYears as $idx => $y): ?>
                                <div id="table-opd-<?php echo htmlspecialchars($y); ?>" class="top10-table-opd" style="<?php echo $idx === 0 ? '' : 'display:none;'; ?>">
                                    <?php renderTop10Table($tabs_data['top10_opd']['data'][$y]); ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php else: ?>
                            <div class="alert alert-warning">ไม่พบข้อมูล</div>
                        <?php endif; ?>
                    </div>

                    <!-- TAB 5: 10 อันดับโรค IPD -->
                    <div class="tab-pane fade" id="tab-topipd" role="tabpanel">
                        <?php 
                        $ipdYears = array_keys($tabs_data['top10_ipd']['data'] ?? []);
                        rsort($ipdYears);
                        if (!empty($ipdYears)): 
                        ?>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="fw-bold text-dark m-0"><i class="bi bi-list-ol text-primary"></i> 10 อันดับโรคผู้ป่วยใน (IPD)</h4>
                            <div class="d-flex align-items-center bg-white rounded-pill shadow-sm px-3 py-1">
                                <i class="bi bi-calendar-event text-primary me-2"></i>
                                <select class="form-select form-select-sm border-0 fw-bold text-dark" id="yearSelectIpd" onchange="changeTop10Year('ipd', this.value)" style="box-shadow: none; cursor: pointer; min-width: 120px;">
                                    <?php foreach($ipdYears as $y): ?>
                                        <option value="<?php echo htmlspecialchars($y); ?>">ปีงบประมาณ <?php echo htmlspecialchars($y); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-6 mb-4">
                                <div class="card dashboard-card"><div class="card-body" style="height:350px"><canvas id="ipdPeopleChart"></canvas></div></div>
                            </div>
                            <div class="col-lg-6 mb-4">
                                <div class="card dashboard-card"><div class="card-body" style="height:350px"><canvas id="ipdVisitsChart"></canvas></div></div>
                            </div>
                        </div>
                        <div class="card dashboard-card">
                            <div class="card-body p-0">
                                <?php foreach($ipdYears as $idx => $y): ?>
                                <div id="table-ipd-<?php echo htmlspecialchars($y); ?>" class="top10-table-ipd" style="<?php echo $idx === 0 ? '' : 'display:none;'; ?>">
                                    <?php renderTop10Table($tabs_data['top10_ipd']['data'][$y]); ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php else: ?>
                            <div class="alert alert-warning">ไม่พบข้อมูล</div>
                        <?php endif; ?>
                    </div>

                    <!-- TAB 3: OP -->
                    <div class="tab-pane fade" id="tab-op" role="tabpanel">
                        <?php 
                        $opYears = [];
                        if (isset($tabs_data['op']['headers'])) {
                            foreach (['C','D','E','F','G'] as $col) {
                                $yStr = trim($tabs_data['op']['headers'][$col] ?? '');
                                if (!empty($yStr)) {
                                    // Extract 4 digit year or use as is
                                    preg_match('/(25\d{2})/', $yStr, $m);
                                    $opYears[$col] = !empty($m[1]) ? $m[1] : $yStr;
                                }
                            }
                        }
                        $opYearList = array_values($opYears);
                        rsort($opYearList);
                        ?>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="fw-bold text-dark m-0"><i class="bi bi-people text-primary"></i> ข้อมูลผู้ป่วยนอก (OP)</h4>
                            <?php if(!empty($opYears)): ?>
                            <div class="d-flex align-items-center bg-white rounded-pill shadow-sm px-3 py-1">
                                <i class="bi bi-calendar-event text-primary me-2"></i>
                                <select class="form-select form-select-sm border-0 fw-bold text-dark" id="opYearSelect" onchange="changeOpYear(this.value)" style="box-shadow: none; cursor: pointer; min-width: 120px;">
                                    <option value="all">ดูทุกปี (ภาพรวม)</option>
                                    <?php 
                                    // Sort by year descending but keep key
                                    $sortedOpYears = $opYears;
                                    arsort($sortedOpYears);
                                    foreach($sortedOpYears as $col => $y): 
                                    ?>
                                        <option value="<?php echo htmlspecialchars($col); ?>">ปี <?php echo htmlspecialchars($y); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="row">
                            <div class="col-lg-8 mb-4">
                                <div class="card dashboard-card h-100">
                                    <div class="card-header"><h5 class="mb-0 fw-bold text-primary"><i class="bi bi-graph-up"></i> แนวโน้มการรับบริการผู้ป่วยนอก (OPD) ย้อนหลัง</h5></div>
                                    <div class="card-body" style="height: 350px; position: relative;">
                                        <canvas id="opTrendChart"></canvas>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-4 mb-4">
                                <div class="card dashboard-card h-100">
                                    <div class="card-header"><h5 class="mb-0 fw-bold text-success"><i class="bi bi-pie-chart-fill"></i> สัดส่วนสิทธิ์การรักษา <span id="opDoughnutTitle">(ทุกปี)</span></h5></div>
                                    <div class="card-body d-flex justify-content-center" style="height: 350px; position: relative;">
                                        <canvas id="opDoughnutChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card dashboard-card">
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover table-striped align-middle mb-0" id="opDataTable">
                                        <thead class="table-light">
                                            <tr>
                                                <?php 
                                                $headers = $tabs_data['op']['headers'] ?? [];
                                                foreach ($headers as $k => $v) {
                                                    $isYear = in_array($k, ['C','D','E','F','G']);
                                                    echo '<th class="py-3 text-secondary op-col op-col-'.($isYear ? $k : 'main').'">' . htmlspecialchars($v) . '</th>';
                                                }
                                                ?>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($tabs_data['op']['rows'] ?? [] as $row): ?>
                                            <tr>
                                                <?php foreach ($headers as $k => $v): 
                                                    $val = isset($row[$k]) ? trim((string)$row[$k]) : '';
                                                    $isYear = in_array($k, ['C','D','E','F','G']);
                                                ?>
                                                    <td class="op-col op-col-<?php echo $isYear ? $k : 'main'; ?>"><?php echo htmlspecialchars($val); ?></td>
                                                <?php endforeach; ?>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- TAB 2: IPD -->
                    <div class="tab-pane fade" id="tab-ipd" role="tabpanel">
                        <?php 
                        $ipdSummaryYears = [];
                        foreach ($tabs_data['ipd']['rows'] ?? [] as $r) {
                            $yStr = trim((string)($r['B'] ?? ''));
                            if (!empty($yStr)) {
                                preg_match('/(25\d{2})/', $yStr, $m);
                                $y = !empty($m[1]) ? $m[1] : $yStr;
                                $ipdSummaryYears[] = $y;
                            }
                        }
                        $ipdSummaryYears = array_unique($ipdSummaryYears);
                        rsort($ipdSummaryYears);
                        ?>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="fw-bold text-dark m-0"><i class="bi bi-hospital text-primary"></i> ข้อมูลผู้ป่วยใน (IPD)</h4>
                            <?php if(!empty($ipdSummaryYears)): ?>
                            <div class="d-flex align-items-center bg-white rounded-pill shadow-sm px-3 py-1">
                                <i class="bi bi-calendar-event text-primary me-2"></i>
                                <select class="form-select form-select-sm border-0 fw-bold text-dark" id="ipdYearSelect" onchange="changeIpdYear(this.value)" style="box-shadow: none; cursor: pointer; min-width: 120px;">
                                    <option value="all">ดูทุกปี (ภาพรวม)</option>
                                    <?php foreach($ipdSummaryYears as $y): ?>
                                        <option value="<?php echo htmlspecialchars($y); ?>">ปี <?php echo htmlspecialchars($y); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="row">
                            <div class="col-lg-6 mb-4">
                                <div class="card dashboard-card h-100">
                                    <div class="card-header"><h5 class="mb-0 fw-bold text-primary"><i class="bi bi-bar-chart-fill"></i> จำนวนคน / ครั้ง ผู้ป่วยใน</h5></div>
                                    <div class="card-body" style="height: 350px; position: relative;">
                                        <canvas id="ipdSummaryBarChart"></canvas>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-6 mb-4">
                                <div class="card dashboard-card h-100">
                                    <div class="card-header"><h5 class="mb-0 fw-bold text-danger"><i class="bi bi-graph-up-arrow"></i> อัตราครองเตียง (%) และ CMI</h5></div>
                                    <div class="card-body" style="height: 350px; position: relative;">
                                        <canvas id="ipdMetricsLineChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card dashboard-card">
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover table-striped align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <?php foreach ($tabs_data['ipd']['headers'] ?? [] as $v) echo '<th class="py-3 text-secondary">' . htmlspecialchars($v) . '</th>'; ?>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($tabs_data['ipd']['rows'] ?? [] as $row): 
                                                $yStr = trim((string)($row['B'] ?? ''));
                                                preg_match('/(25\d{2})/', $yStr, $m);
                                                $y = !empty($m[1]) ? $m[1] : $yStr;
                                            ?>
                                            <tr class="ipd-row ipd-row-<?php echo htmlspecialchars($y); ?>">
                                                <?php foreach (array_keys($tabs_data['ipd']['headers']) as $k) {
                                                    $val = isset($row[$k]) ? trim((string)$row[$k]) : '';
                                                    echo '<td>' . htmlspecialchars($val) . '</td>';
                                                } ?>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <script>
                    Chart.defaults.font.family = "'Prompt', sans-serif";
                    
                    function exportToPDF() {
                        const element = document.getElementById('print-area');
                        const opt = {
                            margin:       0.2,
                            filename:     'hospital_dashboard.pdf',
                            image:        { type: 'jpeg', quality: 0.98 },
                            html2canvas:  { scale: 2, useCORS: true },
                            jsPDF:        { unit: 'in', format: 'a4', orientation: 'landscape' }
                        };
                        const tabs = document.querySelectorAll('.tab-pane');
                        tabs.forEach(t => { t.classList.add('show', 'active'); });
                        html2pdf().set(opt).from(element).save().then(() => {
                            tabs.forEach(t => { t.classList.remove('show', 'active'); });
                            document.querySelector('.tab-pane.fade').classList.add('show', 'active');
                            location.reload(); 
                        });
                    }

                    // --- Charts for Tab 1 (Summary) ---
                    <?php 
                    $sumYears = []; $sumPeople = []; $sumVisits = []; $sumOpd = 0; $sumIpd = 0;
                    if (isset($tabs_data['summary']['chart_data'])) {
                        foreach ($tabs_data['summary']['chart_data'] as $cd) {
                            $sumYears[] = $cd['year'] ?: 'ปี';
                            $sumPeople[] = $cd['people'];
                            $sumVisits[] = $cd['visits'];
                            $sumOpd += $cd['opd_visits'];
                            $sumIpd += $cd['ipd_visits'];
                        }
                    }
                    ?>
                    const sumYears = <?php echo json_encode($sumYears); ?>;
                    if (sumYears.length > 0) {
                        new Chart(document.getElementById('summaryBarChart'), {
                            type: 'bar',
                            data: {
                                labels: sumYears,
                                datasets: [
                                    { label: 'จำนวน (คน)', data: <?php echo json_encode($sumPeople); ?>, backgroundColor: '#0d6efd', borderRadius: 6 },
                                    { label: 'จำนวน (ครั้ง)', data: <?php echo json_encode($sumVisits); ?>, backgroundColor: '#fd7e14', borderRadius: 6 }
                                ]
                            },
                            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'top' } } }
                        });
                        new Chart(document.getElementById('summaryDoughnutChart'), {
                            type: 'doughnut',
                            data: {
                                labels: ['ผู้ป่วยนอก (OPD)', 'ผู้ป่วยใน (IPD)'],
                                datasets: [{
                                    data: [<?php echo $sumOpd; ?>, <?php echo $sumIpd; ?>],
                                    backgroundColor: ['#20c997', '#dc3545'], borderWidth: 0, hoverOffset: 10
                                }]
                            },
                            options: { responsive: true, maintainAspectRatio: false, cutout: '70%', plugins: { legend: { position: 'bottom' } } }
                        });
                    }

                    // --- Charts for Tab 5 and 6 (Top 10) Multi-Year ---
                    <?php
                    function getChartDataByYear($yearsData) {
                        $result = [];
                        foreach ($yearsData as $year => $list) {
                            $labels = []; $people = []; $visits = [];
                            foreach ($list as $r) {
                                $short = mb_substr($r['pdx'] . ' ' . (empty($r['name_th']) ? explode(',', $r['name_en'])[0] : $r['name_th']), 0, 30);
                                $labels[] = $short; $people[] = $r['people']; $visits[] = $r['visits'];
                            }
                            $result[$year] = ['labels' => $labels, 'people' => $people, 'visits' => $visits];
                        }
                        return $result;
                    }
                    $top10OpdDataJS = getChartDataByYear($tabs_data['top10_opd']['data'] ?? []);
                    $top10IpdDataJS = getChartDataByYear($tabs_data['top10_ipd']['data'] ?? []);
                    ?>
                    const top10Data = {
                        'opd': <?php echo json_encode($top10OpdDataJS); ?>,
                        'ipd': <?php echo json_encode($top10IpdDataJS); ?>
                    };
                    
                    let charts = {};

                    function createHorizontalBarChart(ctxId, labels, data, label, color) {
                        return new Chart(document.getElementById(ctxId), {
                            type: 'bar',
                            data: { labels: labels, datasets: [{ label: label, data: data, backgroundColor: color, borderRadius: 6 }] },
                            options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
                        });
                    }
                    
                    // Init charts with first year
                    const initialOpdYear = <?php echo !empty($opdYears) ? json_encode($opdYears[0]) : 'null'; ?>;
                    if (initialOpdYear && top10Data['opd'][initialOpdYear]) {
                        charts['opdPeople'] = createHorizontalBarChart('opdPeopleChart', top10Data['opd'][initialOpdYear].labels, top10Data['opd'][initialOpdYear].people, 'จำนวนผู้ป่วย (คน)', '#0dcaf0');
                        charts['opdVisits'] = createHorizontalBarChart('opdVisitsChart', top10Data['opd'][initialOpdYear].labels, top10Data['opd'][initialOpdYear].visits, 'จำนวนรับบริการ (ครั้ง)', '#6610f2');
                    }
                    
                    const initialTopIpdYear = <?php 
                        $top10IpdYears = array_keys($tabs_data['top10_ipd']['data'] ?? []);
                        rsort($top10IpdYears);
                        echo !empty($top10IpdYears) ? json_encode($top10IpdYears[0]) : 'null'; 
                    ?>;
                    if (initialTopIpdYear && top10Data['ipd'][initialTopIpdYear]) {
                        charts['ipdPeople'] = createHorizontalBarChart('ipdPeopleChart', top10Data['ipd'][initialTopIpdYear].labels, top10Data['ipd'][initialTopIpdYear].people, 'จำนวนผู้ป่วย (คน)', '#ffc107');
                        charts['ipdVisits'] = createHorizontalBarChart('ipdVisitsChart', top10Data['ipd'][initialTopIpdYear].labels, top10Data['ipd'][initialTopIpdYear].visits, 'จำนวนรับบริการ (ครั้ง)', '#d63384');
                    }

                    function changeTop10Year(type, year) {
                        // Toggle tables
                        document.querySelectorAll('.top10-table-' + type).forEach(el => el.style.display = 'none');
                        document.getElementById('table-' + type + '-' + year).style.display = 'block';
                        
                        // Update charts
                        if (top10Data[type] && top10Data[type][year]) {
                            const data = top10Data[type][year];
                            charts[type + 'People'].data.labels = data.labels;
                            charts[type + 'People'].data.datasets[0].data = data.people;
                            charts[type + 'People'].update();
                            
                            charts[type + 'Visits'].data.labels = data.labels;
                            charts[type + 'Visits'].data.datasets[0].data = data.visits;
                            charts[type + 'Visits'].update();
                        }
                    }

                    // --- Charts for OP Tab ---
                    <?php
                    $opYearsJS = array_values($opYears);
                    $opColToYear = $opYears; // map like 'G' => '69 (มิ.ย.69)'
                    $colors = ['#0d6efd', '#20c997', '#ffc107', '#dc3545', '#6f42c1', '#fd7e14', '#0dcaf0', '#d63384']; 
                    
                    $opProcessedRows = [];
                    foreach ($tabs_data['op']['rows'] as $r) {
                        $label = trim((string)($r['B'] ?? ''));
                        if (empty($label)) continue;
                        
                        // Clean up long labels
                        $label = preg_replace('/จำนวน.*?ผู้ป่วยนอก\s*\(?.*?\)?\s*ของ\s*/u', '', $label);
                        $label = str_replace(['จำนวนครั้งของผู้ป่วยนอก', 'จำนวนผู้ป่วยนอก(Patient Day)', 'ผู้ป่วยนอกทั้งหมดของรพ.', 'จำนวนผู้ป่วยนอก', '/เดือน'], '', $label);
                        $label = trim($label);
                        if (empty($label)) $label = 'ทั่วไป';
                        // Shorten if still too long
                        if (mb_strlen($label) > 35) $label = mb_substr($label, 0, 35) . '...';
                        
                        $dataByYear = [];
                        $total = 0;
                        foreach (['C','D','E','F','G'] as $col) {
                            $y = $opYears[$col] ?? '';
                            if ($y) {
                                $val = isset($r[$col]) ? (int)str_replace(',', '', $r[$col]) : 0;
                                $dataByYear[$y] = $val;
                                $total += $val;
                            }
                        }
                        if ($total > 0) {
                            $opProcessedRows[] = ['label' => $label, 'data' => $dataByYear, 'total' => $total];
                        }
                    }
                    usort($opProcessedRows, function($a, $b) { return $b['total'] <=> $a['total']; });
                    ?>
                    const opRawData = <?php echo json_encode($opProcessedRows); ?>;
                    const opYearsList = <?php echo json_encode($opYearsJS); ?>;
                    const opColMap = <?php echo json_encode($opColToYear); ?>;
                    const chartColors = <?php echo json_encode($colors); ?>;
                    
                    let opTrendChart, opDoughnutChart;

                    function initOpCharts() {
                        if (opYearsList.length === 0 || opRawData.length === 0) return;
                        
                        // Prepare Trend (Bar) datasets for 'all' years
                        let barDatasets = [];
                        for(let i=0; i<Math.min(7, opRawData.length); i++) {
                            let dataArr = opYearsList.map(y => opRawData[i].data[y] || 0);
                            barDatasets.push({
                                label: opRawData[i].label,
                                data: dataArr,
                                backgroundColor: chartColors[i % chartColors.length],
                                borderRadius: 4
                            });
                        }
                        
                        opTrendChart = new Chart(document.getElementById('opTrendChart'), {
                            type: 'bar', 
                            data: { labels: opYearsList, datasets: barDatasets },
                            options: { 
                                responsive: true, maintainAspectRatio: false, 
                                plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: {size: 11} } } },
                                scales: { x: { stacked: true }, y: { stacked: true } }
                            }
                        });

                        // Prepare Doughnut dataset for 'all' years (total sum)
                        updateOpDoughnut('all');
                    }
                    
                    function updateOpDoughnut(year) {
                        if(!opDoughnutChart) {
                            opDoughnutChart = new Chart(document.getElementById('opDoughnutChart'), {
                                type: 'doughnut',
                                data: { labels: [], datasets: [{ data: [], backgroundColor: [], borderWidth: 0, hoverOffset: 10 }] },
                                options: { responsive: true, maintainAspectRatio: false, cutout: '65%', plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: {size: 11} } } } }
                            });
                        }
                        
                        let labels = [];
                        let data = [];
                        let otherSum = 0;
                        
                        // Re-sort data based on the selected year
                        let sortedData = [...opRawData].map(pr => {
                            let val = (year === 'all') ? pr.total : (pr.data[year] || 0);
                            return { label: pr.label, val: val };
                        }).sort((a,b) => b.val - a.val);

                        sortedData.forEach((item, idx) => {
                            if (idx < 5) {
                                labels.push(item.label);
                                data.push(item.val);
                            } else {
                                otherSum += item.val;
                            }
                        });
                        
                        if (otherSum > 0) {
                            labels.push('อื่นๆ (Others)');
                            data.push(otherSum);
                        }
                        
                        opDoughnutChart.data.labels = labels;
                        opDoughnutChart.data.datasets[0].data = data;
                        opDoughnutChart.data.datasets[0].backgroundColor = chartColors.slice(0, labels.length);
                        opDoughnutChart.update();
                        
                        document.getElementById('opDoughnutTitle').innerText = year === 'all' ? '(รวมทุกปี)' : '(ปี ' + year + ')';
                    }

                    function changeOpYear(colKey) {
                        let yearStr = (colKey === 'all') ? 'all' : opColMap[colKey];
                        
                        // Toggle Table Columns
                        document.querySelectorAll('.op-col').forEach(el => {
                            if (colKey === 'all' || el.classList.contains('op-col-main') || el.classList.contains('op-col-' + colKey)) {
                                el.style.display = '';
                            } else {
                                el.style.display = 'none';
                            }
                        });
                        
                        // Update Doughnut Chart
                        updateOpDoughnut(yearStr);
                        
                        // Filter Bar Chart
                        if (opTrendChart) {
                            if (yearStr === 'all') {
                                opTrendChart.data.labels = opYearsList;
                                opTrendChart.data.datasets.forEach((ds, i) => {
                                    ds.data = opYearsList.map(y => opRawData[i].data[y] || 0);
                                });
                            } else {
                                opTrendChart.data.labels = [yearStr];
                                opTrendChart.data.datasets.forEach((ds, i) => {
                                    ds.data = [opRawData[i].data[yearStr] || 0];
                                });
                            }
                            opTrendChart.update();
                        }
                    }

                    // --- Charts for IPD Tab ---
                    <?php
                    $ipdRaw = [];
                    foreach ($tabs_data['ipd']['rows'] ?? [] as $r) {
                        $yStr = trim((string)($r['B'] ?? 'ปี'));
                        preg_match('/(25\d{2})/', $yStr, $m);
                        $y = !empty($m[1]) ? $m[1] : $yStr;
                        $ipdRaw[] = [
                            'year' => $y,
                            'people' => (int)str_replace(',', '', $r['C'] ?? '0'),
                            'visits' => (int)str_replace(',', '', $r['D'] ?? '0'),
                            'bed' => (float)str_replace(',', '', $r['H'] ?? '0'),
                            'cmi' => (float)str_replace(',', '', $r['J'] ?? '0')
                        ];
                    }
                    ?>
                    const ipdRawData = <?php echo json_encode($ipdRaw); ?>;
                    let ipdBarChart, ipdLineChart;

                    function initIpdCharts() {
                        if (ipdRawData.length === 0) return;
                        let labels = ipdRawData.map(d => d.year);
                        let people = ipdRawData.map(d => d.people);
                        let visits = ipdRawData.map(d => d.visits);
                        let bed = ipdRawData.map(d => d.bed);
                        let cmi = ipdRawData.map(d => d.cmi);

                        ipdBarChart = new Chart(document.getElementById('ipdSummaryBarChart'), {
                            type: 'bar',
                            data: {
                                labels: labels,
                                datasets: [
                                    { label: 'ผู้ป่วย (คน)', data: people, backgroundColor: '#0dcaf0', borderRadius: 4 },
                                    { label: 'รับบริการ (ครั้ง)', data: visits, backgroundColor: '#6f42c1', borderRadius: 4 }
                                ]
                            },
                            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'top' } } }
                        });
                        ipdLineChart = new Chart(document.getElementById('ipdMetricsLineChart'), {
                            type: 'line',
                            data: {
                                labels: labels,
                                datasets: [
                                    { label: 'อัตราครองเตียง (%)', data: bed, borderColor: '#dc3545', backgroundColor: '#dc3545', yAxisID: 'y', tension: 0.2 },
                                    { label: 'CMI', data: cmi, borderColor: '#ffc107', backgroundColor: '#ffc107', yAxisID: 'y1', tension: 0.2 }
                                ]
                            },
                            options: { 
                                responsive: true, maintainAspectRatio: false, 
                                scales: {
                                    y: { type: 'linear', display: true, position: 'left', title: {display: true, text: 'อัตราครองเตียง (%)'} },
                                    y1: { type: 'linear', display: true, position: 'right', grid: { drawOnChartArea: false }, title: {display: true, text: 'CMI'} }
                                } 
                            }
                        });
                    }

                    function changeIpdYear(year) {
                        // Toggle Table Rows
                        document.querySelectorAll('.ipd-row').forEach(el => {
                            if (year === 'all' || el.classList.contains('ipd-row-' + year)) {
                                el.style.display = '';
                            } else {
                                el.style.display = 'none';
                            }
                        });
                        
                        // Filter Charts
                        if (ipdBarChart && ipdLineChart) {
                            let filtered = (year === 'all') ? ipdRawData : ipdRawData.filter(d => d.year === year);
                            let labels = filtered.map(d => d.year);
                            
                            ipdBarChart.data.labels = labels;
                            ipdBarChart.data.datasets[0].data = filtered.map(d => d.people);
                            ipdBarChart.data.datasets[1].data = filtered.map(d => d.visits);
                            ipdBarChart.update();
                            
                            ipdLineChart.data.labels = labels;
                            ipdLineChart.data.datasets[0].data = filtered.map(d => d.bed);
                            ipdLineChart.data.datasets[1].data = filtered.map(d => d.cmi);
                            ipdLineChart.update();
                        }
                    }

                    // Initialize all
                    initOpCharts();
                    initIpdCharts();
                </script>
            <?php endif; ?>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('toggle-sidebar').addEventListener('click', function() {
    document.getElementById('sidebar').classList.toggle('toggled');
});
</script>
</body>
</html>
<?php 
function renderGenericTable($headers, $rows) {
    if (empty($rows)) { echo "<div class='alert alert-warning m-3'>ไม่พบข้อมูลในตาราง</div>"; return; }
    echo '<div class="table-responsive"><table class="table table-hover table-striped align-middle mb-0">';
    echo '<thead class="table-light"><tr>';
    $cols = array_keys($headers);
    foreach ($cols as $c) echo '<th class="py-3 text-secondary">' . htmlspecialchars($headers[$c]) . '</th>';
    echo '</tr></thead><tbody>';
    foreach ($rows as $row) {
        echo '<tr>';
        foreach ($cols as $c) {
            $val = isset($row[$c]) ? trim((string)$row[$c]) : '';
            echo '<td>' . htmlspecialchars($val) . '</td>';
        }
        echo '</tr>';
    }
    echo '</tbody></table></div>';
}

function renderTop10Table($list) {
    if (empty($list)) { echo "<div class='alert alert-warning m-3'>ไม่พบข้อมูล</div>"; return; }
    echo '<div class="table-responsive"><table class="table table-hover table-striped align-middle mb-0">';
    echo '<thead class="table-light"><tr><th class="text-center py-3 text-secondary">ลำดับ</th><th class="py-3 text-secondary">PDX</th><th class="py-3 text-secondary">ชื่อโรค (EN)</th><th class="py-3 text-secondary">ชื่อโรค (TH)</th><th class="text-end py-3 text-secondary">จำนวน (คน)</th><th class="text-end py-3 text-secondary">รับบริการ (ครั้ง)</th></tr></thead><tbody>';
    foreach ($list as $row) {
        echo "<tr>";
        echo "<td class='text-center'><span class='badge bg-light text-dark border px-2 py-1 fs-6'>{$row['rank']}</span></td>";
        echo "<td class='fw-bold text-danger'>" . htmlspecialchars($row['pdx']) . "</td>";
        echo "<td>" . htmlspecialchars($row['name_en']) . "</td>";
        echo "<td>" . htmlspecialchars($row['name_th']) . "</td>";
        echo "<td class='text-end fw-bold text-primary'>" . number_format($row['people']) . "</td>";
        echo "<td class='text-end fw-bold text-success'>" . number_format($row['visits']) . "</td>";
        echo "</tr>";
    }
    echo '</tbody></table></div>';
}
?>
