<?php
require_once __DIR__ . '/config.php';

$code = $_GET['code'] ?? '';

if (empty($code)) {
    die("Error: No authorization code received from Health ID.");
}

$config = loadConfig();
$env = $config['oauth_env'] ?? 'PRD';

$healthIdUrl = $env === 'PRD' ? $config['health_id_prd_url'] : $config['health_id_uat_url'];
$clientId = $env === 'PRD' ? $config['health_id_prd_client_id'] : $config['health_id_uat_client_id'];
$clientSecret = $env === 'PRD' ? $config['health_id_prd_secret'] : $config['health_id_uat_secret'];
$redirectUri = $config['base_url'] . '/callback_thaid.php';

$tokenUrl = $healthIdUrl . '/api/v1/token';
$postData = http_build_query([
    'grant_type' => 'authorization_code',
    'code' => $code,
    'redirect_uri' => $redirectUri,
    'client_id' => $clientId,
    'client_secret' => $clientSecret
]);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $tokenUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/x-www-form-urlencoded'
]);
if ($env === 'UAT') {
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
}

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    die("Error exchanging token. HTTP Status: " . $httpCode . "<br>Response: " . htmlspecialchars($response));
}

$tokenData = json_decode($response, true);
if (empty($tokenData['data']['access_token'])) {
    die("Error: Invalid token response structure.");
}

// Decode JWT to get user details
$accessToken = $tokenData['data']['access_token'];
$tokenParts = explode('.', $accessToken);
$payload = isset($tokenParts[1]) ? json_decode(base64_decode(strtr($tokenParts[1], '-_', '+/')), true) : [];

$pid = $payload['pid'] ?? $payload['sub'] ?? 'Unknown_PID';
$fullname = $payload['name'] ?? 'ผู้ใช้งาน ThaID (' . $pid . ')';

// Log user in
$_SESSION['user_id'] = $pid;
$_SESSION['username'] = $pid;
$_SESSION['fullname'] = $fullname;
$_SESSION['role'] = 'user'; // Basic user role

header("Location: index.php");
exit;
