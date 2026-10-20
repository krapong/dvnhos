<?php
session_start();
require_once 'local_db.php';
require_once 'GoogleAuthenticator.php';

// เช็กว่าผู้ใช้ผ่านการล็อกอินขั้นแรกมาแล้วหรือยัง
if (!isset($_SESSION['temp_mfa_user_id'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['temp_mfa_user_id'];
$user_name_display = $_SESSION['temp_mfa_user_name'] ?? $username;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['mfa_code'] ?? '');
    
    if (empty($code)) {
        $error = 'กรุณากรอกรหัส 6 หลัก';
    } else {
        // ดึง Secret Key จาก Database
        $stmt = $local_pdo->prepare("SELECT secret_key FROM user_mfa WHERE username = :username AND is_enabled = 1");
        $stmt->execute([':username' => $username]);
        $mfa_data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($mfa_data) {
            $ga = new GoogleAuthenticator();
            $checkResult = $ga->verifyCode($mfa_data['secret_key'], $code, 2);
            
            if ($checkResult) {
                // รหัสถูกต้อง เข้าสู่ระบบได้
                $_SESSION['user_id'] = $username;
                $_SESSION['user_name'] = $user_name_display;
                $_SESSION['mfa_verified'] = true;
                $_SESSION['is_admin'] = $_SESSION['temp_is_admin'] ?? false;
                $_SESSION['is_manager'] = $_SESSION['temp_is_manager'] ?? false;
                
                // ล้าง session ชั่วคราว
                unset($_SESSION['temp_mfa_user_id']);
                unset($_SESSION['temp_mfa_user_name']);
                unset($_SESSION['temp_is_admin']);
                unset($_SESSION['temp_is_manager']);
                
                header("Location: dashboard_summary.php");
                exit();
            } else {
                $error = 'รหัสไม่ถูกต้อง กรุณาลองใหม่อีกครั้ง';
            }
        } else {
            $error = 'ไม่พบข้อมูลการตั้งค่า MFA กรุณาติดต่อผู้ดูแลระบบ';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ยืนยันตัวตน - ระบบติดตามชาร์ต</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
</head>
<body class="login-bg">
<div class="container d-flex justify-content-center align-items-center min-vh-100">
    <div class="login-card p-5 custom-card text-center" style="max-width: 450px; width: 100%;">
        <div class="mb-4">
            <i class="bi bi-shield-check text-success" style="font-size: 3rem;"></i>
            <h4 class="mt-3 fw-bold text-dark">ยืนยันตัวตน 2 ขั้นตอน</h4>
            <p class="text-muted">กรุณากรอกรหัสจาก Google Authenticator</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><i class="bi bi-exclamation-circle-fill me-2"></i><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="verify_mfa.php">
            <div class="mb-4">
                <input type="text" class="form-control text-center fs-4 ls-wider" id="mfa_code" name="mfa_code" maxlength="6" pattern="[0-9]{6}" placeholder="------" required autofocus autocomplete="off">
            </div>
            
            <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">
                <i class="bi bi-unlock-fill me-2"></i> ยืนยันการเข้าสู่ระบบ
            </button>
            <a href="login.php" class="btn btn-light w-100 mt-2">ยกเลิก</a>
        </form>
    </div>
</div>
</body>
</html>
