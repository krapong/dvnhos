<?php
/**
 * Login Screen
 * NHSO Claim & Authen Code Detection System
 */

require_once __DIR__ . '/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header("Location: index.php");
    exit;
}

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'กรุณากรอกชื่อผู้ใช้งานและรหัสผ่าน';
    } else {
        $users = getUsersList();
        
        if (isset($users[$username])) {
            $user = $users[$username];
            if (password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $username;
                $_SESSION['username'] = $username;
                $_SESSION['fullname'] = $user['fullname'];
                $_SESSION['role'] = $user['role'];
                
                header("Location: index.php");
                exit;
            } else {
                $error = 'รหัสผ่านไม่ถูกต้อง';
            }
        } else {
            $error = 'ไม่พบชื่อผู้ใช้งานนี้ในระบบ';
        }
    }
}

$expired = isset($_GET['expired']) && $_GET['expired'] == 1;
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="ระบบตรวจจับผู้รับบริการที่ไม่ได้ขอเลข Authen Code (HOSxP) - เข้าสู่ระบบ">
    <title>เข้าสู่ระบบ — NHSO Claim Detection</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bg-main: #f0f4f8;
            --bg-surface: rgba(255, 255, 255, 0.85);
            --border-color: rgba(203, 213, 225, 0.5);
            --primary: #0f766e;
            --primary-hover: #0d655e;
            --primary-glow: rgba(15, 118, 110, 0.15);
            --secondary: #0369a1;
            --text-title: #0f172a;
            --text-body: #334155;
            --text-muted: #64748b;
            --danger: #dc2626;
            --danger-bg: #fff1f2;
            --danger-border: #fecdd3;
            --warning: #d97706;
            --warning-bg: #fffbeb;
            --warning-border: #fde68a;
            
            --transition-speed: 0.3s;
            --font-stack: 'Sarabun', 'Inter', -apple-system, sans-serif;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: #0f172a; /* Sleek dark theme background specifically for login screen */
            background-image: 
                radial-gradient(at 0% 0%, rgba(15, 118, 110, 0.25) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(3, 105, 161, 0.2) 0px, transparent 50%),
                radial-gradient(at 50% 50%, rgba(15, 118, 110, 0.15) 0px, transparent 60%);
            background-attachment: fixed;
            color: var(--text-body);
            font-family: var(--font-stack);
            -webkit-font-smoothing: antialiased;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-wrapper {
            width: 100%;
            max-width: 440px;
            perspective: 1000px;
        }

        .login-card {
            background: rgba(15, 23, 42, 0.65); /* Rich dark card reflecting glassmorphism */
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 24px;
            padding: 40px 32px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            text-align: center;
            transform: translateY(20px);
            opacity: 0;
            animation: slideIn 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        @keyframes slideIn {
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .logo-icon {
            width: 52px;
            height: 52px;
            background: linear-gradient(135deg, var(--primary) 0%, #10b981 100%);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            color: white;
            font-size: 24px;
            box-shadow: 0 8px 24px rgba(15, 118, 110, 0.4);
            margin: 0 auto 20px auto;
        }

        h1 {
            font-size: 22px;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 6px;
            letter-spacing: -0.5px;
        }

        .subtitle {
            font-size: 13px;
            color: #94a3b8;
            margin-bottom: 30px;
        }

        /* Forms */
        .form-group {
            margin-bottom: 20px;
            text-align: left;
            position: relative;
        }

        label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: #94a3b8;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-icon {
            position: absolute;
            left: 14px;
            color: #64748b;
            font-size: 16px;
            pointer-events: none;
            transition: color var(--transition-speed);
        }

        .form-control {
            width: 100%;
            background-color: rgba(30, 41, 59, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 12px 16px 12px 42px;
            color: #ffffff;
            font-family: var(--font-stack);
            font-size: 14px;
            transition: all var(--transition-speed);
        }

        .form-control:focus {
            outline: none;
            border-color: #14b8a6;
            background-color: rgba(30, 41, 59, 0.8);
            box-shadow: 0 0 0 3px rgba(20, 184, 166, 0.25);
        }

        .form-control:focus + .input-icon {
            color: #14b8a6;
        }

        /* Alerts & Errors */
        .alert {
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 13px;
            line-height: 1.5;
            text-align: left;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            border: 1px solid transparent;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-5px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .alert-danger {
            background-color: rgba(220, 38, 38, 0.15);
            border-color: rgba(220, 38, 38, 0.25);
            color: #f87171;
        }

        .alert-warning {
            background-color: rgba(217, 119, 6, 0.15);
            border-color: rgba(217, 119, 6, 0.25);
            color: #fbbf24;
        }

        /* Button */
        .btn-submit {
            width: 100%;
            background: linear-gradient(135deg, var(--primary) 0%, #10b981 100%);
            color: white;
            border: none;
            padding: 14px;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all var(--transition-speed);
            box-shadow: 0 4px 15px rgba(15, 118, 110, 0.3);
            margin-top: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(15, 118, 110, 0.5);
            background: linear-gradient(135deg, var(--primary-hover) 0%, #059669 100%);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        .footer-note {
            margin-top: 30px;
            font-size: 11px;
            color: #64748b;
        }

        .footer-note a {
            color: #94a3b8;
            text-decoration: none;
        }

        .footer-note a:hover {
            color: #ffffff;
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="login-wrapper">
    <div class="login-card">
        <div class="logo-icon">N</div>
        <h1>NHSO Claim Detection</h1>
        <p class="subtitle">ระบบตรวจจับสิทธิ์เคลมตกหล่น HOSxP รพ.เทพรัตนเวชชานุกูล ฯ</p>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <span>⚠️</span>
                <div><?php echo htmlspecialchars($error); ?></div>
            </div>
        <?php endif; ?>

        <?php if ($expired): ?>
            <div class="alert alert-warning">
                <span>⏳</span>
                <div>เซสชั่นหมดอายุการใช้งาน กรุณาเข้าสู่ระบบอีกครั้ง</div>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php" autocomplete="off" style="display: none;">
            <div class="form-group">
                <label for="username">ชื่อผู้ใช้งาน (Username)</label>
                <div class="input-wrapper">
                    <input type="text" id="username" name="username" class="form-control" placeholder="พิมพ์ชื่อผู้ใช้งาน..." required autofocus value="<?php echo htmlspecialchars($username); ?>">
                    <span class="input-icon">👤</span>
                </div>
            </div>

            <div class="form-group">
                <label for="password">รหัสผ่าน (Password)</label>
                <div class="input-wrapper">
                    <input type="password" id="password" name="password" class="form-control" placeholder="พิมพ์รหัสผ่าน..." required>
                    <span class="input-icon">🔒</span>
                </div>
            </div>

            <button type="submit" class="btn-submit">
                <span>🔑 เข้าสู่ระบบ</span>
            </button>
        </form>

        <div style="margin: 20px 0; display: none; align-items: center; justify-content: center; gap: 10px;">
            <div style="height: 1px; background: rgba(255,255,255,0.1); flex: 1;"></div>
            <span style="color: #64748b; font-size: 12px; font-weight: 600;">หรือเข้าสู่ระบบด้วย</span>
            <div style="height: 1px; background: rgba(255,255,255,0.1); flex: 1;"></div>
        </div>

        <div style="display: flex; flex-direction: column; gap: 10px; margin-top: 20px;">
            <a href="login_provider.php" class="btn-submit" style="background: linear-gradient(135deg, #10b981 0%, #047857 100%); text-decoration: none; box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4);">
                <span>🩺 เข้าสู่ระบบด้วย Provider ID</span>
            </a>
        </div>

        <div class="footer-note">
            <p>ติดต่อศูนย์ข้อมูลสารสนเทศ โทร. 109/119</p>
            <p style="margin-top: 4px; color: #475569;">Developer by นายศุภชัย นะที &middot; v1.2.0</p>
        </div>
    </div>
</div>

</body>
</html>
