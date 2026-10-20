<?php
require_once 'auth.php';
require_once 'db_config.php';
require_once 'local_db.php';

$config_file = 'telegram_config.json';
$bot_token = '';

if (file_exists($config_file)) {
    $config_content = file_get_contents($config_file);
    $config = json_decode($config_content, true);
    if (is_array($config)) {
        $bot_token = $config['bot_token'] ?? '';
    }
}

if (empty($bot_token)) {
    die("<script>alert('กรุณาตั้งค่า Telegram Bot Token ที่หน้า Settings ก่อนใช้งาน'); window.location.href='settings.php';</script>");
}

// ดึงข้อมูลสำหรับเดือนปัจจุบัน
$start_date = date('Y-m-01');
$end_date = date('Y-m-d');

// ดึงสรุปค้างชาร์ตรายแพทย์
$sql = "
    SELECT 
        d.code AS doctor_code,
        IFNULL(d.name, 'ไม่ระบุแพทย์') AS doctor_name,
        SUM(CASE WHEN DATEDIFF(CURDATE(), i.dchdate) > 7 THEN 1 ELSE 0 END) AS over_7_days,
        SUM(CASE WHEN DATEDIFF(CURDATE(), i.dchdate) <= 7 THEN 1 ELSE 0 END) AS under_7_days,
        COUNT(i.an) AS total_pending
    FROM ipt i
    LEFT JOIN doctor d ON d.code = i.admdoctor
    WHERE i.dchdate BETWEEN :start_date AND :end_date
      AND NOT EXISTS (
          SELECT 1 FROM ipt_doctor_diag id WHERE id.an = i.an AND id.diag_text IS NOT NULL AND TRIM(id.diag_text) != ''
      )
    GROUP BY d.code, d.name
    HAVING total_pending > 0
";

$stmt = $pdo->prepare($sql);
$stmt->bindParam(':start_date', $start_date);
$stmt->bindParam(':end_date', $end_date);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ดึงข้อมูล Chat ID ของแพทย์จาก SQLite
$stmt = $local_pdo->query("SELECT doctor_code, chat_id FROM doctor_telegram");
$telegram_configs = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    if (!empty($row['chat_id'])) {
        $telegram_configs[$row['doctor_code']] = $row['chat_id'];
    }
}

$sent_count = 0;
$error_count = 0;
$no_chat_id_count = 0;

$thai_year = date('Y') + 543;
$date_str = date('d/m/') . $thai_year;

foreach ($results as $row) {
    $doctor_code = $row['doctor_code'];
    
    // ตรวจสอบว่าแพทย์ท่านนี้ตั้งค่า Chat ID ไว้หรือไม่
    if (isset($telegram_configs[$doctor_code])) {
        $chat_id = $telegram_configs[$doctor_code];
        
        $message = "🚨 *แจ้งเตือนชาร์ตค้างส่วนตัว* 🚨\n";
        $message .= "เรียน นพ./พญ. {$row['doctor_name']}\n";
        $message .= "📅 ประจำวันที่: {$date_str}\n\n";
        $message .= "ท่านมีชาร์ตค้างสรุปดังนี้:\n";
        $message .= "🔴 เกิน 7 วัน (ด่วน): `{$row['over_7_days']}` ราย\n";
        $message .= "🟡 ไม่เกิน 7 วัน: `{$row['under_7_days']}` ราย\n";
        $message .= "📊 *รวมทั้งหมด:* `{$row['total_pending']}` ราย\n\n";
        $message .= "กรุณาเข้าระบบ HOSxP เพื่อดำเนินการสรุปชาร์ตให้ครบถ้วน ขอบคุณครับ\n";
        
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
            $sent_count++;
        } else {
            $error_count++;
        }
    } else {
        $no_chat_id_count++;
    }
}

echo "<script>
    alert('ส่งข้อความสำเร็จ {$sent_count} ท่าน\\nเกิดข้อผิดพลาด {$error_count} ท่าน\\nแพทย์ที่ไม่ได้ตั้ง Chat ID {$no_chat_id_count} ท่าน'); 
    window.location.href='dashboard_summary.php';
</script>";
?>
