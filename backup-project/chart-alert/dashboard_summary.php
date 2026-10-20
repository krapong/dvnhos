<?php
require_once 'auth.php';
require_once 'db_config.php';
require_once 'local_db.php';

// ป้องกันไม่ให้แพทย์เข้าถึงหน้านี้
if ($is_doctor) {
    header("Location: index.php");
    exit();
}

// รับค่าช่วงวันที่จากฟอร์ม
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// --- 1. KPI Queries ---
// a. ยอดรวมค้าง, ค้างเกิน 14 วัน, จำนวนแพทย์
$sql_kpi = "
    SELECT COUNT(i.an) AS total_incomplete,
           SUM(CASE WHEN DATEDIFF(CURDATE(), i.dchdate) > 14 THEN 1 ELSE 0 END) AS overdue_14,
           COUNT(DISTINCT i.admdoctor) AS doctor_count
    FROM ipt i
    WHERE i.dchdate BETWEEN :start_date AND :end_date
      AND NOT EXISTS (SELECT 1 FROM ipt_doctor_diag id WHERE id.an = i.an AND id.diag_text IS NOT NULL AND TRIM(id.diag_text) != '')
";
$stmt_kpi = $pdo->prepare($sql_kpi);
$stmt_kpi->execute([':start_date' => $start_date, ':end_date' => $end_date]);
$kpi_data = $stmt_kpi->fetch(PDO::FETCH_ASSOC);

$total_incomplete = $kpi_data['total_incomplete'] ?: 0;
$overdue_14 = $kpi_data['overdue_14'] ?: 0;
$doctor_count = $kpi_data['doctor_count'] ?: 0;

// b. อัตราความสำเร็จ (Completion Rate)
$sql_total = "SELECT COUNT(an) FROM ipt WHERE dchdate BETWEEN :start_date AND :end_date";
$stmt_total = $pdo->prepare($sql_total);
$stmt_total->execute([':start_date' => $start_date, ':end_date' => $end_date]);
$total_discharges = $stmt_total->fetchColumn() ?: 0;

$completed_charts = $total_discharges - $total_incomplete;
$completion_rate = $total_discharges > 0 ? round(($completed_charts / $total_discharges) * 100, 2) : 0;

// --- 2. Doctor Ranking Query ---
$sql_ranking = "
    SELECT 
        d.code AS doctor_code,
        IFNULL(d.name, 'ไม่ระบุแพทย์') AS doctor_name,
        MAX(w.name) AS ward_name,
        
        SUM(CASE WHEN DATEDIFF(CURDATE(), i.dchdate) BETWEEN 1 AND 7 THEN 1 ELSE 0 END) AS days_1_7,
        SUM(CASE WHEN DATEDIFF(CURDATE(), i.dchdate) BETWEEN 8 AND 30 THEN 1 ELSE 0 END) AS days_8_30,
        SUM(CASE WHEN DATEDIFF(CURDATE(), i.dchdate) > 30 THEN 1 ELSE 0 END) AS days_over_30,
        
        SUM(CASE WHEN DATEDIFF(CURDATE(), i.dchdate) > 7 THEN 1 ELSE 0 END) AS over_7_days,
        SUM(CASE WHEN DATEDIFF(CURDATE(), i.dchdate) <= 7 THEN 1 ELSE 0 END) AS under_7_days,
        SUM(CASE WHEN w.name LIKE '%ชาย%' THEN 1 ELSE 0 END) AS pending_ward_m,
        SUM(CASE WHEN w.name LIKE '%หญิง%' THEN 1 ELSE 0 END) AS pending_ward_f,
        SUM(CASE WHEN w.name LIKE '%คลอด%' THEN 1 ELSE 0 END) AS pending_labor,
        SUM(CASE WHEN w.name LIKE '%Home%' OR w.name LIKE '%Home%' THEN 1 ELSE 0 END) AS pending_hw,
        SUM(CASE WHEN w.name LIKE '%กายภาพ%' THEN 1 ELSE 0 END) AS pending_imc,
        SUM(CASE WHEN w.name LIKE '%Refer%' THEN 1 ELSE 0 END) AS pending_refer,
        0 AS to_edit,
        
        COUNT(i.an) AS total_per_doctor
        
    FROM ipt i
    LEFT JOIN doctor d ON d.code = i.admdoctor
    LEFT JOIN ward w ON w.ward = i.ward
    WHERE i.dchdate BETWEEN :start_date AND :end_date
      AND NOT EXISTS (
          SELECT 1 
          FROM ipt_doctor_diag id 
          WHERE id.an = i.an 
            AND id.diag_text IS NOT NULL 
            AND TRIM(id.diag_text) != ''
      )
    GROUP BY d.code, d.name
    ORDER BY total_per_doctor DESC
";
$stmt_ranking = $pdo->prepare($sql_ranking);
$stmt_ranking->execute([':start_date' => $start_date, ':end_date' => $end_date]);
$rankings = $stmt_ranking->fetchAll(PDO::FETCH_ASSOC);

// Load Alert Logs from SQLite
$logs = [];
$stmt_logs = $local_pdo->query("SELECT doctor_code, MAX(notify_time) as last_notify FROM alert_logs GROUP BY doctor_code");
while ($row = $stmt_logs->fetch(PDO::FETCH_ASSOC)) {
    $logs[$row['doctor_code']] = $row['last_notify'];
}

// สำหรับ Chart.js และหาผลรวมแถวสุดท้าย
$labels_doctor = [];
$data_under_7 = [];
$data_8_30 = [];
$data_over_30 = [];
$data_total_per_doctor = [];
$totals = [
    'under_7_days' => 0, 'days_8_30' => 0, 'days_over_30' => 0,
    'pending_ward_m' => 0, 'pending_ward_f' => 0, 'pending_labor' => 0, 
    'pending_hw' => 0, 'pending_imc' => 0, 'pending_refer' => 0, 
    'to_edit' => 0, 'total' => 0
];

foreach ($rankings as $r) {
    $labels_doctor[] = $r['doctor_name'];
    $data_under_7[] = $r['under_7_days'];
    $data_8_30[] = $r['days_8_30'];
    $data_over_30[] = $r['days_over_30'];
    $data_total_per_doctor[] = $r['total_per_doctor'];
    
    $totals['under_7_days'] += $r['under_7_days'];
    $totals['days_8_30'] += $r['days_8_30'];
    $totals['days_over_30'] += $r['days_over_30'];
    $totals['pending_ward_m'] += $r['pending_ward_m'];
    $totals['pending_ward_f'] += $r['pending_ward_f'];
    $totals['pending_labor'] += $r['pending_labor'];
    $totals['pending_hw'] += $r['pending_hw'];
    $totals['pending_imc'] += $r['pending_imc'];
    $totals['pending_refer'] += $r['pending_refer'];
    $totals['to_edit'] += $r['to_edit'];
    $totals['total'] += $r['total_per_doctor'];
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard ผู้บริหาร - ระบบติดตามชาร์ต</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <!-- Custom Style -->
    <link rel="stylesheet" href="style.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .badge-1-7 { background-color: #198754; color: white; } /* 🟢 */
        .badge-8-30 { background-color: #ffc107; color: black; } /* 🟡 */
        .badge-over-30 { background-color: #dc3545; color: white; } /* 🔴 */
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
            <a href="dashboard_summary.php" class="sidebar-link active">
                <i class="bi bi-bar-chart-fill"></i> Dashboard ภาพรวม
            </a>
            <a href="disease_dashboard.php" class="sidebar-link">
                <i class="bi bi-graph-up-arrow"></i> แดชบอร์ด 10 อันดับโรค
            </a>
            <a href="index.php" class="sidebar-link">
                <i class="bi bi-card-list"></i> รายละเอียดชาร์ต (IPD)
            </a>
            <a href="opd_incomplete.php" class="sidebar-link">
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
        <!-- Top Header for Toggle Button -->
        <header class="top-header">
            <button id="toggle-sidebar" class="btn-toggle-sidebar">
                <i class="bi bi-list"></i>
            </button>
            <h5 class="m-0 ms-3 fw-bold text-primary">Dashboard สรุปภาพรวมและสถิติ</h5>
            <div class="ms-auto d-flex gap-2">
                <a href="telegram_notify.php" class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm" onclick="return confirm('ยืนยันการส่งข้อความสรุปเข้ากลุ่ม Telegram หรือไม่?');">
                    <i class="bi bi-telegram"></i> ส่งแจ้งเตือนภาพรวม
                </a>
            </div>
        </header>

        <div class="container-fluid main-container">
    
    <!-- Filter Form -->
    <form method="GET" class="filter-form d-flex flex-wrap align-items-center gap-3">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-calendar-event text-primary fs-4"></i>
            <h5 class="m-0 me-3">ตัวกรองข้อมูล</h5>
        </div>
        <div class="d-flex align-items-center gap-2">
            <label class="fw-bold mb-0">ตั้งแต่วันที่:</label>
            <input type="date" name="start_date" class="form-control form-control-sm" style="width: auto;" value="<?php echo htmlspecialchars($start_date); ?>" required>
        </div>
        <div class="d-flex align-items-center gap-2">
            <label class="fw-bold mb-0">ถึงวันที่:</label>
            <input type="date" name="end_date" class="form-control form-control-sm" style="width: auto;" value="<?php echo htmlspecialchars($end_date); ?>" required>
        </div>
        <div class="ms-auto">
            <button type="submit" class="btn btn-primary"><i class="bi bi-search me-1"></i> ค้นหา</button>
            <a href="?" class="btn btn-outline-secondary"><i class="bi bi-arrow-counterclockwise me-1"></i> รีเซ็ต</a>
        </div>
    </form>

    <!-- KPI Cards -->
    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="widget-card bg-gradient-primary">
                <div class="widget-details">
                    <p class="text-white mb-1">ชาร์ตค้างทั้งหมด</p>
                    <h3 class="text-white mb-0"><?php echo number_format($total_incomplete); ?></h3>
                </div>
                <i class="bi bi-files widget-icon"></i>
            </div>
        </div>
        <div class="col-md-3">
            <div class="widget-card bg-gradient-danger">
                <div class="widget-details">
                    <p class="text-white mb-1">ค้างเกิน 14 วัน</p>
                    <h3 class="text-white mb-0"><?php echo number_format($overdue_14); ?></h3>
                </div>
                <i class="bi bi-exclamation-triangle widget-icon"></i>
            </div>
        </div>
        <div class="col-md-3">
            <div class="widget-card bg-gradient-success">
                <div class="widget-details">
                    <p class="text-white mb-1">อัตราสรุปสำเร็จ</p>
                    <h3 class="text-white mb-0"><?php echo $completion_rate; ?>%</h3>
                </div>
                <i class="bi bi-check-circle widget-icon"></i>
            </div>
        </div>
        <div class="col-md-3">
            <div class="widget-card bg-gradient-warning">
                <div class="widget-details">
                    <p class="text-dark mb-1">แพทย์ที่ติดค้าง</p>
                    <h3 class="text-dark mb-0"><?php echo number_format($doctor_count); ?> <span class="fs-6">ท่าน</span></h3>
                </div>
                <i class="bi bi-person-lines-fill widget-icon text-dark"></i>
            </div>
        </div>
    </div>

    <!-- Chart Section -->
    <div class="row g-3 mb-3">
        <div class="col-lg-8">
            <div class="custom-card h-100">
                <h5 class="page-title text-primary"><i class="bi bi-bar-chart-line"></i> สรุปผลงานชาร์ตค้างรายแพทย์ (จำแนกตามความเร่งด่วน)</h5>
                <div style="height: 300px;">
                    <canvas id="doctorBarChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="custom-card h-100">
                <h5 class="page-title text-primary"><i class="bi bi-pie-chart-fill"></i> สัดส่วนชาร์ตค้างรายแพทย์</h5>
                <div style="height: 300px; display: flex; justify-content: center; align-items: center;">
                    <canvas id="urgencyDoughnutChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Table -->
    <div class="table-container">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="page-title mb-0"><i class="bi bi-trophy"></i> Doctor Ranking & Breakdown (สถิติรายแพทย์)</h5>
        </div>
        
        <div class="table-responsive">
            <table class="table table-hover text-center align-middle">
                <thead>
                    <tr>
                        <th width="5%">ลำดับ</th>
                        <th class="text-start" width="15%">แพทย์</th>
                        <th>&le; 7 วัน <span class="badge badge-1-7">🟢</span></th>
                        <th>8-30 วัน <span class="badge badge-8-30">🟡</span></th>
                        <th>> 30 วัน <span class="badge badge-over-30">🔴</span></th>
                        <th>ค้าง WARD ช</th>
                        <th>ค้าง WARD ญ</th>
                        <th>ค้างคลอด</th>
                        <th>HW</th>
                        <th>IMC 0 DAY</th>
                        <th>W-REFER</th>
                        <th>แก้ไข</th>
                        <th class="text-primary">รวมทั้งหมด</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($rankings) > 0): ?>
                        <?php $i = 1; foreach ($rankings as $row): ?>
                            <tr>
                                <td><?php echo $i++; ?></td>
                                <td class="text-start fw-bold">
                                    <a href="index.php?doctor_code=<?php echo urlencode($row['doctor_code']); ?>&start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>" class="text-decoration-none text-dark">
                                        <?php echo htmlspecialchars($row['doctor_name']); ?>
                                    </a>
                                </td>
                                <td><?php echo $row['under_7_days'] > 0 ? $row['under_7_days'] : '-'; ?></td>
                                <td class="<?php echo $row['days_8_30'] > 0 ? 'text-warning text-dark fw-bold' : 'text-muted'; ?>"><?php echo $row['days_8_30'] > 0 ? $row['days_8_30'] : '-'; ?></td>
                                <td class="<?php echo $row['days_over_30'] > 0 ? 'text-danger fw-bold' : 'text-muted'; ?>"><?php echo $row['days_over_30'] > 0 ? $row['days_over_30'] : '-'; ?></td>
                                <td><?php echo $row['pending_ward_m'] > 0 ? $row['pending_ward_m'] : '-'; ?></td>
                                <td><?php echo $row['pending_ward_f'] > 0 ? $row['pending_ward_f'] : '-'; ?></td>
                                <td><?php echo $row['pending_labor'] > 0 ? $row['pending_labor'] : '-'; ?></td>
                                <td><?php echo $row['pending_hw'] > 0 ? $row['pending_hw'] : '-'; ?></td>
                                <td><?php echo $row['pending_imc'] > 0 ? $row['pending_imc'] : '-'; ?></td>
                                <td><?php echo $row['pending_refer'] > 0 ? $row['pending_refer'] : '-'; ?></td>
                                <td><?php echo $row['to_edit'] > 0 ? $row['to_edit'] : '-'; ?></td>
                                <td class="text-primary fw-bold fs-6"><?php echo $row['total_per_doctor']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="12" class="py-5 text-muted"><i class="bi bi-check-circle fs-2 d-block mb-2 text-success"></i> ยอดเยี่ยม! ไม่มีชาร์ตค้างในช่วงเวลานี้</td></tr>
                    <?php endif; ?>
                </tbody>
                <?php if (count($rankings) > 0): ?>
                <tfoot>
                    <tr class="fw-bold bg-light">
                        <td colspan="2" class="text-end">รวมทั้งหมด</td>
                        <td class="text-success"><?php echo $totals['under_7_days']; ?></td>
                        <td class="text-warning text-dark"><?php echo $totals['days_8_30']; ?></td>
                        <td class="text-danger"><?php echo $totals['days_over_30']; ?></td>
                        <td><?php echo $totals['pending_ward_m']; ?></td>
                        <td><?php echo $totals['pending_ward_f']; ?></td>
                        <td><?php echo $totals['pending_labor']; ?></td>
                        <td><?php echo $totals['pending_hw']; ?></td>
                        <td><?php echo $totals['pending_imc']; ?></td>
                        <td><?php echo $totals['pending_refer']; ?></td>
                        <td><?php echo $totals['to_edit']; ?></td>
                        <td class="text-primary fs-5"><?php echo $totals['total']; ?></td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Initialize Chart.js -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    const fontOptions = { family: "'Prompt', sans-serif" };

    const ctxBar = document.getElementById('doctorBarChart');
    if(ctxBar) {
        new Chart(ctxBar, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($labels_doctor); ?>,
                datasets: [
                    {
                        label: '≤ 7 วัน (ปกติ)',
                        data: <?php echo json_encode($data_under_7); ?>,
                        backgroundColor: '#198754',
                        borderWidth: 0
                    },
                    {
                        label: '8-30 วัน (ต้องเตือน)',
                        data: <?php echo json_encode($data_8_30); ?>,
                        backgroundColor: '#ffc107',
                        borderWidth: 0
                    },
                    {
                        label: '> 30 วัน (วิกฤต)',
                        data: <?php echo json_encode($data_over_30); ?>,
                        backgroundColor: '#dc3545',
                        borderWidth: 0
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: { stacked: true, grid: { display: false } },
                    y: { stacked: true, beginAtZero: true }
                },
                plugins: {
                    legend: { position: 'top', labels: { font: fontOptions } },
                    tooltip: { titleFont: fontOptions, bodyFont: fontOptions }
                }
            }
        });
    }

    const ctxDoughnut = document.getElementById('urgencyDoughnutChart');
    if(ctxDoughnut) {
        const doctorLabels = <?php echo json_encode($labels_doctor); ?>;
        const doctorData = <?php echo json_encode($data_total_per_doctor); ?>;
        const bgColors = doctorData.map((_, i) => `hsl(${(i * 360 / Math.max(doctorData.length, 1)) % 360}, 70%, 55%)`);

        new Chart(ctxDoughnut, {
            type: 'doughnut',
            data: {
                labels: doctorLabels,
                datasets: [{
                    data: doctorData,
                    backgroundColor: bgColors,
                    borderWidth: 1,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: doctorLabels.length <= 10, position: 'bottom', labels: { font: fontOptions } },
                    tooltip: { titleFont: fontOptions, bodyFont: fontOptions }
                }
            }
        });
    }
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

    </div> <!-- End main-content wrapper (container-fluid) -->
</div> <!-- End main-content -->
</div> <!-- End wrapper -->

</body>
</html>