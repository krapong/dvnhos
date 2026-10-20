<?php
// telegram_helper.php

function sendTelegramNotify($message) {
    global $pdo;

    $telegram_bot_token = '';
    $telegram_chat_id = '';

    if (isset($pdo)) {
        try {
            $stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('telegram_bot_token', 'telegram_chat_id')");
            while ($row = $stmt->fetch()) {
                if ($row['setting_key'] === 'telegram_bot_token') $telegram_bot_token = $row['setting_value'];
                if ($row['setting_key'] === 'telegram_chat_id') $telegram_chat_id = $row['setting_value'];
            }
        } catch (\PDOException $e) {
            // Silently fail if table doesn't exist or DB error
        }
    }

    if (empty($telegram_bot_token) || empty($telegram_chat_id)) {
        return false; // ยังไม่ได้ตั้งค่า
    }

    $url = "https://api.telegram.org/bot{$telegram_bot_token}/sendMessage";
    $data = [
        'chat_id' => $telegram_chat_id,
        'text' => $message,
        'parse_mode' => 'HTML'
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    // Timeout 5 seconds to prevent blocking the save action if Telegram is slow
    curl_setopt($ch, CURLOPT_TIMEOUT, 5); 
    
    $result = curl_exec($ch);
    curl_close($ch);
    
    return $result;
}
?>
