<?php
session_start();
require_once 'db_config.php';
require_once 'local_db.php';

// หากล็อกอินอย่างสมบูรณ์ (ผ่าน MFA แล้ว) ให้ข้ามไปหน้า dashboard เลย
if (isset($_SESSION['user_id']) && isset($_SESSION['mfa_verified']) && $_SESSION['mfa_verified'] === true) {
    header("Location: dashboard_summary.php");
    exit();
} elseif (isset($_SESSION['user_id'])) {
    // กรณีมี session เก่าค้างอยู่แต่ยังไม่ผ่าน MFA ให้เคลียร์ทิ้ง
    session_destroy();
    session_start();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $ip_address = $_SERVER['REMOTE_ADDR'];

    if (!empty($username) && !empty($password)) {
        // เช็ก Brute Force
        if (is_brute_force_blocked($local_pdo, $ip_address, $username)) {
            $error = 'คุณพยายามเข้าสู่ระบบผิดพลาดเกินกำหนด กรุณารอ 15 นาทีแล้วลองใหม่';
        } else {
            try {
                $sql = "SELECT loginname, passweb, name, groupname, doctorcode FROM opduser WHERE loginname = :username";
                $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // ตรวจสอบรหัสผ่านแบบคลุมทั้ง MD5 ปกติ, MD5 ตัวพิมพ์ใหญ่ (นิยมใน HOSxP), และแบบ Plain text
                $pass_md5 = md5($password);
                $pass_md5_upper = strtoupper(md5($password));
                
                if ($user['passweb'] === $pass_md5 || $user['passweb'] === $pass_md5_upper || $user['passweb'] === $password) {
                    
                    // ตรวจสอบสิทธิ์
                    $user_group = trim($user['groupname'] ?? '');
                    
                    // อนุญาตให้ทุกคนล็อกอินได้ แต่ตั้งต้นเป็นเพียงผู้ดู (Viewer)
                    $is_allowed = true;
                    $is_doctor = false;
                    $is_admin = false;
                    $is_manager = false;

                    $loginname_lower = strtolower($user['loginname']);
                    
                    // กลุ่ม 18 ศูนย์คอมพิวเตอร์ และ 32 เวชสถิติ ให้เป็น Admin
                    if (strpos($user_group, '18') !== false || strpos($user_group, 'ศูนย์คอม') !== false || 
                        strpos($user_group, '32') !== false || strpos($user_group, 'เวชสถิติ') !== false || 
                        strpos($user_group, 'เวชระเบียน') !== false || 
                        $loginname_lower === 'pwwa' || $loginname_lower === 'golfit' || 
                        $loginname_lower === 'naying' || $loginname_lower === 'yingying') {
                        $is_admin = true;
                    } 

                    // ถ้ามีรหัสแพทย์และไม่ใช่แอดมิน ให้เข้าระบบในฐานะแพทย์ (ดูชาร์ตตัวเอง)
                    if (!empty($user['doctorcode']) && !$is_admin) {
                        $is_doctor = true;
                    }
                    
                    if ($is_allowed) {
                        clear_login_attempts($local_pdo, $ip_address, $username);
                        
                        if ($is_doctor) {
                            // สำหรับแพทย์ ให้ข้าม MFA ไปเลย
                            $_SESSION['user_id'] = $user['loginname'];
                            $_SESSION['user_name'] = $user['name'];
                            $_SESSION['mfa_verified'] = true; // ถือว่าผ่าน
                            $_SESSION['is_doctor'] = true;
                            $_SESSION['doctor_code'] = $user['doctorcode'];
                            header("Location: index.php"); // ส่งแพทย์ไปหน้า index.php (รายละเอียด) เลย
                            exit();
                        }

                        // ซ่อนระบบ MFA ไว้ก่อน ให้ข้ามไปใช้งานได้เลย
                        $_SESSION['user_id'] = $user['loginname'];
                        $_SESSION['user_name'] = $user['name'];
                        $_SESSION['mfa_verified'] = true;
                        $_SESSION['is_admin'] = $is_admin;
                        $_SESSION['is_manager'] = $is_manager;

                        header("Location: dashboard_summary.php");
                        exit();

                        /*
                        $_SESSION['temp_mfa_user_id'] = $user['loginname'];
                        $_SESSION['temp_mfa_user_name'] = $user['name'];
                        $_SESSION['temp_is_admin'] = $is_admin;
                        $_SESSION['temp_is_manager'] = $is_manager;

                        // ตรวจสอบสถานะ MFA
                        $stmt_mfa = $local_pdo->prepare("SELECT is_enabled FROM user_mfa WHERE username = :username");
                        $stmt_mfa->execute([':username' => $user['loginname']]);
                        $mfa_data = $stmt_mfa->fetch(PDO::FETCH_ASSOC);

                        if ($mfa_data && $mfa_data['is_enabled'] == 1) {
                            header("Location: verify_mfa.php");
                        } else {
                            header("Location: setup_mfa.php");
                        }
                        exit();
                        */
                    } else {
                        $error = 'บัญชีของคุณไม่มีสิทธิ์เข้าใช้งานระบบนี้ (อนุญาตเฉพาะ ศูนย์คอมฯ, เวชสถิติ และ แพทย์)';
                    }
                    
                } else {
                    record_login_attempt($local_pdo, $ip_address, $username);
                    $error = 'รหัสผ่านไม่ถูกต้อง';
                }
            } else {
                record_login_attempt($local_pdo, $ip_address, $username);
                $error = 'ไม่พบชื่อผู้ใช้งานนี้ในระบบ';
            }
        } catch (PDOException $e) {
            $error = 'เกิดข้อผิดพลาดฐานข้อมูล: ' . $e->getMessage();
        }
        }

    } else {
        $error = 'กรุณากรอกชื่อผู้ใช้และรหัสผ่านให้ครบถ้วน';
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - ระบบติดตามชาร์ต รพ.เทพรัตนเวชชานุกูลฯ</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <!-- Custom Style -->
    <link rel="stylesheet" href="style.css">
</head>
<body class="login-bg">

<div class="container d-flex justify-content-center align-items-center min-vh-100">
    <div class="login-card p-5 custom-card text-center" style="max-width: 450px; width: 100%;">
        <div class="mb-4">
            <i class="bi bi-hospital text-primary" style="font-size: 4rem;"></i>
            <h3 class="mt-3 fw-bold text-dark">ระบบติดตามชาร์ต</h3>
            <p class="text-muted">รพ.เทพรัตนเวชชานุกูล เฉลิมพระเกียรติ ๖๐ พรรษา</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show text-start" role="alert">
                <i class="bi bi-exclamation-circle-fill me-2"></i> <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php" class="text-start">
            <div class="mb-3">
                <label for="username" class="form-label fw-bold">ชื่อผู้ใช้ (HOSxP)</label>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="bi bi-person-fill text-muted"></i></span>
                    <input type="text" class="form-control" id="username" name="username" placeholder="ระบุ Username" required autofocus>
                </div>
            </div>
            
            <div class="mb-4">
                <label for="password" class="form-label fw-bold">รหัสผ่าน</label>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="bi bi-key-fill text-muted"></i></span>
                    <input type="password" class="form-control" id="password" name="password" placeholder="ระบุ Password" required>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary w-100 py-2 fw-bold fs-5 shadow-sm">
                <i class="bi bi-box-arrow-in-right me-2"></i> เข้าสู่ระบบ
            </button>
        </form>
        
        <div class="mt-4 text-muted small">
            &copy; <?php echo date('Y'); ?> ระบบติดตามชาร์ตผู้ป่วยใน
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
