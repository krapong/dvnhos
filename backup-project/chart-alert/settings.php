<?php
require_once 'auth.php';
require_once 'db_config.php';
require_once 'local_db.php';

if (!$is_admin) {
    die("Access Denied: คุณไม่มีสิทธิ์เข้าถึงหน้านี้");
}

$config_file = 'telegram_config.json';
$success_msg = '';
$error_msg = '';

// หากมีการส่งฟอร์มเพื่อบันทึกการตั้งค่า
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bot_token = trim($_POST['bot_token'] ?? '');
    $chat_id = trim($_POST['chat_id'] ?? '');

    $config_data = [
        'bot_token' => $bot_token,
        'chat_id' => $chat_id
    ];

    if (file_put_contents($config_file, json_encode($config_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
        $success_msg = 'บันทึกการตั้งค่า Telegram เรียบร้อยแล้ว';
    } else {
        $error_msg = 'ไม่สามารถบันทึกไฟล์การตั้งค่าได้ กรุณาตรวจสอบสิทธิ์ของโฟลเดอร์';
    }
}

// อ่านค่าปัจจุบันมาแสดงในฟอร์ม
$current_token = '';
$current_chat_id = '';
if (file_exists($config_file)) {
    $config_content = file_get_contents($config_file);
    $config = json_decode($config_content, true);
    if (is_array($config)) {
        $current_token = $config['bot_token'] ?? '';
        $current_chat_id = $config['chat_id'] ?? '';
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตั้งค่าระบบ - ระบบติดตามสรุปชาร์ต</title>
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
            <a href="settings.php" class="sidebar-link active">
                <i class="bi bi-gear-fill"></i> การตั้งค่า (Settings)
            </a>
            <a href="doctor_telegram_setup.php" class="sidebar-link">
                <i class="bi bi-clock-history"></i> ตั้งค่ารอบการแจ้งเตือน
            </a>
            <?php endif; ?>
        </div>
        
        <!-- ส่วนแสดงชื่อผู้ใช้งานและปุ่ม Logout -->
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
        <!-- Top Header -->
        <header class="top-header">
            <button id="toggle-sidebar" class="btn-toggle-sidebar">
                <i class="bi bi-list"></i>
            </button>
            <h5 class="m-0 ms-3 fw-bold text-primary">การตั้งค่าระบบแจ้งเตือน (Telegram)</h5>
        </header>

        <div class="container-fluid main-container">
            <div class="row">
                <div class="col-md-8 mx-auto">
                    <div class="custom-card">
                        <div class="d-flex align-items-center mb-4">
                            <i class="bi bi-telegram text-primary" style="font-size: 2.5rem; margin-right: 15px;"></i>
                            <div>
                                <h4 class="mb-0 fw-bold">การเชื่อมต่อ Telegram Bot</h4>
                                <p class="text-muted mb-0 small">ระบุข้อมูลบอทเพื่อให้ระบบส่งข้อความสรุปชาร์ตเข้ากลุ่มอัตโนมัติ</p>
                            </div>
                        </div>

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

                        <form method="POST" action="settings.php">
                            <div class="mb-4">
                                <label for="bot_token" class="form-label fw-bold">Bot Token (ได้จาก @BotFather)</label>
                                <input type="text" class="form-control" id="bot_token" name="bot_token" value="<?php echo htmlspecialchars($current_token); ?>" placeholder="ตัวอย่าง: 123456789:ABCdefGHIjklmNOPqrstUVWxyz" required>
                            </div>
                            
                            <div class="mb-4">
                                <label for="chat_id" class="form-label fw-bold">Chat ID (กลุ่ม หรือ แชทส่วนตัว)</label>
                                <input type="text" class="form-control" id="chat_id" name="chat_id" value="<?php echo htmlspecialchars($current_chat_id); ?>" placeholder="ตัวอย่าง: -100123456789" required>
                                <div class="form-text">สามารถหา Chat ID ของกลุ่มได้โดยดึงบอทเข้ากลุ่ม แล้วไปที่ <code>https://api.telegram.org/bot[TOKEN_ของบอท]/getUpdates</code></div>
                            </div>
                            
                            <hr class="my-4">
                            
                            <div class="text-end">
                                <button type="submit" class="btn btn-primary px-4"><i class="bi bi-save me-2"></i>บันทึกการตั้งค่า</button>
                            </div>
                        </form>
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
