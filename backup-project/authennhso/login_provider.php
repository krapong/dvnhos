<?php
require_once __DIR__ . '/config.php';

$config = loadConfig();
$env = $config['oauth_env'] ?? 'PRD';

$healthIdUrl = $env === 'PRD' ? $config['health_id_prd_url'] : $config['health_id_uat_url'];
$clientId = $env === 'PRD' ? $config['health_id_prd_client_id'] : $config['health_id_uat_client_id'];

// Use callback_provider.php for Provider ID login
$redirectUri = $config['base_url'] . '/callback_provider.php';

$authUrl = $healthIdUrl . '/oauth/redirect' . 
    '?client_id=' . urlencode($clientId) . 
    '&redirect_uri=' . urlencode($redirectUri) . 
    '&response_type=code';

header("Location: " . $authUrl);
exit;
