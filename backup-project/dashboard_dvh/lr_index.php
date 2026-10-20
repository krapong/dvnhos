<?php
session_start();
require_once 'db.php';

// Allow public access to view the maintenance message
// No authentication required here currently

$username = $_SESSION['username'] ?? 'ผู้เยี่ยมชม';
$role = $_SESSION['role'] ?? 'guest';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบงานห้องคลอด LR | Labor Room System</title>
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
        body { font-family: 'Prompt', sans-serif; background-color: #fff1f2; }
        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(253, 244, 245, 0.8);
            box-shadow: 0 10px 30px -10px rgba(244, 63, 94, 0.15);
        }
    </style>
</head>
<body class="text-slate-800 min-h-screen flex flex-col">

    <!-- Top Navigation -->
    <nav class="bg-rose-600 text-white shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center space-x-4">
                    <div class="flex-shrink-0 flex items-center bg-white p-1 rounded-full">
                        <img class="h-10 w-10 object-contain rounded-full" src="Logo.jpg" alt="Hospital Logo">
                    </div>
                    <span class="font-bold text-lg md:text-xl tracking-wide flex items-center">
                        <i class="fas fa-baby mr-2 text-rose-200"></i> ระบบงานห้องคลอด LR (Labor Room)
                    </span>
                </div>
                <div class="flex items-center space-x-4">
                    <?php if($_SESSION['role'] === 'admin'): ?>
                        <a href="admin_panel.php" class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1.5 rounded-md text-sm font-medium transition flex items-center shadow-sm">
                            <i class="fas fa-cog mr-1"></i> <span>ตั้งค่าระบบ</span>
                        </a>
                    <?php endif; ?>
                    <a href="index.php" class="text-white hover:text-rose-100 px-3 py-1.5 rounded-md text-sm font-medium transition flex items-center">
                        <i class="fas fa-home mr-1"></i> หน้าหลัก
                    </a>
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <span class="text-xs bg-rose-700 px-3 py-1.5 rounded-full font-medium"><i class="fas fa-user-circle mr-1"></i> <?= htmlspecialchars($username) ?> (<?= htmlspecialchars($role) ?>)</span>
                        <a href="logout.php" class="bg-red-500 hover:bg-red-600 text-white px-3 py-1.5 rounded-md text-sm font-medium transition">ออกจากระบบ</a>
                    <?php else: ?>
                        <a href="login.php" class="bg-white text-rose-600 hover:bg-rose-50 px-3 py-1.5 rounded-md text-sm font-medium transition shadow-sm"><i class="fas fa-lock mr-1"></i> เจ้าหน้าที่</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="flex-grow flex items-center justify-center p-6 w-full max-w-4xl mx-auto">
        <div class="glass-card rounded-3xl p-8 sm:p-12 text-center border-t-8 border-rose-500 max-w-2xl w-full">
            <div class="w-28 h-28 rounded-full bg-rose-100 flex items-center justify-center mx-auto mb-8 text-rose-500 shadow-md">
                <i class="fas fa-baby-carriage text-6xl"></i>
            </div>
            
            <span class="px-4 py-1.5 bg-rose-50 border border-rose-200 text-rose-600 rounded-full text-xs font-semibold uppercase tracking-wider block w-max mx-auto mb-4">
                <i class="fas fa-cog animate-spin mr-1.5"></i> กำลังอยู่ระหว่างการปรับปรุงระบบ
            </span>
            
            <h1 class="text-3xl font-extrabold text-slate-800 mb-4 tracking-tight">ระบบบันทึกข้อมูลห้องคลอด (LR)</h1>
            <p class="text-slate-500 mb-8 leading-relaxed max-w-md mx-auto text-sm sm:text-base">
                ฝ่ายไอทีและสถิติกำลังพัฒนาและบูรณาการโมดูลการลงบันทึกข้อมูลสถิติผู้รับบริการห้องคลอดประจำวัน เพื่อสรุปรายงานและคำนวณปีงบประมาณอัตโนมัติเช่นเดียวกับระบบอื่นๆ
            </p>

            <div class="flex flex-col sm:flex-row justify-center items-center gap-4">
                <a href="index.php" class="w-full sm:w-auto px-8 py-3 bg-rose-500 text-white font-medium rounded-xl hover:bg-rose-600 transition shadow-md shadow-rose-200 flex items-center justify-center gap-2">
                    <i class="fas fa-arrow-left"></i> กลับสู่หน้าหลัก
                </a>
                <a href="mailto:it@hospital.com" class="w-full sm:w-auto px-6 py-3 border border-slate-200 text-slate-600 font-medium rounded-xl hover:bg-slate-50 transition flex items-center justify-center gap-2">
                    <i class="fas fa-envelope"></i> ติดต่อฝ่ายไอที
                </a>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-rose-50 border-t border-rose-100 py-6 text-center text-rose-400 text-xs mt-12">
        <p>ระบบบันทึกข้อมูลโรงพยาบาลเทพรัตนเวชชานุกูลเฉลิมพระเกียรติ 60 พรรษา - ฝ่ายไอทีและสถิติข้อมูลสุขภาพ</p>
    </footer>

</body>
</html>
