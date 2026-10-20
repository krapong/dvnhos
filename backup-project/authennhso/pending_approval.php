<?php
require_once __DIR__ . '/config.php';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รอการอนุมัติสิทธิ์ — NHSO Claim Detection</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: #0f172a;
            color: #f8fafc;
            font-family: 'Sarabun', 'Inter', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
            text-align: center;
        }
        .card {
            background: rgba(30, 41, 59, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 40px;
            max-width: 500px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
        }
        .icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
        h2 { margin: 0 0 10px 0; color: #fbbf24; }
        p { color: #94a3b8; line-height: 1.6; }
        .btn {
            display: inline-block;
            margin-top: 20px;
            background: #3b82f6;
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
        }
        .btn:hover { background: #2563eb; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">⏳</div>
        <h2>รอการอนุมัติสิทธิ์เข้าใช้งาน</h2>
        <p>บัญชีของคุณอยู่ในระบบแล้ว แต่ยังไม่ได้รับการอนุมัติให้เข้าสู่ระบบ <br>กรุณาติดต่อผู้ดูแลระบบ (Admin) เพื่อขออนุมัติสิทธิ์ครับ</p>
        <a href="login.php" class="btn">กลับหน้าแรก</a>
    </div>
</body>
</html>
