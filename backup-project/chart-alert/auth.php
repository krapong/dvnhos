<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// เช็กว่ามีการล็อกอินหรือยัง และผ่าน MFA หรือยัง
if (!isset($_SESSION['user_id']) || !isset($_SESSION['mfa_verified']) || $_SESSION['mfa_verified'] !== true) {
    header("Location: login.php");
    exit();
}

$is_admin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
$is_manager = isset($_SESSION['is_manager']) && $_SESSION['is_manager'] === true;
$is_doctor = isset($_SESSION['is_doctor']) && $_SESSION['is_doctor'] === true;
?>
