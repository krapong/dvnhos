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
$success = '';

$ga = new GoogleAuthenticator();

// ถ้ายังไม่มี secret key ใน session (โหลดหน้าครั้งแรก) ให้สร้างใหม่
if (!isset($_SESSION['mfa_setup_secret'])) {
    $_SESSION['mfa_setup_secret'] = $ga->createSecret();
}

$secret = $_SESSION['mfa_setup_secret'];
// สร้าง QR Code URL (สามารถเปลี่ยน 'ChartAlert' เป็นชื่อระบบที่ต้องการให้โชว์ในแอป)
$qrCodeUrl = $ga->getQRCodeGoogleUrl($username, $secret, 'ChartAlert');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['mfa_code'] ?? '');
    
    if (empty($code)) {
        $error = 'กรุณากรอกรหัส 6 หลัก';
    } else {
        $checkResult = $ga->verifyCode($secret, $code, 2);    // อนุญาตเวลาคลาดเคลื่อนได้นิดหน่อย
        if ($checkResult) {
            // บันทึกลง SQLite
            try {
                $stmt = $local_pdo->prepare("
                    INSERT INTO user_mfa (username, secret_key, is_enabled) 
                    VALUES (:username, :secret, 1)
                    ON CONFLICT(username) DO UPDATE SET secret_key = :secret, is_enabled = 1
                ");
                $stmt->execute([':username' => $username, ':secret' => $secret]);
                
                // เลื่อนขั้น Session เป็น Logged In แบบสมบูรณ์
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
                unset($_SESSION['mfa_setup_secret']);
                
                header("Location: dashboard_summary.php");
                exit();
            } catch (PDOException $e) {
                $error = 'ไม่สามารถบันทึกข้อมูลได้: ' . $e->getMessage();
            }
        } else {
            $error = 'รหัสไม่ถูกต้อง ลองใหม่อีกครั้ง';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตั้งค่า MFA - ระบบติดตามชาร์ต</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
</head>
<body class="login-bg">
<div class="container d-flex justify-content-center align-items-center min-vh-100">
    <div class="login-card p-5 custom-card text-center" style="max-width: 500px; width: 100%;">
        <div class="mb-4">
            <i class="bi bi-shield-lock-fill text-primary" style="font-size: 3rem;"></i>
            <h4 class="mt-3 fw-bold text-dark">ตั้งค่าการยืนยันตัวตน (2FA)</h4>
            <p class="text-muted">สำหรับคุณ <b><?php echo htmlspecialchars($user_name_display); ?></b></p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><i class="bi bi-exclamation-circle-fill me-2"></i><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="alert alert-info text-start small">
            1. ดาวน์โหลดแอป <strong>Google Authenticator</strong> บนมือถือของคุณ<br>
            2. เปิดแอปแล้วสแกน QR Code ด้านล่างนี้<br>
            3. นำตัวเลข 6 หลักที่ปรากฏในแอปมากรอกเพื่อยืนยัน
        </div>

        <div class="mb-4">
            <img src="<?php echo $qrCodeUrl; ?>" alt="QR Code" class="img-thumbnail shadow-sm">
        </div>

        <form method="POST" action="setup_mfa.php">
            <div class="mb-4">
                <label for="mfa_code" class="form-label fw-bold">กรอกรหัสยืนยัน 6 หลัก</label>
                <input type="text" class="form-control text-center fs-4 ls-wider" id="mfa_code" name="mfa_code" maxlength="6" pattern="[0-9]{6}" required autofocus>
            </div>
            
            <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">
                <i class="bi bi-check-circle me-2"></i> ยืนยันและเปิดใช้งาน
            </button>
            <a href="login.php" class="btn btn-light w-100 mt-2">ยกเลิก</a>
        </form>
    </div>
</div>
</body>
</html>
