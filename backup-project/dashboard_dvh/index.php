<?php 
session_start();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบบันทึกข้อมูลโรงพยาบาล</title>
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
        body { font-family: 'Prompt', sans-serif; background-color: #f3f4f6; }
        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .glass-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
    </style>
</head>
<body class="text-gray-800 h-screen flex flex-col">

    <!-- Top Navigation -->
    <nav class="bg-ministry text-white shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center space-x-4">
                    <div class="flex-shrink-0 flex items-center bg-white p-1 rounded-full">
                        <img class="h-10 w-10 object-contain rounded-full" src="Logo.jpg" alt="Hospital Logo">
                    </div>
                    <span class="font-bold text-lg md:text-xl tracking-wide">
                        ระบบบันทึกข้อมูลโรงพยาบาลเทพรัตนเวชชานุกูลเฉลิมพระเกียรติ 60 พรรษา
                    </span>
                </div>
                <div class="flex items-center space-x-4">
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <?php if($_SESSION['role'] === 'admin'): ?>
                            <a href="admin_panel.php" class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1.5 rounded-md text-sm font-medium transition flex items-center shadow-sm">
                                <i class="fas fa-cog mr-1"></i> <span>ตั้งค่าระบบ</span>
                            </a>
                        <?php endif; ?>
                        <span class="text-sm hidden md:inline-block font-medium"><i class="fas fa-user-circle mr-1"></i> <?= htmlspecialchars($_SESSION['username']) ?> (<?= htmlspecialchars($_SESSION['role']) ?>)</span>
                        <a href="logout.php" class="bg-red-500 hover:bg-red-600 text-white px-3 py-1.5 rounded-md text-sm font-medium transition">ออกจากระบบ</a>
                    <?php else: ?>
                        <a href="login.php" class="bg-white text-ministry hover:bg-gray-100 px-3 py-1.5 rounded-md text-sm font-medium transition shadow-sm border border-transparent"><i class="fas fa-lock mr-1"></i> เจ้าหน้าที่</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="flex-grow flex items-center justify-center p-4 sm:p-6 lg:p-8">
        <div class="max-w-6xl w-full">
            <h1 class="text-3xl font-bold text-center text-gray-800 mb-2">เลือกหน่วยงานที่ต้องการบันทึกข้อมูล</h1>
            <p class="text-center text-gray-500 mb-10">กรุณาเลือกระบบงานที่คุณต้องการเข้าสู่หน้าแดชบอร์ด</p>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                
                <!-- PD Card -->
                <a href="pd_index.php" class="block">
                    <div class="glass-card rounded-2xl p-8 border-t-8 border-ministry h-full flex flex-col items-center justify-center text-center">
                        <div class="w-24 h-24 rounded-full bg-green-100 flex items-center justify-center mb-6 text-ministry">
                            <i class="fas fa-procedures text-5xl"></i>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-800 mb-3">ล้างไตทางช่องท้อง (PD)</h2>
                        <p class="text-gray-600 mb-6">ระบบบันทึกข้อมูลประจำวัน งานล้างไตทางช่องท้อง พร้อมสรุปรายงาน</p>
                        <button class="mt-auto px-6 py-2 bg-ministry text-white rounded-full font-medium hover:bg-ministry-dark transition">
                            เข้าสู่ระบบ <i class="fas fa-arrow-right ml-2"></i>
                        </button>
                    </div>
                </a>

                <!-- HD Card -->
                <a href="hd_index.php" class="block">
                    <div class="glass-card rounded-2xl p-8 border-t-8 border-blue-600 h-full flex flex-col items-center justify-center text-center">
                        <div class="w-24 h-24 rounded-full bg-blue-100 flex items-center justify-center mb-6 text-blue-600">
                            <i class="fas fa-bed-pulse text-5xl"></i>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-800 mb-3">ฟอกเลือดด้วยเครื่องไตเทียม (HD)</h2>
                        <p class="text-gray-600 mb-6">ระบบบันทึกข้อมูลประจำวัน หน่วย Hemodialysis พร้อมสรุปรายงาน</p>
                        <button class="mt-auto px-6 py-2 bg-blue-600 text-white rounded-full font-medium hover:bg-blue-800 transition">
                            เข้าสู่ระบบ <i class="fas fa-arrow-right ml-2"></i>
                        </button>
                    </div>
                </a>

                <!-- LR Card -->
                <a href="lr_index.php" class="block">
                    <div class="glass-card rounded-2xl p-8 border-t-8 border-rose-500 h-full flex flex-col items-center justify-center text-center">
                        <div class="w-24 h-24 rounded-full bg-rose-100 flex items-center justify-center mb-6 text-rose-500">
                            <i class="fas fa-baby text-5xl"></i>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-800 mb-3">ห้องคลอด (LR)</h2>
                        <p class="text-gray-600 mb-6">ระบบบันทึกข้อมูลประจำวัน งานห้องคลอด Labor Room พร้อมสรุปรายงาน</p>
                        <button class="mt-auto px-6 py-2 bg-rose-500 text-white rounded-full font-medium hover:bg-rose-700 transition">
                            เข้าสู่ระบบ <i class="fas fa-arrow-right ml-2"></i>
                        </button>
                    </div>
                </a>

            </div>
        </div>
    <!-- Footer -->
    <footer class="fixed bottom-4 right-4 z-50 text-right text-xs text-gray-400 pointer-events-none">
        <p>developer by นายศุภชัย นะที</p>
        <p>นักวิชาการคอมพิวเตอร์</p>
        <p>กลุ่มงานสุขภาพดิจิทัล</p>
    </footer>

</body>
</html>
