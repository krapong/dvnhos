<?php
require 'db.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'pd_user', 'both_user'])) {
    echo json_encode(['success' => false, 'message' => 'ไม่มีสิทธิ์เข้าถึงข้อมูล API (PD)']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action == 'save_record') {
    if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'pd_user'])) {
        echo json_encode(['success' => false, 'message' => 'ไม่มีสิทธิ์ในการบันทึกข้อมูล (PD)']);
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
    $ktv_collection = (int)($_POST['ktv_collection'] ?? 0);
    $pd_fluid_check = (int)($_POST['pd_fluid_check'] ?? 0);
    $peritonitis = (int)($_POST['peritonitis'] ?? 0);
    $exitsite_infection = (int)($_POST['exitsite_infection'] ?? 0);
    
    if (empty($record_date)) {
        echo json_encode(['success' => false, 'message' => 'กรุณาระบุวันที่']);
        exit;
    }

    // Calculate Fiscal Year: if month >= 10, fiscal year is year + 1
    $dateObj = new DateTime($record_date);
    $month = (int)$dateObj->format('m');
    $year = (int)$dateObj->format('Y');
    // Thai Fiscal year standard: starts Oct 1. year in DB should probably be Buddhist year or just AD year. 
    // Usually, 2569 fiscal year means Oct 2025 - Sep 2026. 
    // We'll store standard AD year of the fiscal year end. 
    // e.g. Oct 2025 -> FY 2026.
    $fiscal_year = ($month >= 10) ? $year + 1 : $year;

    try {
        $stmt = $pdo->prepare("
            INSERT INTO pd_daily_records (
                record_date, fiscal_year, service_count, admit_count, refer_count, wound_dressing, epo_injection, transfer_line_change, emergency_refer, new_training, retraining, pet_collection, ktv_collection, pd_fluid_check, peritonitis, exitsite_infection
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                service_count = VALUES(service_count), 
                admit_count = VALUES(admit_count),
                refer_count = VALUES(refer_count),
                wound_dressing = VALUES(wound_dressing),
                epo_injection = VALUES(epo_injection),
                transfer_line_change = VALUES(transfer_line_change),
                emergency_refer = VALUES(emergency_refer),
                new_training = VALUES(new_training),
                retraining = VALUES(retraining),
                pet_collection = VALUES(pet_collection),
                ktv_collection = VALUES(ktv_collection),
                pd_fluid_check = VALUES(pd_fluid_check),
                peritonitis = VALUES(peritonitis),
                exitsite_infection = VALUES(exitsite_infection)
        ");
        $stmt->execute([
            $record_date, $fiscal_year, $service_count, $admit_count, $refer_count, $wound_dressing, $epo_injection, $transfer_line_change, $emergency_refer, $new_training, $retraining, $pet_collection, $ktv_collection, $pd_fluid_check, $peritonitis, $exitsite_infection
        ]);

        // --- Telegram Notification Logic ---
        require_once 'telegram_helper.php';
        
        $stmt_total = $pdo->prepare("
            SELECT 
                (SELECT SUM(service_count) FROM pd_daily_records WHERE MONTH(record_date) = ? AND YEAR(record_date) = ?) as monthly_total,
                (SELECT SUM(service_count) FROM pd_daily_records WHERE fiscal_year = ?) as yearly_total
        ");
        $stmt_total->execute([$month, $year, $fiscal_year]);
        $totals = $stmt_total->fetch();
        
        $monthly_total = $totals['monthly_total'] ?? 0;
        $yearly_total = $totals['yearly_total'] ?? 0;

        $thai_months = ["", "ม.ค.", "ก.พ.", "มี.ค.", "เม.ย.", "พ.ค.", "มิ.ย.", "ก.ค.", "ส.ค.", "ก.ย.", "ต.ค.", "พ.ย.", "ธ.ค."];
        $thai_month_str = $thai_months[$month] . ' ' . substr(($year + 543), 2, 2);
        
        $username = $_SESSION['username'] ?? 'Unknown';
        
        $msg = "🚨 <b>มีการบันทึก/อัปเดตข้อมูลใหม่ (แผนก PD)</b>\n";
        $msg .= "📅 <b>วันที่ปฏิบัติงาน:</b> " . date('d/m/Y', strtotime($record_date)) . "\n";
        $msg .= "👤 <b>ผู้บันทึก:</b> {$username}\n\n";
        $msg .= "📊 <b>ข้อมูลที่บันทึกของวันนี้:</b>\n";
        
        if ($service_count > 0) $msg .= "- ผู้รับบริการ PD: {$service_count} ราย\n";
        if ($admit_count > 0) $msg .= "- Admit: {$admit_count} ราย\n";
        if ($refer_count > 0) $msg .= "- Refer: {$refer_count} ราย\n";
        if ($wound_dressing > 0) $msg .= "- ทำแผล (Wound dressing): {$wound_dressing} ราย\n";
        if ($epo_injection > 0) $msg .= "- ฉีดยา EPO: {$epo_injection} ราย\n";
        if ($transfer_line_change > 0) $msg .= "- เปลี่ยนสาย Transfer line: {$transfer_line_change} ราย\n";
        if ($emergency_refer > 0) $msg .= "- ฉุกเฉิน (Refer): {$emergency_refer} ราย\n";
        if ($new_training > 0) $msg .= "- Training รายใหม่: {$new_training} ราย\n";
        if ($retraining > 0) $msg .= "- Retraining: {$retraining} ราย\n";
        if ($pet_collection > 0) $msg .= "- เก็บ PET: {$pet_collection} ราย\n";
        if ($ktv_collection > 0) $msg .= "- เก็บ KT/V: {$ktv_collection} ราย\n";
        if ($pd_fluid_check > 0) $msg .= "- ส่งตรวจน้ำยาล้างไต: {$pd_fluid_check} ราย\n";
        if ($peritonitis > 0) $msg .= "- Peritonitis: {$peritonitis} ราย\n";
        if ($exitsite_infection > 0) $msg .= "- Exit site infection: {$exitsite_infection} ราย\n";
        
        $msg .= "\n📈 <b>สรุปยอดสะสม (เฉพาะยอดผู้รับบริการ PD):</b>\n";
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
    if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'pd_user'])) {
        echo json_encode(['success' => false, 'message' => 'ไม่มีสิทธิ์ในการลบข้อมูล (PD)']);
        exit;
    }

    $id = $_POST['id'] ?? '';
    if (empty($id)) {
        echo json_encode(['success' => false, 'message' => 'ไม่พบรหัสที่ต้องการลบ']);
        exit;
    }
    try {
        $stmt = $pdo->prepare("DELETE FROM pd_daily_records WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'ลบข้อมูลเรียบร้อยแล้ว']);
    } catch (\PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

if ($action == 'get_records') {
    // Get recent records for the table
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    try {
        $stmt = $pdo->prepare("SELECT * FROM pd_daily_records ORDER BY record_date DESC LIMIT ?");
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
    // Current Fiscal Year calculation
    $currentMonth = (int)date('m');
    $currentYear = (int)date('Y');
    $defaultFy = ($currentMonth >= 10) ? $currentYear + 1 : $currentYear;
    $fy = isset($_GET['fy']) ? (int)$_GET['fy'] : $defaultFy;

    try {
        // Summary Cards - SUM all 14 metrics
        $stmt = $pdo->prepare("
            SELECT 
                SUM(service_count) as total_service, 
                SUM(admit_count) as total_admit,
                SUM(refer_count) as total_refer,
                SUM(wound_dressing) as total_wound,
                SUM(epo_injection) as total_epo,
                SUM(transfer_line_change) as total_transfer,
                SUM(emergency_refer) as total_emer,
                SUM(new_training) as total_new_train,
                SUM(retraining) as total_retrain,
                SUM(pet_collection) as total_pet,
                SUM(ktv_collection) as total_ktv,
                SUM(pd_fluid_check) as total_pd_fluid,
                SUM(peritonitis) as total_peri,
                SUM(exitsite_infection) as total_exit
            FROM pd_daily_records 
            WHERE fiscal_year = ?
        ");
        $stmt->execute([$fy]);
        $summary = $stmt->fetch();

        // Monthly Data for charts
        $stmt = $pdo->prepare("
            SELECT 
                MONTH(record_date) as month,
                YEAR(record_date) as year,
                SUM(service_count) as monthly_service, 
                SUM(admit_count) as monthly_admit 
            FROM pd_daily_records 
            WHERE fiscal_year = ?
            GROUP BY YEAR(record_date), MONTH(record_date)
            ORDER BY YEAR(record_date), MONTH(record_date)
        ");
        $stmt->execute([$fy]);
        $monthly_data = $stmt->fetchAll();

        // Sort monthly data by Fiscal Year months (Oct -> Sep)
        $ordered_months = [];
        for ($m = 10; $m <= 12; $m++) {
            $ordered_months[] = ['month' => $m, 'year' => $fy - 1, 'monthly_service' => 0, 'monthly_admit' => 0];
        }
        for ($m = 1; $m <= 9; $m++) {
            $ordered_months[] = ['month' => $m, 'year' => $fy, 'monthly_service' => 0, 'monthly_admit' => 0];
        }

        foreach ($ordered_months as &$om) {
            foreach ($monthly_data as $md) {
                if ($md['month'] == $om['month'] && $md['year'] == $om['year']) {
                    $om['monthly_service'] = $md['monthly_service'];
                    $om['monthly_admit'] = $md['monthly_admit'];
                }
            }
        }

        echo json_encode([
            'success' => true, 
            'fy' => $fy,
            'summary' => [
                'total_service' => $summary['total_service'] ?? 0,
                'total_admit' => $summary['total_admit'] ?? 0,
                'total_refer' => $summary['total_refer'] ?? 0,
                'total_wound' => $summary['total_wound'] ?? 0,
                'total_epo' => $summary['total_epo'] ?? 0,
                'total_transfer' => $summary['total_transfer'] ?? 0,
                'total_emer' => $summary['total_emer'] ?? 0,
                'total_new_train' => $summary['total_new_train'] ?? 0,
                'total_retrain' => $summary['total_retrain'] ?? 0,
                'total_pet' => $summary['total_pet'] ?? 0,
                'total_ktv' => $summary['total_ktv'] ?? 0,
                'total_pd_fluid' => $summary['total_pd_fluid'] ?? 0,
                'total_peri' => $summary['total_peri'] ?? 0,
                'total_exit' => $summary['total_exit'] ?? 0
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
        $stmt = $pdo->prepare("SELECT * FROM pd_daily_records WHERE MONTH(record_date) = ? AND YEAR(record_date) = ? ORDER BY record_date ASC");
        $stmt->execute([$month, $target_year]);
        $month_records = $stmt->fetchAll();

        $stmt = $pdo->prepare("
            SELECT 
                SUM(service_count) as service_count, SUM(admit_count) as admit_count,
                SUM(refer_count) as refer_count, SUM(wound_dressing) as wound_dressing,
                SUM(epo_injection) as epo_injection, SUM(transfer_line_change) as transfer_line_change,
                SUM(emergency_refer) as emergency_refer, SUM(new_training) as new_training,
                SUM(retraining) as retraining, SUM(pet_collection) as pet_collection,
                SUM(ktv_collection) as ktv_collection, SUM(pd_fluid_check) as pd_fluid_check,
                SUM(peritonitis) as peritonitis, SUM(exitsite_infection) as exitsite_infection
            FROM pd_daily_records 
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
