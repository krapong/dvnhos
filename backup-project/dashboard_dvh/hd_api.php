<?php
require 'db.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'hd_user', 'both_user'])) {
    echo json_encode(['success' => false, 'message' => 'ไม่มีสิทธิ์เข้าถึงข้อมูล API (HD)']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action == 'save_record') {
    if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'hd_user'])) {
        echo json_encode(['success' => false, 'message' => 'ไม่มีสิทธิ์ในการบันทึกข้อมูล (HD)']);
        exit;
    }

    $record_date = $_POST['record_date'] ?? '';
    $service_count = (int)($_POST['service_count'] ?? 0);
    $admit_count = (int)($_POST['admit_count'] ?? 0);
    $refer_count = (int)($_POST['refer_count'] ?? 0);
    $wound_dressing = (int)($_POST['wound_dressing'] ?? 0);
    $epo_injection = (int)($_POST['epo_injection'] ?? 0);
    $transfer_line_change = (int)($_POST['transfer_line_change'] ?? 0);
    $emergency_refer = (int)($_POST['emergency_refer'] ?? 0);
    $new_training = (int)($_POST['new_training'] ?? 0);
    $retraining = (int)($_POST['retraining'] ?? 0);
    $pet_collection = (int)($_POST['pet_collection'] ?? 0);
    
    if (empty($record_date)) {
        echo json_encode(['success' => false, 'message' => 'กรุณาระบุวันที่']);
        exit;
    }

    $dateObj = new DateTime($record_date);
    $month = (int)$dateObj->format('m');
    $year = (int)$dateObj->format('Y');
    $fiscal_year = ($month >= 10) ? $year + 1 : $year;

    $service_count = $_POST['service_count'] ?? 0;
    $hd_count = $_POST['hd_count'] ?? 0;
    $admit_count = $_POST['admit_count'] ?? 0;
    $refer_opd = $_POST['refer_opd'] ?? 0;
    $emergency = $_POST['emergency'] ?? 0;
    $refer_er = $_POST['refer_er'] ?? 0;
    $blood_transfusion = $_POST['blood_transfusion'] ?? 0;
    $off_dlc = $_POST['off_dlc'] ?? 0;
    $off_perm = $_POST['off_perm'] ?? 0;
    $insert_dlc = $_POST['insert_dlc'] ?? 0;
    $ro_cs_bacteria = $_POST['ro_cs_bacteria'] ?? 0;
    $ro_endotoxin = $_POST['ro_endotoxin'] ?? 0;
    $counselling = $_POST['counselling'] ?? 0;
    $injection = $_POST['injection'] ?? 0;
    $wound_dressing = $_POST['wound_dressing'] ?? 0;
    $suture = $_POST['suture'] ?? 0;
    $remove_suture = $_POST['remove_suture'] ?? 0;
    $hypotension = $_POST['hypotension'] ?? 0;
    $hypertension = $_POST['hypertension'] ?? 0;
    $muscle_cramp = $_POST['muscle_cramp'] ?? 0;
    $headache = $_POST['headache'] ?? 0;
    $fever = $_POST['fever'] ?? 0;
    $fever_after = $_POST['fever_after'] ?? 0;

    try {
        $stmt = $pdo->prepare("
            INSERT INTO hd_daily_records 
            (record_date, fiscal_year, service_count, hd_count, admit_count, refer_opd, emergency, refer_er, blood_transfusion, off_dlc, off_perm, insert_dlc, ro_cs_bacteria, ro_endotoxin, counselling, injection, wound_dressing, suture, remove_suture, hypotension, hypertension, muscle_cramp, headache, fever, fever_after)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            service_count=VALUES(service_count),
            hd_count=VALUES(hd_count),
            admit_count=VALUES(admit_count),
            refer_opd=VALUES(refer_opd),
            emergency=VALUES(emergency),
            refer_er=VALUES(refer_er),
            blood_transfusion=VALUES(blood_transfusion),
            off_dlc=VALUES(off_dlc),
            off_perm=VALUES(off_perm),
            insert_dlc=VALUES(insert_dlc),
            ro_cs_bacteria=VALUES(ro_cs_bacteria),
            ro_endotoxin=VALUES(ro_endotoxin),
            counselling=VALUES(counselling),
            injection=VALUES(injection),
            wound_dressing=VALUES(wound_dressing),
            suture=VALUES(suture),
            remove_suture=VALUES(remove_suture),
            hypotension=VALUES(hypotension),
            hypertension=VALUES(hypertension),
            muscle_cramp=VALUES(muscle_cramp),
            headache=VALUES(headache),
            fever=VALUES(fever),
            fever_after=VALUES(fever_after)
        ");
        $stmt->execute([
            $record_date, $fiscal_year, $service_count, $hd_count, $admit_count, $refer_opd, $emergency, $refer_er, $blood_transfusion, $off_dlc, $off_perm, $insert_dlc, $ro_cs_bacteria, $ro_endotoxin, $counselling, $injection, $wound_dressing, $suture, $remove_suture, $hypotension, $hypertension, $muscle_cramp, $headache, $fever, $fever_after
        ]);

        // --- Telegram Notification Logic ---
        require_once 'telegram_helper.php';
        
        $stmt_total = $pdo->prepare("
            SELECT 
                (SELECT SUM(service_count) FROM hd_daily_records WHERE MONTH(record_date) = ? AND YEAR(record_date) = ?) as monthly_total,
                (SELECT SUM(service_count) FROM hd_daily_records WHERE fiscal_year = ?) as yearly_total
        ");
        $stmt_total->execute([$month, $year, $fiscal_year]);
        $totals = $stmt_total->fetch();
        
        $monthly_total = $totals['monthly_total'] ?? 0;
        $yearly_total = $totals['yearly_total'] ?? 0;

        $thai_months = ["", "ม.ค.", "ก.พ.", "มี.ค.", "เม.ย.", "พ.ค.", "มิ.ย.", "ก.ค.", "ส.ค.", "ก.ย.", "ต.ค.", "พ.ย.", "ธ.ค."];
        $thai_month_str = $thai_months[$month] . ' ' . substr(($year + 543), 2, 2);
        
        $username = $_SESSION['username'] ?? 'Unknown';
        
        $msg = "🚨 <b>มีการบันทึก/อัปเดตข้อมูลใหม่ (แผนก HD)</b>\n";
        $msg .= "📅 <b>วันที่ปฏิบัติงาน:</b> " . date('d/m/Y', strtotime($record_date)) . "\n";
        $msg .= "👤 <b>ผู้บันทึก:</b> {$username}\n\n";
        $msg .= "📊 <b>ข้อมูลที่บันทึกของวันนี้:</b>\n";
        
        if ($service_count > 0) $msg .= "- ผู้รับบริการ HD: {$service_count} ราย\n";
        if ($hd_count > 0) $msg .= "- HD Count: {$hd_count} ราย\n";
        if ($admit_count > 0) $msg .= "- Admit: {$admit_count} ราย\n";
        if ($refer_opd > 0) $msg .= "- Refer OPD: {$refer_opd} ราย\n";
        if ($emergency > 0) $msg .= "- ฉุกเฉิน: {$emergency} ราย\n";
        if ($refer_er > 0) $msg .= "- Refer ER: {$refer_er} ราย\n";
        if ($blood_transfusion > 0) $msg .= "- Blood Transfusion: {$blood_transfusion} ราย\n";
        if ($off_dlc > 0) $msg .= "- Off DLC: {$off_dlc} ราย\n";
        if ($off_perm > 0) $msg .= "- Off Perm: {$off_perm} ราย\n";
        if ($insert_dlc > 0) $msg .= "- Insert DLC: {$insert_dlc} ราย\n";
        if ($ro_cs_bacteria > 0) $msg .= "- RO C/S Bacteria: {$ro_cs_bacteria} ราย\n";
        if ($ro_endotoxin > 0) $msg .= "- RO Endotoxin: {$ro_endotoxin} ราย\n";
        if ($counselling > 0) $msg .= "- Counselling: {$counselling} ราย\n";
        if ($injection > 0) $msg .= "- Injection: {$injection} ราย\n";
        if ($wound_dressing > 0) $msg .= "- Wound Dressing: {$wound_dressing} ราย\n";
        if ($suture > 0) $msg .= "- Suture: {$suture} ราย\n";
        if ($remove_suture > 0) $msg .= "- Remove Suture: {$remove_suture} ราย\n";
        if ($hypotension > 0) $msg .= "- Hypotension: {$hypotension} ราย\n";
        if ($hypertension > 0) $msg .= "- Hypertension: {$hypertension} ราย\n";
        if ($muscle_cramp > 0) $msg .= "- Muscle Cramp: {$muscle_cramp} ราย\n";
        if ($headache > 0) $msg .= "- Headache: {$headache} ราย\n";
        if ($fever > 0) $msg .= "- Fever: {$fever} ราย\n";
        if ($fever_after > 0) $msg .= "- Fever After: {$fever_after} ราย\n";
        
        $msg .= "\n📈 <b>สรุปยอดสะสม (เฉพาะยอดผู้รับบริการ HD):</b>\n";
        $msg .= "- <b>รวมเดือนนี้ ({$thai_month_str}):</b> " . number_format($monthly_total) . " ราย\n";
        $msg .= "- <b>รวมปีงบประมาณนี้ (" . ($fiscal_year + 543) . "):</b> " . number_format($yearly_total) . " ราย\n\n";
        $msg .= "⏰ <i>เวลาที่บันทึก: " . date('d/m/Y H:i') . "</i>";
        
        sendTelegramNotify($msg);
        // ----------------------------------------

        echo json_encode(['success' => true, 'message' => 'บันทึกข้อมูลเรียบร้อยแล้ว']);
    } catch (\PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

if ($action == 'delete_record') {
    if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'hd_user'])) {
        echo json_encode(['success' => false, 'message' => 'ไม่มีสิทธิ์ในการลบข้อมูล (HD)']);
        exit;
    }

    $id = $_POST['id'] ?? '';
    if (empty($id)) {
        echo json_encode(['success' => false, 'message' => 'ไม่พบรหัสที่ต้องการลบ']);
        exit;
    }
    try {
        $stmt = $pdo->prepare("DELETE FROM hd_daily_records WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'ลบข้อมูลเรียบร้อยแล้ว']);
    } catch (\PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

if ($action == 'get_records') {
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    try {
        $stmt = $pdo->prepare("SELECT * FROM hd_daily_records ORDER BY record_date DESC LIMIT ?");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        $records = $stmt->fetchAll();
        echo json_encode(['success' => true, 'data' => $records]);
    } catch (\PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

if ($action == 'get_dashboard_data') {
    $currentMonth = (int)date('m');
    $currentYear = (int)date('Y');
    $defaultFy = ($currentMonth >= 10) ? $currentYear + 1 : $currentYear;
    $fy = isset($_GET['fy']) ? (int)$_GET['fy'] : $defaultFy;

    try {
        $stmt = $pdo->prepare("
            SELECT 
                SUM(service_count) as total_service_count,
                SUM(hd_count) as total_hd_count,
                SUM(admit_count) as total_admit_count,
                SUM(refer_opd) as total_refer_opd,
                SUM(emergency) as total_emergency,
                SUM(refer_er) as total_refer_er,
                SUM(blood_transfusion) as total_blood_transfusion,
                SUM(off_dlc) as total_off_dlc,
                SUM(off_perm) as total_off_perm,
                SUM(insert_dlc) as total_insert_dlc,
                SUM(ro_cs_bacteria) as total_ro_cs_bacteria,
                SUM(ro_endotoxin) as total_ro_endotoxin,
                SUM(counselling) as total_counselling,
                SUM(injection) as total_injection,
                SUM(wound_dressing) as total_wound_dressing,
                SUM(suture) as total_suture,
                SUM(remove_suture) as total_remove_suture,
                SUM(hypotension) as total_hypotension,
                SUM(hypertension) as total_hypertension,
                SUM(muscle_cramp) as total_muscle_cramp,
                SUM(headache) as total_headache,
                SUM(fever) as total_fever,
                SUM(fever_after) as total_fever_after
            FROM hd_daily_records 
            WHERE fiscal_year = ?
        ");
        $stmt->execute([$fy]);
        $summary = $stmt->fetch();

        $stmt = $pdo->prepare("
            SELECT MONTH(record_date) as month, YEAR(record_date) as year, 
                   SUM(service_count) as monthly_service, SUM(hd_count) as monthly_hd
            FROM hd_daily_records 
            WHERE fiscal_year = ?
            GROUP BY YEAR(record_date), MONTH(record_date)
            ORDER BY YEAR(record_date), MONTH(record_date)
        ");
        $stmt->execute([$fy]);
        $monthly_data = $stmt->fetchAll();

        $ordered_months = [];
        for ($m = 10; $m <= 12; $m++) {
            $ordered_months[] = ['month' => $m, 'year' => $fy - 1, 'monthly_service' => 0, 'monthly_hd' => 0];
        }
        for ($m = 1; $m <= 9; $m++) {
            $ordered_months[] = ['month' => $m, 'year' => $fy, 'monthly_service' => 0, 'monthly_hd' => 0];
        }

        foreach ($ordered_months as &$om) {
            foreach ($monthly_data as $md) {
                if ($md['month'] == $om['month'] && $md['year'] == $om['year']) {
                    $om['monthly_service'] = $md['monthly_service'];
                    $om['monthly_hd'] = $md['monthly_hd'];
                }
            }
        }

        echo json_encode([
            'success' => true, 
            'fy' => $fy,
            'summary' => [
                'total_service_count' => $summary['total_service_count'] ?? 0,
                'total_hd_count' => $summary['total_hd_count'] ?? 0,
                'total_admit_count' => $summary['total_admit_count'] ?? 0,
                'total_refer_opd' => $summary['total_refer_opd'] ?? 0,
                'total_emergency' => $summary['total_emergency'] ?? 0,
                'total_refer_er' => $summary['total_refer_er'] ?? 0,
                'total_blood_transfusion' => $summary['total_blood_transfusion'] ?? 0,
                'total_off_dlc' => $summary['total_off_dlc'] ?? 0,
                'total_off_perm' => $summary['total_off_perm'] ?? 0,
                'total_insert_dlc' => $summary['total_insert_dlc'] ?? 0,
                'total_ro_cs_bacteria' => $summary['total_ro_cs_bacteria'] ?? 0,
                'total_ro_endotoxin' => $summary['total_ro_endotoxin'] ?? 0,
                'total_counselling' => $summary['total_counselling'] ?? 0,
                'total_injection' => $summary['total_injection'] ?? 0,
                'total_wound_dressing' => $summary['total_wound_dressing'] ?? 0,
                'total_suture' => $summary['total_suture'] ?? 0,
                'total_remove_suture' => $summary['total_remove_suture'] ?? 0,
                'total_hypotension' => $summary['total_hypotension'] ?? 0,
                'total_hypertension' => $summary['total_hypertension'] ?? 0,
                'total_muscle_cramp' => $summary['total_muscle_cramp'] ?? 0,
                'total_headache' => $summary['total_headache'] ?? 0,
                'total_fever' => $summary['total_fever'] ?? 0,
                'total_fever_after' => $summary['total_fever_after'] ?? 0
            ],
            'chart_data' => $ordered_months
        ]);

    } catch (\PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

if ($action == 'get_excel_report') {
    $fy = isset($_GET['fy']) ? (int)$_GET['fy'] : (int)date('Y');
    $month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
    
    $target_year = ($month >= 10) ? $fy - 1 : $fy;
    $end_of_month = date('Y-m-t', strtotime(sprintf('%04d-%02d-01', $target_year, $month)));

    try {
        $stmt = $pdo->prepare("SELECT * FROM hd_daily_records WHERE MONTH(record_date) = ? AND YEAR(record_date) = ? ORDER BY record_date ASC");
        $stmt->execute([$month, $target_year]);
        $month_records = $stmt->fetchAll();

        $stmt = $pdo->prepare("
            SELECT 
                SUM(service_count) as total_service_count,
                SUM(hd_count) as total_hd_count,
                SUM(admit_count) as total_admit_count,
                SUM(refer_opd) as total_refer_opd,
                SUM(emergency) as total_emergency,
                SUM(refer_er) as total_refer_er,
                SUM(blood_transfusion) as total_blood_transfusion,
                SUM(off_dlc) as total_off_dlc,
                SUM(off_perm) as total_off_perm,
                SUM(insert_dlc) as total_insert_dlc,
                SUM(ro_cs_bacteria) as total_ro_cs_bacteria,
                SUM(ro_endotoxin) as total_ro_endotoxin,
                SUM(counselling) as total_counselling,
                SUM(injection) as total_injection,
                SUM(wound_dressing) as total_wound_dressing,
                SUM(suture) as total_suture,
                SUM(remove_suture) as total_remove_suture,
                SUM(hypotension) as total_hypotension,
                SUM(hypertension) as total_hypertension,
                SUM(muscle_cramp) as total_muscle_cramp,
                SUM(headache) as total_headache,
                SUM(fever) as total_fever,
                SUM(fever_after) as total_fever_after
            FROM hd_daily_records 
            WHERE fiscal_year = ? AND record_date <= ?
        ");
        $stmt->execute([$fy, $end_of_month]);
        $yearly_sums = $stmt->fetch();

        echo json_encode([
            'success' => true,
            'month_records' => $month_records,
            'yearly_sums' => $yearly_sums
        ]);
    } catch (\PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
