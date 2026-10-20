<?php
require_once 'auth.php';
require_once 'db_config.php';

$config_file = 'telegram_config.json';
$bot_token = '';
$chat_id = '';

if (file_exists($config_file)) {
    $config_content = file_get_contents($config_file);
    $config = json_decode($config_content, true);
    if (is_array($config)) {
        $bot_token = $config['bot_token'] ?? '';
        $chat_id = $config['chat_id'] ?? '';
    }
}

// ตรวจสอบว่ามีการตั้งค่า Token และ Chat ID หรือยัง
if (empty($bot_token) || empty($chat_id)) {
    die("<script>alert('กรุณาตั้งค่า Telegram Bot Token และ Chat ID ที่หน้า Settings ก่อนใช้งาน'); window.location.href='settings.php';</script>");
}

// ดึงข้อมูลสำหรับเดือนปัจจุบัน
$start_date = date('Y-m-01');
$end_date = date('Y-m-d');

$sql = "
    SELECT 
        IFNULL(d.name, 'ไม่ระบุแพทย์') AS doctor_name,
        SUM(CASE WHEN DATEDIFF(CURDATE(), i.dchdate) BETWEEN 8 AND 30 THEN 1 ELSE 0 END) AS days_8_30,
        SUM(CASE WHEN DATEDIFF(CURDATE(), i.dchdate) > 30 THEN 1 ELSE 0 END) AS days_over_30,
        SUM(CASE WHEN DATEDIFF(CURDATE(), i.dchdate) <= 7 THEN 1 ELSE 0 END) AS under_7_days
    FROM ipt i
    LEFT JOIN doctor d ON d.code = i.admdoctor
    WHERE i.dchdate BETWEEN :start_date AND :end_date
      AND NOT EXISTS (
          SELECT 1 FROM ipt_doctor_diag id WHERE id.an = i.an AND id.diag_text IS NOT NULL AND TRIM(id.diag_text) != ''
      )
    GROUP BY d.name
    HAVING (days_over_30 > 0 OR days_8_30 > 0 OR under_7_days > 0)
    ORDER BY days_over_30 DESC, days_8_30 DESC, under_7_days DESC
";

$stmt = $pdo->prepare($sql);
$stmt->bindParam(':start_date', $start_date);
$stmt->bindParam(':end_date', $end_date);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_over_30 = 0;
$total_8_30 = 0;
$total_under_7 = 0;

$list_over_30 = "";
$list_8_30 = "";
$list_under_7 = "";

foreach ($results as $row) {
    if ($row['days_over_30'] > 0) {
        $list_over_30 .= "- {$row['doctor_name']} ({$row['days_over_30']} ราย)\n";
        $total_over_30 += $row['days_over_30'];
    }
    if ($row['days_8_30'] > 0) {
        $list_8_30 .= "- {$row['doctor_name']} ({$row['days_8_30']} ราย)\n";
        $total_8_30 += $row['days_8_30'];
    }
    if ($row['under_7_days'] > 0) {
        $list_under_7 .= "- {$row['doctor_name']} ({$row['under_7_days']} ราย)\n";
        $total_under_7 += $row['under_7_days'];
    }
}

// สร้างข้อความ
$thai_year = date('Y') + 543;
$date_str = date('d/m/') . $thai_year;

$message = "🏥 *รายงานสรุปชาร์ตค้าง (ภาพรวมกลุ่ม)* 🏥\n";
$message .= "📅 ประจำวันที่: {$date_str}\n\n";

if ($total_over_30 > 0) {
    $message .= "🔴 *ระดับวิกฤต (> 30 วัน):* `{$total_over_30}` ราย\n";
    $message .= $list_over_30 . "\n";
} else {
    $message .= "🔴 *ระดับวิกฤต (> 30 วัน):* 0 ราย ✅\n\n";
}

if ($total_8_30 > 0) {
    $message .= "🟡 *ระดับต้องเตือน (8-30 วัน):* `{$total_8_30}` ราย\n";
    $message .= $list_8_30 . "\n";
} else {
    $message .= "🟡 *ระดับต้องเตือน (8-30 วัน):* 0 ราย ✅\n\n";
}

if ($total_under_7 > 0) {
    $message .= "🟢 *ระดับปกติ (<= 7 วัน):* `{$total_under_7}` ราย\n";
    $message .= $list_under_7 . "\n";
} else {
    $message .= "🟢 *ระดับปกติ (<= 7 วัน):* 0 ราย\n\n";
}

$grand_total = $total_over_30 + $total_8_30 + $total_under_7;
$message .= "📊 *ยอดรวมทั้งหมด:* `{$grand_total}` ราย\n\n";
$message .= "🔗 [👉 เข้าสู่ระบบเพื่อดูรายละเอียด](http://YOUR_PRIVATE_SERVER:8000/)";

// ส่งไปยัง Telegram API
$url = "https://api.telegram.org/bot{$bot_token}/sendMessage";
$post_fields = [
    'chat_id' => $chat_id,
    'text' => $message,
    'parse_mode' => 'Markdown',
    'disable_web_page_preview' => true
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$result = curl_exec($ch);
curl_close($ch);

$res = json_decode($result, true);

if (isset($res['ok']) && $res['ok'] == true) {
    echo "<script>alert('ส่งข้อความสรุปกลุ่มเข้า Telegram สำเร็จเรียบร้อย!'); window.location.href='dashboard_summary.php';</script>";
} else {
    $error_desc = isset($res['description']) ? $res['description'] : 'Unknown error';
    $error_desc = str_replace("'", "\'", $error_desc);
    echo "<script>alert('เกิดข้อผิดพลาดในการส่ง: {$error_desc}'); window.location.href='dashboard_summary.php';</script>";
}
?>
