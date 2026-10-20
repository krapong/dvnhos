<?php
/**
 * HOSxP Database Configuration
 * แก้ไขค่าต่อไปนี้ให้ตรงกับ HOSxP server ของคุณ
 */

define('DB_HOST', 'YOUR_PRIVATE_SERVER');       // IP หรือ hostname ของ MySQL HOSxP
define('DB_PORT', '3306');               // MySQL port (ปกติ 3306)
define('DB_NAME', 'CHANGE_ME_BEFORE_USE');          // ชื่อ database HOSxP (hosxp_pcu หรือ his)
define('DB_USER', 'CHANGE_ME_BEFORE_USE');              // MySQL username
define('DB_PASS', 'CHANGE_ME_BEFORE_USE');              // MySQL password
define('DB_CHARSET', 'utf8');

// ==============================
// App Settings
// ==============================
define('APP_TITLE', 'ER ESI ALERT MONITOR');
define('HOSPITAL_NAME', 'โรงพยาบาลเทพรัตนเวชชานุกูล ฯ');
define('DEPT_NAME', 'ห้องฉุกเฉิน (ER)');
define('LOS_LIMIT_MIN', 120);              // เวลาสูงสุดในห้องฉุกเฉิน (นาที)
define('REFRESH_SEC', 30);               // รีเฟรชอัตโนมัติ (วินาที)
define('TIMEZONE', 'Asia/Bangkok');

// ==============================
// Security (optional)
// ==============================
define('API_TOKEN', '');               // ใส่ token เพื่อป้องกัน ถ้าไม่ต้องการให้ว่างไว้
define('ALLOWED_IPS', []);               // เช่น ['YOUR_PRIVATE_SERVER/24'] หรือ [] = ทุก IP

date_default_timezone_set(TIMEZONE);
