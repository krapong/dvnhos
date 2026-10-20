@echo off
:: สคริปต์นี้ใช้สำหรับรันอัตโนมัติผ่าน Windows Task Scheduler ทุก 1 นาที
:: โดยจะเรียกใช้ไฟล์ cron_notify.php เพื่อตรวจสอบเวลาและส่งแจ้งเตือน

cd /d "%~dp0"
"C:\xampp\php\php.exe" cron_notify.php
