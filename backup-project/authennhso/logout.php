<?php
/**
 * Logout handler
 * NHSO Claim & Authen Code Detection System
 */

require_once __DIR__ . '/config.php';

// Clear session variables
$_SESSION = [];

// Destroy cookie session if used
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Redirect to login screen
header("Location: login.php");
exit;
