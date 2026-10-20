<?php
// cron_notify.php
// ใช้รันผ่าน Windows Task Scheduler (CLI) ทุกๆ 1 นาที
ini_set('max_execution_time', 0); // ไม่จำกัดเวลา
date_default_timezone_set('Asia/Bangkok');

require_once __DIR__ . '/db_config.php';
$sqlite_db_path = __DIR__ . '/local_security.sqlite';
$local_pdo = new PDO('sqlite:' . $sqlite_db_path);
$local_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$current_time = date('H:i');
$current_date = date('Y-m-d');

// ดึงรอบแจ้งเตือนทั้งหมดที่เปิดใช้งานอยู่
$stmt = $local_pdo->query("SELECT * FROM notify_schedule WHERE is_active = 1");
$all_schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

$schedules = [];
foreach ($all_schedules as $sch) {
    $notify_time = $sch['notify_time'];
    $run_key = $current_date . '_RUN_' . $notify_time;
    
    // ถ้าเวลาปัจจุบันเลยหรือตรงกับเวลาที่ตั้งไว้
    if ($current_time >= $notify_time) {
        // ตรวจสอบในฐานข้อมูลว่าเคยรันของวันนี้ไปหรือยัง
        $chk = $local_pdo->prepare("SELECT COUNT(*) FROM cron_history WHERE run_key = :run_key");
        $chk->execute([':run_key' => $run_key]);
        
        if ($chk->fetchColumn() == 0) {
            // ยังไม่ได้รัน ให้ลอง Insert ลงฐานข้อมูล
            // ใช้ Try/Catch เพื่อป้องกันกรณีมีหลาย Process รันพร้อมกัน (Concurrency)
            try {
                $ins = $local_pdo->prepare("INSERT INTO cron_history (run_key) VALUES (:run_key)");
                $ins->execute([':run_key' => $run_key]);
                // ถ้า Insert ผ่าน แปลว่าได้สิทธิ์รัน
                $schedules[] = $sch;
            } catch (Exception $e) {
                // ถ้า Insert ไม่ผ่าน (ชนกับ Process อื่นที่รันไปเสี้ยววินาทีก่อนหน้า) จะถูกข้ามไป
            }
        }
    }
}

if (count($schedules) === 0) {
    echo "[$current_date $current_time] No pending schedules to run.\n";
    exit;
}

// โหลดคอนฟิก
$config_file = __DIR__ . '/telegram_config.json';
$bot_token = '';
$group_chat_id = '';
if (file_exists($config_file)) {
    $config_content = file_get_contents($config_file);
    $config = json_decode($config_content, true);
    if (is_array($config)) {
        $bot_token = $config['bot_token'] ?? '';
        $group_chat_id = $config['chat_id'] ?? '';
    }
}

if (empty($bot_token)) {
    echo "[$current_date $current_time] Error: Bot Token is missing.\n";
    exit;
}

echo "[$current_date $current_time] Found " . count($schedules) . " schedules to run.\n";

$start_date = date('Y-m-01');
$end_date = date('Y-m-d');
$thai_year = date('Y') + 543;
$date_str = date('d/m/') . $thai_year;

foreach ($schedules as $sch) {
    if ($sch['notify_type'] === 'group') {
        echo " - Executing Group Notification...\n";
        send_group_notification($pdo, $bot_token, $group_chat_id, $start_date, $end_date, $date_str);
    } elseif ($sch['notify_type'] === 'personal') {
        echo " - Executing Personal Notification...\n";
        send_personal_notification($pdo, $local_pdo, $bot_token, $start_date, $end_date, $date_str);
    }
}

echo "[$current_date $current_time] Done.\n";

// ฟังก์ชันส่งกลุ่ม
function send_group_notification($pdo, $bot_token, $group_chat_id, $start_date, $end_date, $date_str) {
    if (empty($group_chat_id)) {
        echo "   -> Error: Group Chat ID is missing.\n";
        return;
    }

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
    $stmt->execute([':start_date' => $start_date, ':end_date' => $end_date]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $total_over_30 = 0; $total_8_30 = 0; $total_under_7 = 0;
    $list_over_30 = ""; $list_8_30 = ""; $list_under_7 = "";

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

    $message = "🏥 *รายงานสรุปชาร์ตค้าง (ภาพรวมกลุ่ม)* 🏥\n";
    $message .= "📅 ประจำวันที่: {$date_str}\n\n";

    if ($total_over_30 > 0) {
        $message .= "🔴 *ระดับวิกฤต (> 30 วัน):* `{$total_over_30}` ราย\n{$list_over_30}\n";
    } else {
        $message .= "🔴 *ระดับวิกฤต (> 30 วัน):* 0 ราย ✅\n\n";
    }

    if ($total_8_30 > 0) {
        $message .= "🟡 *ระดับต้องเตือน (8-30 วัน):* `{$total_8_30}` ราย\n{$list_8_30}\n";
    } else {
        $message .= "🟡 *ระดับต้องเตือน (8-30 วัน):* 0 ราย ✅\n\n";
    }

    if ($total_under_7 > 0) {
        $message .= "🟢 *ระดับปกติ (<= 7 วัน):* `{$total_under_7}` ราย\n{$list_under_7}\n";
    } else {
        $message .= "🟢 *ระดับปกติ (<= 7 วัน):* 0 ราย\n\n";
    }

    $grand_total = $total_over_30 + $total_8_30 + $total_under_7;
    $message .= "📊 *ยอดรวมทั้งหมด:* `{$grand_total}` ราย\n\n";
    $message .= "🔗 [👉 เข้าสู่ระบบเพื่อดูรายละเอียด](http://YOUR_PRIVATE_SERVER:8000/)";

    call_telegram_api($bot_token, $group_chat_id, $message);
    echo "   -> Group notification sent.\n";
}

// ฟังก์ชันส่งรายบุคคล
function send_personal_notification($pdo, $local_pdo, $bot_token, $start_date, $end_date, $date_str) {
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
    $stmt->execute([':start_date' => $start_date, ':end_date' => $end_date]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt_chat = $local_pdo->query("SELECT doctor_code, chat_id FROM doctor_telegram");
    $telegram_configs = [];
    while ($row = $stmt_chat->fetch(PDO::FETCH_ASSOC)) {
        if (!empty($row['chat_id'])) {
            $telegram_configs[$row['doctor_code']] = $row['chat_id'];
        }
    }

    $sent = 0; $skipped = 0;
    foreach ($results as $row) {
        $doctor_code = $row['doctor_code'];
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
            
            call_telegram_api($bot_token, $chat_id, $message);
            $sent++;
        } else {
            $skipped++;
        }
    }
    echo "   -> Personal notification sent to $sent doctors ($skipped skipped due to no chat ID).\n";
}

function call_telegram_api($bot_token, $chat_id, $message) {
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
    // Ignore SSL certificate checking for local development/servers if needed
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}
