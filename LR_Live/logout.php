<?php
require_once __DIR__.'/auth-bootstrap.php';
if($_SERVER['REQUEST_METHOD']!=='POST'){header('Allow: POST');http_response_code(405);exit;}
$token=$_POST['csrf']??'';if(!is_string($token)||!hash_equals($_SESSION['csrf'],$token)){http_response_code(403);exit('Invalid request.');}
usageLog('logout');clearAuthSession();header('Location: login.php',true,303);exit;
