<?php
require_once 'auth.php';
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

// Setup SQLite database for photo dashboard
$db = new PDO('sqlite:local_security.sqlite');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->exec("CREATE TABLE IF NOT EXISTS photo_dashboard_data (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    json_data TEXT,
    upload_date DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$upload_success = false;
$error_msg = '';
$dashboard_data = null;
$upload_date = '';

// Handle File Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel_file'])) {
    $file = $_FILES['excel_file'];
    if ($file['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['xls', 'xlsx'])) {
            try {
                // For better memory usage, we use reader
                ini_set('memory_limit', '-1');
                
                $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($file['tmp_name']);
                $reader->setReadDataOnly(true);
                $spreadsheet = $reader->load($file['tmp_name']);
                
                $sheet = $spreadsheet->getSheetByName('export');
                if (!$sheet) {
                    $sheet = $spreadsheet->getActiveSheet(); // fallback
                }

                $highestRow = $sheet->getHighestRow();
                
                $totalCount = 0;
                $yCount = 0;
                $nCount = 0;
                $rows = [];
                
                // Assuming headers are on row 1
                for ($row = 2; $row <= $highestRow; $row++) {
                    $hn = trim((string)$sheet->getCell('B' . $row)->getValue());
                    $hasPhoto = trim(strtoupper((string)$sheet->getCell('D' . $row)->getValue()));
                    $datetime = trim((string)$sheet->getCell('E' . $row)->getValue());
                    $name = trim((string)$sheet->getCell('F' . $row)->getValue());
                    $gender = trim((string)$sheet->getCell('G' . $row)->getValue());
                    
                    if (empty($hn) && empty($name)) continue;
                    
                    if ($hasPhoto === 'Y') {
                        $yCount++;
                    } else {
                        $nCount++; // Treat anything else as N
                        $hasPhoto = 'N';
                    }
                    $totalCount++;
                    
                    $rows[] = [
                        'hn' => $hn,
                        'name' => $name,
                        'hasPhoto' => $hasPhoto,
                        'gender' => $gender
                    ];
                }
                
                $data = [
                    'summary' => [
                        'total' => $totalCount,
                        'y' => $yCount,
                        'n' => $nCount
                    ],
                    'rows' => $rows
                ];
                
                // Save to DB
                $stmt = $db->prepare("INSERT INTO photo_dashboard_data (json_data) VALUES (:json)");
                $stmt->execute([':json' => json_encode($data, JSON_UNESCAPED_UNICODE)]);
                
                header("Location: photo_dashboard.php?success=1");
                exit;
            } catch (Exception $e) {
                $error_msg = "เกิดข้อผิดพลาดในการอ่านไฟล์: " . $e->getMessage();
            }
        } else {
            $error_msg = "กรุณาอัปโหลดไฟล์ Excel (.xls หรือ .xlsx) เท่านั้น";
        }
    } else {
        $upload_error_code = $file['error'];
        $error_messages = [
            UPLOAD_ERR_INI_SIZE   => 'ขนาดไฟล์ใหญ่เกินกว่าที่เซิร์ฟเวอร์กำหนด (upload_max_filesize)',
            UPLOAD_ERR_FORM_SIZE  => 'ขนาดไฟล์ใหญ่เกินกว่าที่ฟอร์มกำหนด',
            UPLOAD_ERR_PARTIAL    => 'อัปโหลดไฟล์ได้เพียงบางส่วน (การเชื่อมต่ออาจขัดข้อง)',
            UPLOAD_ERR_NO_FILE    => 'ไม่มีไฟล์ถูกอัปโหลด กรุณาเลือกไฟล์',
            UPLOAD_ERR_NO_TMP_DIR => 'ไม่พบโฟลเดอร์ชั่วคราว (tmp) บนเซิร์ฟเวอร์',
            UPLOAD_ERR_CANT_WRITE => 'ไม่สามารถเขียนไฟล์ลงดิสก์ได้บนเซิร์ฟเวอร์',
            UPLOAD_ERR_EXTENSION  => 'เซิร์ฟเวอร์ปฏิเสธการอัปโหลดไฟล์นามสกุลนี้'
        ];
        $specific_err = $error_messages[$upload_error_code] ?? "รหัสข้อผิดพลาด: $upload_error_code";
        $error_msg = "เกิดข้อผิดพลาดในการอัปโหลดไฟล์: " . $specific_err;
    }
}

// Load data from DB
$stmt = $db->query("SELECT json_data, upload_date FROM photo_dashboard_data ORDER BY id DESC LIMIT 1");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if ($row) {
    $dashboard_data = json_decode($row['json_data'], true);
    $upload_date = date('Y-m-d H:i:s', strtotime($row['upload_date']));
    $upload_success = true;
}

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แดชบอร์ดเก็บรูปภาพผู้รับบริการ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        body { font-family: 'Prompt', sans-serif; background-color: #f4f7f6; }
        .dashboard-card { border: none; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); transition: transform 0.2s; overflow: hidden; }
        .dashboard-card:hover { transform: translateY(-5px); }
        .card-header { background: #fff; border-bottom: 1px solid rgba(0,0,0,0.05); padding: 20px; }
        .stat-card { border-radius: 16px; padding: 25px; color: white; display: flex; align-items: center; justify-content: space-between; }
        .stat-card .icon-box { font-size: 2.5rem; opacity: 0.8; }
        .stat-card .value { font-size: 2rem; font-weight: bold; margin: 0; }
        .stat-card .label { font-size: 1rem; opacity: 0.9; margin: 0; }
        .bg-gradient-primary { background: linear-gradient(45deg, #4e73df, #224abe); }
        .bg-gradient-success { background: linear-gradient(45deg, #1cc88a, #13855c); }
        .bg-gradient-danger { background: linear-gradient(45deg, #e74a3b, #be2617); }
        
        .file-upload-box {
            border: 2px dashed #0d6efd; border-radius: 16px; padding: 40px 20px;
            text-align: center; cursor: pointer; background-color: #f8fbff; transition: all 0.3s ease;
        }
        .file-upload-box:hover { background-color: #e9f2ff; border-color: #0a58ca; }
        .file-upload-box i { font-size: 4rem; color: #0d6efd; }
    </style>
</head>
<body>
<div class="wrapper">
    <!-- Sidebar -->
    <nav id="sidebar" class="sidebar">
        <div class="sidebar-header">
            <i class="bi bi-hospital fs-1 d-block mb-2 text-warning"></i>
            ระบบติดตามชาร์ต<br><span class="fs-6 fw-normal">รพ.เทพรัตนเวชชานุกูลฯ</span>
        </div>
        <div class="sidebar-menu">
            <?php if (!$is_doctor): ?>
            <a href="dashboard_summary.php" class="sidebar-link"><i class="bi bi-bar-chart-fill"></i> Dashboard ภาพรวม</a>
            <?php endif; ?>
            <a href="disease_dashboard.php" class="sidebar-link"><i class="bi bi-graph-up-arrow"></i> แดชบอร์ด 10 อันดับโรค</a>
            <a href="index.php" class="sidebar-link"><i class="bi bi-card-list"></i> <?php echo $is_doctor ? "ชาร์ตค้างของฉัน (IPD)" : "รายละเอียดชาร์ต (IPD)"; ?></a>
            <a href="opd_incomplete.php" class="sidebar-link"><i class="bi bi-people-fill"></i> รายการผู้ป่วยนอก (OPD)</a>
            <a href="photo_dashboard.php" class="sidebar-link active"><i class="bi bi-camera-fill"></i> แดชบอร์ดการเก็บรูปภาพ</a>
            <?php if ($is_admin): ?>
            <a href="settings.php" class="sidebar-link"><i class="bi bi-gear-fill"></i> การตั้งค่า (Settings)</a>
            <a href="doctor_telegram_setup.php" class="sidebar-link"><i class="bi bi-clock-history"></i> ตั้งค่ารอบการแจ้งเตือน</a>
            <?php endif; ?>
        </div>
        <div class="mt-auto p-3 border-top border-secondary">
            <div class="text-white-50 small mb-2">
                <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($_SESSION['user_name'] ?? $_SESSION['user_id'] ?? 'User'); ?>
            </div>
            <a href="logout.php" class="btn btn-outline-light btn-sm w-100 text-start">
                <i class="bi bi-box-arrow-left"></i> ออกจากระบบ
            </a>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <header class="top-header">
            <button id="toggle-sidebar" class="btn-toggle-sidebar"><i class="bi bi-list"></i></button>
            <h5 class="m-0 ms-3 fw-bold text-primary">แดชบอร์ดผลการดำเนินงาน</h5>
        </header>

        <div class="container-fluid main-container" id="print-area">
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i> อัปโหลดและประมวลผลสำเร็จ!
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            <?php if (!empty($error_msg)): ?>
                <div class="alert alert-danger shadow-sm"><i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error_msg; ?></div>
            <?php endif; ?>

            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                <div>
                    <h3 class="fw-bold mb-1 text-dark"><i class="bi bi-camera-fill text-primary"></i> ข้อมูลการเก็บรูปภาพผู้มารับบริการ</h3>
                    <?php if ($upload_success): ?>
                    <span class="text-muted small"><i class="bi bi-clock-history"></i> ข้อมูลล่าสุดเมื่อ: <?php echo htmlspecialchars($upload_date); ?></span>
                    <?php endif; ?>
                </div>
                <div>
                    <button type="button" class="btn btn-primary shadow-sm rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#uploadModal">
                        <i class="bi bi-cloud-arrow-up-fill"></i> อัปโหลดไฟล์ Excel ใหม่
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
                            <h4 class="text-center mb-4 text-primary fw-bold">นำเข้าไฟล์ Excel ข้อมูลรูปภาพ</h4>
                            <form action="photo_dashboard.php" method="POST" enctype="multipart/form-data">
                                <div class="file-upload-box mb-4" onclick="document.getElementById('excel_file').click()">
                                    <i class="bi bi-file-earmark-excel"></i>
                                    <h5 class="mt-3 text-dark fw-bold">คลิกเพื่อเลือกไฟล์</h5>
                                    <p class="text-muted small">อ่านข้อมูลจาก Sheet "export" คอลัมน์ D (มีรูปภาพ)</p>
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
                    <p class="text-muted">กรุณาอัปโหลดไฟล์ Excel เพื่อแสดงแดชบอร์ดการเก็บรูปภาพ</p>
                </div>
            <?php else: 
                $sum = $dashboard_data['summary'];
                $yPercent = $sum['total'] > 0 ? round(($sum['y'] / $sum['total']) * 100, 1) : 0;
            ?>
                <!-- Stats Row -->
                <div class="row mb-4">
                    <div class="col-md-4 mb-3">
                        <div class="stat-card bg-gradient-primary shadow-sm h-100">
                            <div>
                                <p class="label">ผู้มารับบริการทั้งหมด</p>
                                <h3 class="value"><?php echo number_format($sum['total']); ?> <span class="fs-6 fw-normal">คน</span></h3>
                            </div>
                            <div class="icon-box"><i class="bi bi-people-fill"></i></div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="stat-card bg-gradient-success shadow-sm h-100">
                            <div>
                                <p class="label">มีรูปภาพในระบบ (Y)</p>
                                <h3 class="value"><?php echo number_format($sum['y']); ?> <span class="fs-6 fw-normal">คน (<?php echo $yPercent; ?>%)</span></h3>
                            </div>
                            <div class="icon-box"><i class="bi bi-camera-fill"></i></div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="stat-card bg-gradient-danger shadow-sm h-100">
                            <div>
                                <p class="label">ไม่มีรูปภาพ (N)</p>
                                <h3 class="value"><?php echo number_format($sum['n']); ?> <span class="fs-6 fw-normal">คน</span></h3>
                            </div>
                            <div class="icon-box"><i class="bi bi-person-x-fill"></i></div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Chart -->
                    <div class="col-lg-5 mb-4">
                        <div class="card dashboard-card h-100">
                            <div class="card-header"><h5 class="mb-0 fw-bold text-dark"><i class="bi bi-pie-chart-fill text-primary"></i> สัดส่วนการเก็บรูปภาพ</h5></div>
                            <div class="card-body d-flex justify-content-center" style="height: 350px; position: relative;">
                                <canvas id="photoChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <!-- Data Table -->
                    <div class="col-lg-7 mb-4">
                        <div class="card dashboard-card h-100">
                            <div class="card-header border-0 pb-0">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-table text-info"></i> ข้อมูลผู้มารับบริการล่าสุด</h5>
                                    <span class="badge bg-light text-dark border" id="rowCountBadge">กำลังโหลด...</span>
                                </div>
                                <div class="row g-2 mb-3">
                                    <div class="col-md-7">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                                            <input type="text" class="form-control border-start-0 ps-0" id="searchInput" placeholder="ค้นหา HN หรือ ชื่อ-สกุล...">
                                        </div>
                                    </div>
                                    <div class="col-md-5">
                                        <select class="form-select form-select-sm text-secondary" id="statusFilter">
                                            <option value="all">สถานะทั้งหมด</option>
                                            <option value="Y">✅ มีรูปภาพแล้ว</option>
                                            <option value="N">❌ ยังไม่มีรูปภาพ</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body p-0" style="max-height: 450px; overflow-y: auto;">
                                <table class="table table-hover align-middle mb-0 text-center" id="photoTable">
                                    <thead class="table-light sticky-top">
                                        <tr>
                                            <th style="width: 10%;">ลำดับ</th>
                                            <th style="width: 20%;">HN</th>
                                            <th class="text-start" style="width: 45%;">ชื่อ-สกุล</th>
                                            <th style="width: 25%;">รูปภาพ</th>
                                        </tr>
                                    </thead>
                                    <tbody id="photoTableBody">
                                        <!-- Rendered by JS -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <script>
                    Chart.defaults.font.family = "'Prompt', sans-serif";
                    
                    // Chart
                    new Chart(document.getElementById('photoChart'), {
                        type: 'doughnut',
                        data: {
                            labels: ['มีรูปภาพ (Y)', 'ไม่มีรูปภาพ (N)'],
                            datasets: [{
                                data: [<?php echo $sum['y']; ?>, <?php echo $sum['n']; ?>],
                                backgroundColor: ['#1cc88a', '#e74a3b'],
                                borderWidth: 0, hoverOffset: 10
                            }]
                        },
                        options: { 
                            responsive: true, maintainAspectRatio: false, cutout: '65%', 
                            plugins: { 
                                legend: { position: 'bottom', labels: { padding: 20, font: {size: 14} } } 
                            } 
                        }
                    });

                    // Filtering Logic
                    const rawData = <?php echo json_encode($dashboard_data['rows'] ?? [], JSON_UNESCAPED_UNICODE); ?>;
                    const tbody = document.getElementById('photoTableBody');
                    const searchInput = document.getElementById('searchInput');
                    const statusFilter = document.getElementById('statusFilter');
                    const rowCountBadge = document.getElementById('rowCountBadge');

                    function renderTable() {
                        const searchTerm = searchInput.value.toLowerCase().trim();
                        const statusVal = statusFilter.value;
                        
                        let filtered = rawData.filter(r => {
                            const matchSearch = r.hn.toLowerCase().includes(searchTerm) || r.name.toLowerCase().includes(searchTerm);
                            const matchStatus = (statusVal === 'all') || (r.hasPhoto === statusVal);
                            return matchSearch && matchStatus;
                        });
                        
                        // Render
                        tbody.innerHTML = '';
                        if (filtered.length === 0) {
                            tbody.innerHTML = '<tr><td colspan="4" class="py-4 text-muted"><i class="bi bi-inbox fs-4 d-block mb-2"></i>ไม่พบข้อมูลที่ค้นหา</td></tr>';
                        } else {
                            // Limit to 200 rows to prevent browser freeze if too many
                            const displayData = filtered.slice(0, 200);
                            let html = '';
                            displayData.forEach((r, idx) => {
                                const badge = r.hasPhoto === 'Y' 
                                    ? '<span class="badge bg-success rounded-pill px-3"><i class="bi bi-check-circle"></i> มีรูป</span>'
                                    : '<span class="badge bg-danger rounded-pill px-3"><i class="bi bi-x-circle"></i> ไม่มี</span>';
                                html += `
                                    <tr>
                                        <td class="text-muted">${idx + 1}</td>
                                        <td>${r.hn}</td>
                                        <td class="text-start">${r.name}</td>
                                        <td>${badge}</td>
                                    </tr>
                                `;
                            });
                            tbody.innerHTML = html;
                        }
                        
                        rowCountBadge.innerText = `แสดง ${Math.min(filtered.length, 200)} จาก ${filtered.length} รายการ`;
                    }

                    searchInput.addEventListener('input', renderTable);
                    statusFilter.addEventListener('change', renderTable);
                    
                    // Initial Render
                    renderTable();
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
