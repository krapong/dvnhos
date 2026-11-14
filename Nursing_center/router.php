<?php
require __DIR__ . '/security.php';
try { ncc_route(); }
catch (Throwable $error) {
    error_log('NCC: '.$error->getMessage());
    http_response_code(503);
    header('Content-Type: text/html; charset=utf-8');
    echo 'ระบบยังไม่พร้อมใช้งาน กรุณาให้ผู้ดูแลตรวจสอบการเชื่อมต่อฐานข้อมูล';
}
