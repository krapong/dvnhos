<?php
require_once 'auth.php';
require_once 'db_config.php';

if (!$is_admin) {
    die("Access Denied: คุณไม่มีสิทธิ์เข้าถึงหน้านี้");
}
require_once 'local_db.php';

$success_msg = '';
$error_msg = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add_schedule') {
            $notify_time = trim($_POST['notify_time'] ?? '');
            $notify_type = trim($_POST['notify_type'] ?? '');
            if (!empty($notify_time) && !empty($notify_type)) {
                try {
                    $stmt = $local_pdo->prepare("INSERT INTO notify_schedule (notify_time, notify_type, is_active) VALUES (:time, :type, 1)");
                    $stmt->execute([':time' => $notify_time, ':type' => $notify_type]);
                    $success_msg = 'เพิ่มรอบการแจ้งเตือนเรียบร้อยแล้ว';
                } catch (PDOException $e) {
                    $error_msg = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
                }
            }
        } elseif ($_POST['action'] === 'toggle_schedule') {
            $id = $_POST['schedule_id'];
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            $stmt = $local_pdo->prepare("UPDATE notify_schedule SET is_active = :is_active WHERE id = :id");
            $stmt->execute([':is_active' => $is_active, ':id' => $id]);
            $success_msg = 'อัปเดตสถานะเรียบร้อยแล้ว';
        } elseif ($_POST['action'] === 'delete_schedule') {
            $id = $_POST['schedule_id'];
            $stmt = $local_pdo->prepare("DELETE FROM notify_schedule WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $success_msg = 'ลบรอบการแจ้งเตือนเรียบร้อยแล้ว';
        } elseif ($_POST['action'] === 'update_chat_id') {
            $doctor_code = trim($_POST['doctor_code'] ?? '');
            $chat_id = trim($_POST['chat_id'] ?? '');
            if (!empty($doctor_code)) {
                try {
                    if (empty($chat_id)) {
                        $stmt = $local_pdo->prepare("DELETE FROM doctor_telegram WHERE doctor_code = :doctor_code");
                        $stmt->execute([':doctor_code' => $doctor_code]);
                        $success_msg = 'ลบการตั้งค่า Chat ID เรียบร้อยแล้ว';
                    } else {
                        $stmt = $local_pdo->prepare("
                            INSERT INTO doctor_telegram (doctor_code, chat_id, updated_at) 
                            VALUES (:doctor_code, :chat_id, CURRENT_TIMESTAMP)
                            ON CONFLICT(doctor_code) DO UPDATE SET 
                                chat_id = excluded.chat_id, 
                                updated_at = CURRENT_TIMESTAMP
                        ");
                        $stmt->execute([':doctor_code' => $doctor_code, ':chat_id' => $chat_id]);
                        $success_msg = 'บันทึกการตั้งค่า Chat ID เรียบร้อยแล้ว';
                    }
                } catch (PDOException $e) {
                    $error_msg = 'เกิดข้อผิดพลาดในการบันทึก: ' . $e->getMessage();
                }
            }
        }
    }
}

// Fetch Schedules
$stmt_sch = $local_pdo->query("SELECT * FROM notify_schedule ORDER BY notify_time ASC");
$schedules = $stmt_sch->fetchAll(PDO::FETCH_ASSOC);

// Fetch doctors from MySQL
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sql = "SELECT code, name FROM doctor WHERE code IN ('0517', '0384', '0472', '0413', '0388', '0523', '0518')";
if (!empty($search)) {
    $sql .= " AND (name LIKE :search OR code LIKE :search)";
}
$sql .= " ORDER BY name";

$stmt = $pdo->prepare($sql);
if (!empty($search)) {
    $search_param = "%{$search}%";
    $stmt->bindParam(':search', $search_param);
}
$stmt->execute();
$doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch chat IDs from SQLite
$stmt = $local_pdo->query("SELECT doctor_code, chat_id, updated_at FROM doctor_telegram");
$telegram_configs = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $telegram_configs[$row['doctor_code']] = $row;
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตั้งค่ารอบการแจ้งเตือน - ระบบติดตามสรุปชาร์ต</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <!-- Custom Style -->
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
            <a href="dashboard_summary.php" class="sidebar-link">
                <i class="bi bi-bar-chart-fill"></i> Dashboard ภาพรวม
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
            <a href="doctor_telegram_setup.php" class="sidebar-link active">
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
            <h5 class="m-0 ms-3 fw-bold text-primary">ตั้งค่าการแจ้งเตือน</h5>
        </header>

        <div class="container-fluid main-container">
            <div class="row">
                <div class="col-md-10 mx-auto">
                    
                    <?php if ($success_msg): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bi bi-check-circle-fill me-2"></i> <?php echo $success_msg; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error_msg): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error_msg; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Schedule Settings Card -->
                    <div class="custom-card mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-clock-history text-primary" style="font-size: 2.5rem; margin-right: 15px;"></i>
                                <div>
                                    <h4 class="mb-0 fw-bold">ตั้งค่ารอบเวลาแจ้งเตือนอัตโนมัติ</h4>
                                    <p class="text-muted mb-0 small">กำหนดเวลาให้ระบบส่งแจ้งเตือนเข้า Telegram โดยอัตโนมัติ</p>
                                </div>
                            </div>
                        </div>

                        <!-- Add Schedule Form -->
                        <form method="POST" class="row g-3 align-items-end mb-4 bg-light p-3 rounded border">
                            <input type="hidden" name="action" value="add_schedule">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">เวลาที่ต้องการส่ง (เช่น 08:30)</label>
                                <input type="time" name="notify_time" class="form-control" required>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label fw-bold">รูปแบบการแจ้งเตือน</label>
                                <select name="notify_type" class="form-select" required>
                                    <option value="group">ส่งสรุปภาพรวมเข้า "กลุ่ม" (Group)</option>
                                    <option value="personal">ส่งรายการค้างส่วนตัวหา "แพทย์รายบุคคล"</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-plus-circle"></i> เพิ่มรอบเวลา
                                </button>
                            </div>
                        </form>

                        <!-- Schedule List -->
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>เวลาแจ้งเตือน</th>
                                        <th>รูปแบบการแจ้งเตือน</th>
                                        <th>สถานะ (เปิด/ปิด)</th>
                                        <th>ลบ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($schedules) > 0): ?>
                                        <?php foreach ($schedules as $sch): ?>
                                            <tr>
                                                <td class="fw-bold fs-5 text-primary"><i class="bi bi-clock"></i> <?php echo htmlspecialchars($sch['notify_time']); ?> น.</td>
                                                <td>
                                                    <?php if ($sch['notify_type'] == 'group'): ?>
                                                        <span class="badge bg-info text-dark"><i class="bi bi-people-fill"></i> ภาพรวมกลุ่ม</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning text-dark"><i class="bi bi-person-fill"></i> แจ้งแพทย์รายบุคคล</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <form method="POST" class="d-inline" onchange="this.submit()">
                                                        <input type="hidden" name="action" value="toggle_schedule">
                                                        <input type="hidden" name="schedule_id" value="<?php echo $sch['id']; ?>">
                                                        <div class="form-check form-switch">
                                                            <input class="form-check-input" type="checkbox" name="is_active" style="width: 40px; height: 20px;" <?php echo $sch['is_active'] ? 'checked' : ''; ?>>
                                                        </div>
                                                    </form>
                                                </td>
                                                <td>
                                                    <form method="POST" class="d-inline" onsubmit="return confirm('ยืนยันการลบรอบเวลานี้?');">
                                                        <input type="hidden" name="action" value="delete_schedule">
                                                        <input type="hidden" name="schedule_id" value="<?php echo $sch['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center py-4 text-muted">ยังไม่ได้ตั้งเวลารอบการแจ้งเตือน</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div> <!-- End Schedule Card -->

                    <!-- Chat ID Card -->
                    <div class="custom-card">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-person-lines-fill text-primary" style="font-size: 2.5rem; margin-right: 15px;"></i>
                                <div>
                                    <h4 class="mb-0 fw-bold">จัดการ Telegram Chat ID แพทย์</h4>
                                    <p class="text-muted mb-0 small">จับคู่รหัสแพทย์กับ Chat ID เพื่อส่งข้อความแจ้งเตือนชาร์ตค้างส่วนตัว</p>
                                </div>
                            </div>
                            
                            <!-- Search Form -->
                            <form method="GET" class="d-flex">
                                <input type="text" name="search" class="form-control form-control-sm me-2" placeholder="ค้นหาชื่อแพทย์หรือรหัส" value="<?php echo htmlspecialchars($search); ?>">
                                <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-search"></i></button>
                                <?php if($search): ?>
                                    <a href="doctor_telegram_setup.php" class="btn btn-outline-secondary btn-sm ms-1"><i class="bi bi-x"></i></a>
                                <?php endif; ?>
                            </form>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th width="10%">รหัสแพทย์</th>
                                        <th width="35%">ชื่อแพทย์</th>
                                        <th width="30%">Telegram Chat ID</th>
                                        <th width="15%">อัปเดตล่าสุด</th>
                                        <th width="10%">จัดการ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($doctors) > 0): ?>
                                        <?php foreach ($doctors as $doc): ?>
                                            <?php 
                                                $code = $doc['code'];
                                                $chat_id = $telegram_configs[$code]['chat_id'] ?? '';
                                                $updated_at = $telegram_configs[$code]['updated_at'] ?? '-';
                                            ?>
                                            <tr>
                                                <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($code); ?></span></td>
                                                <td class="fw-bold"><?php echo htmlspecialchars($doc['name']); ?></td>
                                                <td>
                                                    <form method="POST" class="d-flex align-items-center" id="form-<?php echo $code; ?>">
                                                        <input type="hidden" name="action" value="update_chat_id">
                                                        <input type="hidden" name="doctor_code" value="<?php echo htmlspecialchars($code); ?>">
                                                        <input type="text" name="chat_id" class="form-control form-control-sm" value="<?php echo htmlspecialchars($chat_id); ?>" placeholder="ยังไม่ตั้งค่า">
                                                    </form>
                                                </td>
                                                <td class="small text-muted"><?php echo htmlspecialchars($updated_at); ?></td>
                                                <td>
                                                    <button type="submit" form="form-<?php echo $code; ?>" class="btn btn-sm btn-<?php echo $chat_id ? 'success' : 'primary'; ?>">
                                                        <i class="bi bi-save"></i> บันทึก
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-4 text-muted">ไม่พบข้อมูลแพทย์</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Sidebar Toggle Script -->
<script>
document.getElementById('toggle-sidebar').addEventListener('click', function() {
    document.getElementById('sidebar').classList.toggle('toggled');
    if(window.innerWidth <= 768) {
        document.getElementById('sidebar').classList.toggle('show');
    }
});
</script>

    </div> <!-- End main-content -->
</div> <!-- End wrapper -->

</body>
</html>
