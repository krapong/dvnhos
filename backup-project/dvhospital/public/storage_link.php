<?php
/**
 * DVHospital Automated Storage Symlinker for Shared Hosting
 * Programmatically creates the public/storage symlink.
 * For security, delete this file after running!
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบเชื่อมต่อโฟลเดอร์รูปภาพอัปโหลด (Storage Link Tool)</title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Sarabun', sans-serif;
            background-color: #f3f4f6;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-content: center;
            align-items: center;
            height: 100vh;
        }
        .container {
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            padding: 40px;
            max-width: 500px;
            width: 90%;
            text-align: center;
        }
        .icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
        h1 {
            color: #1f2937;
            font-size: 22px;
            margin-bottom: 10px;
            font-weight: 700;
        }
        p {
            color: #4b5563;
            font-size: 15px;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        .btn {
            background-color: #059669;
            color: #ffffff;
            border: none;
            padding: 12px 30px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.2s;
            text-decoration: none;
            display: inline-block;
        }
        .btn:hover {
            background-color: #047857;
        }
        .status-box {
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            font-weight: 600;
            font-size: 15px;
            text-align: left;
            border-left: 5px solid;
        }
        .success {
            background-color: #ecfdf5;
            color: #065f46;
            border-left-color: #059669;
        }
        .error {
            background-color: #fef2f2;
            color: #991b1b;
            border-left-color: #dc2626;
        }
        .warning {
            background-color: #fffbeb;
            color: #92400e;
            border-left-color: #d97706;
            margin-top: 20px;
            font-size: 13px;
            font-weight: normal;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="icon">📁</div>
    <h1>ระบบเชื่อมโยงโฟลเดอร์ภาพ (Storage Link)</h1>
    <p>เครื่องมือสร้างทางลัด (Symbolic Link) สำหรับแสดงผลรูปภาพข่าวสารและแบนเนอร์ประชาสัมพันธ์ของเว็บไซต์โรงพยาบาล</p>

    <?php
    $target = dirname(__DIR__) . '/storage/app/public';
    $shortcut = __DIR__ . '/storage';

    if (isset($_GET['run'])) {
        // Clear old symlink if exists and is broken
        if (file_exists($shortcut) || is_link($shortcut)) {
            @unlink($shortcut);
        }

        // Try standard php symlink
        if (@symlink($target, $shortcut)) {
            echo '<div class="status-box success">';
            echo '🎉 สำเร็จ! สร้างจุดเชื่อมต่อภาพเรียบร้อยแล้ว<br>';
            echo '<span style="font-size: 13px; font-weight: normal;">Target: ' . htmlspecialchars($target) . '<br>Shortcut: ' . htmlspecialchars($shortcut) . '</span>';
            echo '</div>';
            echo '<div class="status-box warning">';
            echo '🚨 <strong>ข้อแนะนำด้านความปลอดภัย:</strong> กรุณาลบไฟล์ <code>public/storage_link.php</code> ออกจากโฮสติ้งของคุณทันที เพื่อป้องกันบุคคลอื่นเข้ามารันระบบซ้ำ';
            echo '</div>';
        } else {
            // Try shell execution fallback (for Linux systems)
            $output = [];
            $returnVar = -1;
            @exec("ln -s " . escapeshellarg($target) . " " . escapeshellarg($shortcut), $output, $returnVar);

            if ($returnVar === 0) {
                echo '<div class="status-box success">';
                echo '🎉 สำเร็จ (ผ่านระบบ Shell)! สร้างจุดเชื่อมต่อภาพเรียบร้อยแล้ว';
                echo '</div>';
                echo '<div class="status-box warning">';
                echo '🚨 <strong>ข้อแนะนำด้านความปลอดภัย:</strong> กรุณาลบไฟล์ <code>public/storage_link.php</code> ออกจากโฮสติ้งของคุณทันที';
                echo '</div>';
            } else {
                echo '<div class="status-box error">';
                echo '❌ ล้มเหลว! โฮสติ้งของคุณไม่อนุญาตให้ใช้คำสั่งสร้างทางลัดทางโปรแกรม (Symlink Disabled)<br>';
                echo '<span style="font-size: 13px; font-weight: normal; margin-top: 10px; display: block;">กรุณาติดต่อผู้ดูแลระบบโฮสติ้งของท่านเพื่อขอให้ดำเนินการสร้าง Symbolic Link จากโฟลเดอร์ <code>storage/app/public</code> ไปที่ <code>public/storage</code></span>';
                echo '</div>';
            }
        }
    } else {
        // Check current status
        if (file_exists($shortcut) && is_link($shortcut)) {
            echo '<div class="status-box success" style="text-align: center;">';
            echo '✓ โฟลเดอร์รูปภาพอัปโหลดเชื่อมโยงกันอยู่แล้ว';
            echo '</div>';
        } else {
            echo '<a href="?run=1" class="btn">คลิกเพื่อเชื่อมต่อโฟลเดอร์ภาพ</a>';
        }
    }
    ?>

</div>

</body>
</html>
