<?php
require 'db.php';

// If already logged in, redirect to index
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';

$sys = $_GET['sys'] ?? '';
$system_name = 'ระบบบันทึกงานล้างไตและฟอกเลือด';
$theme_hex = '#00603f';
$gradient_class = 'from-ministry to-green-400';
$icon_bg_class = 'bg-green-50';
$btn_class = 'bg-ministry hover:bg-ministry-dark focus:ring-ministry';
$input_class = 'focus:border-ministry focus:ring-ministry';
$link_class = 'text-ministry';

if ($sys === 'hd') {
    $system_name = 'ฟอกเลือดด้วยเครื่องไตเทียม (HD)';
    $theme_hex = '#2563eb';
    $gradient_class = 'from-blue-600 to-blue-400';
    $icon_bg_class = 'bg-blue-50';
    $btn_class = 'bg-blue-600 hover:bg-blue-700 focus:ring-blue-600';
    $input_class = 'focus:border-blue-600 focus:ring-blue-600';
    $link_class = 'text-blue-600';
} elseif ($sys === 'pd') {
    $system_name = 'ล้างไตทางช่องท้อง (PD)';
} elseif ($sys === 'lr') {
    $system_name = 'ห้องคลอด (LR)';
    $theme_hex = '#f43f5e';
    $gradient_class = 'from-rose-500 to-rose-400';
    $icon_bg_class = 'bg-rose-50';
    $btn_class = 'bg-rose-500 hover:bg-rose-600 focus:ring-rose-500';
    $input_class = 'focus:border-rose-500 focus:ring-rose-500';
    $link_class = 'text-rose-500';
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'กรุณากรอกชื่อผู้ใช้และรหัสผ่าน';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            if (isset($user['is_active']) && !$user['is_active']) {
                $error = 'บัญชีผู้ใช้นี้ถูกปิดใช้งานชั่วคราว';
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                header("Location: index.php");
                exit;
            }
        } else {
            $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ | ระบบงานฟอกเลือดและล้างไต</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Prompt', 'sans-serif'],
                    },
                    colors: {
                        ministry: {
                            light: '#008751',
                            DEFAULT: '#00603f',
                            dark: '#004d32',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Prompt', sans-serif; }
        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .bg-pattern {
            background-color: #f3f4f6;
            background-image: radial-gradient(<?= $theme_hex ?> 0.5px, transparent 0.5px), radial-gradient(<?= $theme_hex ?> 0.5px, #f3f4f6 0.5px);
            background-size: 20px 20px;
            background-position: 0 0, 10px 10px;
            background-attachment: fixed;
            opacity: 0.9;
        }
    </style>
</head>
<body class="bg-pattern min-h-screen flex items-center justify-center p-4">

    <div class="glass-card rounded-2xl w-full max-w-md overflow-hidden shadow-2xl relative">
        <div class="absolute top-0 left-0 w-full h-2 bg-gradient-to-r <?= $gradient_class ?>"></div>
        
        <div class="p-8">
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-20 h-20 <?= $icon_bg_class ?> rounded-full mb-4 shadow-inner">
                    <img class="h-14 w-14 object-contain rounded-full" src="Logo.jpg" alt="Hospital Logo">
                </div>
                <h1 class="text-2xl font-bold text-gray-800">เข้าสู่ระบบ</h1>
                <p class="text-sm text-gray-500 mt-1"><?= htmlspecialchars($system_name) ?></p>
            </div>

            <?php if ($error): ?>
            <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded mb-6 text-sm" role="alert">
                <p><i class="fas fa-exclamation-circle mr-2"></i><?= htmlspecialchars($error) ?></p>
            </div>
            <?php endif; ?>

            <form action="login.php<?= $sys ? '?sys=' . urlencode($sys) : '' ?>" method="POST" class="space-y-6">
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-1">ชื่อผู้ใช้ (Username)</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-user text-gray-400"></i>
                        </div>
                        <input type="text" name="username" id="username" required 
                               class="pl-10 w-full rounded-lg border-gray-300 shadow-sm <?= $input_class ?> focus:ring focus:ring-opacity-50 py-2.5 border"
                               placeholder="กรอกชื่อผู้ใช้">
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">รหัสผ่าน (Password)</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-lock text-gray-400"></i>
                        </div>
                        <input type="password" name="password" id="password" required 
                               class="pl-10 w-full rounded-lg border-gray-300 shadow-sm <?= $input_class ?> focus:ring focus:ring-opacity-50 py-2.5 border"
                               placeholder="กรอกรหัสผ่าน">
                    </div>
                </div>

                <div>
                    <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white <?= $btn_class ?> focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors duration-200">
                        <i class="fas fa-sign-in-alt mr-2 mt-0.5"></i> เข้าสู่ระบบ
                    </button>
                </div>
                
                <div class="text-center mt-4">
                    <a href="index.php" class="text-sm <?= $link_class ?> hover:underline flex items-center justify-center">
                        <i class="fas fa-arrow-left mr-1"></i> กลับไปหน้าหลัก
                    </a>
                </div>
            </form>
        </div>
    </div>

</body>
</html>
