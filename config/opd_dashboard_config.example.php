<?php
/**
 * ตัวอย่าง config สำหรับ OPD Dashboard
 * ไฟล์นี้อยู่ในเว็บรูทได้เฉพาะเมื่อไม่มีรหัสผ่านจริง
 * บน Server ให้คัดลอกไปไว้ /var/www/opd_dashboard_config.php
 */
return [
    'database' => [
        'host' => 'YOUR_DATABASE_HOST',
        'port' => 3306,
        'name' => 'YOUR_DATABASE_NAME',
        'user' => 'ใส่ชื่อบัญชีอ่านข้อมูล',
        'password' => 'ใส่รหัสผ่านบัญชีอ่านข้อมูล',
    ],
    // ต้องเป็นโฟลเดอร์ที่มีอยู่จริง เขียนได้โดย Apache และอยู่นอก /var/www/html
    'private_directory' => '/var/lib/opd-dashboard',
    'app_secret' => 'เปลี่ยนเป็นค่าสุ่มยาวก่อนใช้งานจริง',
];
