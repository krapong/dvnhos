<?php
// ปิดการพ่น HTML Error ออกมาปนกับ JSON
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST");

function apiJson(array $body, int $status=200): void {
    http_response_code($status);
    $json = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    echo $json === false ? '{"status":"error","message":"ไม่สามารถแปลงผลลัพธ์เป็น JSON"}' : $json;
    exit;
}
function opdRecordedStages(PDO $conn, string $start, string $end): array {
    $values=array_fill_keys(['registration','wait_triage','triage','doctor','consult'],[]);
    try {
        $stmt=$conn->prepare("SELECT t.vn,t.ovst_service_time_type_code type,t.service_begin_datetime started,t.service_end_datetime ended
            FROM ovst_service_time t JOIN ovst o ON o.vn=t.vn
            WHERE o.vstdate BETWEEN :s AND :e
            AND t.ovst_service_time_type_code IN ('OPD-NEW-VISIT','OPD-SCREEN','OPD-DOCTOR')
            ORDER BY t.service_begin_datetime,t.ovst_service_time_id");
        $stmt->execute([':s'=>$start,':e'=>$end]);$visits=[];
        while($r=$stmt->fetch(PDO::FETCH_ASSOC)) {
            // Retain the first recorded episode, even if incomplete, rather than
            // selecting a later episode just because it yields a positive wait.
            if(!isset($visits[$r['vn']][$r['type']]))$visits[$r['vn']][$r['type']]=$r;
        }
        $elapsed=static function($a,$b):?float {
            if(!$a||!$b||strpos($a,'0000-00-00')===0||strpos($b,'0000-00-00')===0)return null;
            $x=strtotime($a);$y=strtotime($b);if($x===false||$y===false)return null;
            $seconds=$y-$x;return $seconds>=0&&$seconds<=21600?$seconds/60:null;
        };
        foreach($visits as $v) {
            $r=$v['OPD-NEW-VISIT']??[];$s=$v['OPD-SCREEN']??[];$d=$v['OPD-DOCTOR']??[];
            $pairs=['registration'=>[$r['started']??null,$r['ended']??null],
                'wait_triage'=>[$r['ended']??null,$s['started']??null],
                'triage'=>[$s['started']??null,$s['ended']??null],
                'doctor'=>[$s['ended']??null,$d['started']??null],
                'consult'=>[$d['started']??null,$d['ended']??null]];
            foreach($pairs as $key=>$pair){$m=$elapsed($pair[0],$pair[1]);if($m!==null)$values[$key][]=$m;}
        }
    } catch(PDOException $e) { error_log('OPD recorded stages query failed: '.$e->getCode()); }
    $stats=[];foreach($values as $key=>$items)$stats[$key]=['samples'=>count($items),'avg_minutes'=>$items?round(array_sum($items)/count($items),1):null];
    return $stats;
}
function opdCashierToReceipt(PDO $conn, string $start, string $end): array {
    $result=['minutes'=>[], 'cash_entries'=>0, 'receipt_pairs'=>0, 'reversed_pairs'=>0, 'over_24h'=>0, 'source_available'=>false];
    try {
        $fields=opdReceiptFields($conn);
        if(!$fields)return $result;
        $table=$fields['table'];$vn=$fields['vn'];$time=$fields['time'];$date=$fields['date'];
        if(!in_array('status',opdTableColumns($conn,$table),true))return $result;
        $dateExpr=$date ? "COALESCE(NULLIF(NULLIF(r.$date,''),'0000-00-00'),o.vstdate)" : 'o.vstdate';
        // First entry and first OK receipt per VN; this is not a visit-close time.
        $stmt=$conn->prepare("SELECT q.entry_at,MIN(CONCAT($dateExpr,' ',r.$time)) receipt_at
            FROM ovst o
            JOIN (SELECT q.vn,MIN(q.queue_datetime) entry_at
                FROM opd_dep_queue q JOIN ovst v ON v.vn=q.vn
                WHERE v.vstdate BETWEEN :qs AND :qe AND q.depcode='016'
                AND q.queue_datetime>'0000-00-00 00:00:00' GROUP BY q.vn) q ON q.vn=o.vn
            LEFT JOIN $table r ON r.$vn=o.vn AND r.status='OK'
                AND r.$time IS NOT NULL AND r.$time<>''
            WHERE o.vstdate BETWEEN :s AND :e GROUP BY o.vn,q.entry_at");
        $stmt->execute([':qs'=>$start,':qe'=>$end,':s'=>$start,':e'=>$end]);
        while($row=$stmt->fetch(PDO::FETCH_ASSOC)) {
            $result['cash_entries']++;
            if(!$row['receipt_at'])continue;
            $from=strtotime($row['entry_at']);$to=strtotime($row['receipt_at']);
            if($from===false||$to===false)continue;
            $result['receipt_pairs']++;
            $seconds=$to-$from;
            if($seconds<0){$result['reversed_pairs']++;continue;}
            if($seconds>86400){$result['over_24h']++;continue;}
            $result['minutes'][]=$seconds/60;
        }
        $result['source_available']=true;
    } catch(PDOException $e) { error_log('OPD cashier-receipt query failed: '.$e->getCode()); }
    return $result;
}
function opdPharmacyQueueStats(PDO $conn, string $start, string $end): array {
    $result=['samples'=>0,'avg_minutes'=>null];
    try {
        // One entry per VN and dispensing department; TIME is anchored to visit date.
        $stmt=$conn->prepare("SELECT COUNT(*) samples,ROUND(AVG(elapsed_seconds)/60,1) avg_minutes
            FROM (SELECT TIMESTAMPDIFF(SECOND,q.entry_at,CONCAT(o.vstdate,' ',s.service7)) elapsed_seconds
                FROM ovst o JOIN service_time s ON s.vn=o.vn
                JOIN (SELECT q.vn,q.depcode,MIN(q.queue_datetime) entry_at
                    FROM opd_dep_queue q JOIN ovst v ON v.vn=q.vn
                    WHERE v.vstdate BETWEEN :qs AND :qe AND q.depcode IN ('006','046')
                    AND q.queue_datetime>'0000-00-00 00:00:00' GROUP BY q.vn,q.depcode) q
                    ON q.vn=o.vn AND q.depcode=s.service7_dep
                WHERE o.vstdate BETWEEN :s AND :e AND s.service7 IS NOT NULL) durations
            WHERE elapsed_seconds BETWEEN 0 AND 14400");
        $stmt->execute([':qs'=>$start,':qe'=>$end,':s'=>$start,':e'=>$end]);
        $row=$stmt->fetch(PDO::FETCH_ASSOC);
        $result=['samples'=>(int)$row['samples'],'avg_minutes'=>isset($row['avg_minutes'])?(float)$row['avg_minutes']:null];
    } catch(PDOException $e) { error_log('OPD pharmacy queue query failed: '.$e->getCode()); }
    return $result;
}
function opdReceiptFields(PDO $conn): array {
    foreach (['rcpt_print','rcp_print'] as $table) {
        try {
            $stmt=$conn->query('SHOW COLUMNS FROM `'.$table.'`');
            $columns=$stmt->fetchAll(PDO::FETCH_COLUMN,0);
            $vn=null; foreach (['vn','visitno'] as $candidate) if (in_array($candidate,$columns,true)) {$vn=$candidate;break;}
            $time=null; foreach (['bill_time','rcp_time','rcpt_time','payment_time','print_time'] as $candidate) if (in_array($candidate,$columns,true)) {$time=$candidate;break;}
            $date=null; foreach (['bill_date','rcp_date','rcpt_date','payment_date','print_date'] as $candidate) if (in_array($candidate,$columns,true)) {$date=$candidate;break;}
            if ($vn && $time) return ['table'=>$table,'vn'=>$vn,'time'=>$time,'date'=>$date];
        } catch (PDOException $e) { }
    }
    return [];
}
function opdReceiptVisitSource(PDO $conn): array {
    foreach (['rcpt_print','rcp_print'] as $table) {
        try {
            $columns=$conn->query('SHOW COLUMNS FROM `'.$table.'`')->fetchAll(PDO::FETCH_COLUMN,0);
            $vn=null; foreach (['vn','visitno'] as $candidate) if (in_array($candidate,$columns,true)) {$vn=$candidate;break;}
            if ($vn) return ['table'=>$table,'vn'=>$vn,'status'=>in_array('status',$columns,true)?'status':null];
        } catch (PDOException $e) { }
    }
    return [];
}
function opdTableColumns(PDO $conn, string $table): array {
    if (!in_array($table,['vn_stat','service_time','oapp','ovst','ovstdiag','opdscreen','kskdepartment','clinic','patient','rcpt_print','rcp_print'],true)) return [];
    try { return $conn->query('SHOW COLUMNS FROM `'.$table.'`')->fetchAll(PDO::FETCH_COLUMN,0); }
    catch (PDOException $e) { return []; }
}
function opdIsTimeOnly($value): bool {
    return is_string($value) && (bool)preg_match('/^\d{1,2}:\d{2}(?::\d{2})?$/', trim($value));
}
function opdServiceDateTime($value, string $visitDate): ?DateTimeImmutable {
    if ($value === null || trim((string)$value) === '' || strpos((string)$value, '0000-00-00') === 0) return null;
    $value = trim((string)$value);
    try {
        // HOSxP installations may store service_time events as TIME or DATETIME.
        return new DateTimeImmutable(opdIsTimeOnly($value) ? $visitDate.' '.$value : $value);
    } catch (Throwable $e) { return null; }
}
function opdElapsedServiceMinutes($fromValue, $toValue, string $visitDate, int $maxMinutes): ?int {
    $from = opdServiceDateTime($fromValue, $visitDate);
    $to = opdServiceDateTime($toValue, $visitDate);
    if (!$from || !$to) return null;
    $seconds = $to->getTimestamp() - $from->getTimestamp();
    // For time-only columns, account for a visit that crosses midnight.
    if ($seconds <= 0 && opdIsTimeOnly((string)$fromValue) && opdIsTimeOnly((string)$toValue)) $seconds += 86400;
    $minutes = (int)round($seconds / 60);
    return $minutes > 0 && $minutes <= $maxMinutes ? $minutes : null;
}
function opdClinicWaitStats(PDO $conn, string $start, string $end): array {
    // One sample per VN and examination room. Order timestamps must carry the same room code.
    $stmt=$conn->prepare("SELECT IFNULL(k.department,'ไม่ระบุห้องตรวจ') clinic_name,
        COUNT(*) total_visit,
        COUNT(CASE WHEN pairs.elapsed_seconds BETWEEN 0 AND 28800 THEN 1 END) wait_samples,
        SUM(CASE WHEN pairs.elapsed_seconds BETWEEN 0 AND 28800 THEN pairs.elapsed_seconds ELSE 0 END) wait_seconds_total,
        ROUND(AVG(CASE WHEN pairs.elapsed_seconds BETWEEN 0 AND 28800 THEN pairs.elapsed_seconds/60 END),1) avg_wait_min
        FROM (
            SELECT q.vn,q.depcode,TIMESTAMPDIFF(SECOND,q.queue_at,
                MIN(TIMESTAMP(i.rxdate,i.rxtime))) elapsed_seconds
            FROM (
                SELECT q.vn,q.depcode,MIN(q.queue_datetime) queue_at
                FROM ovst o JOIN opd_dep_queue q ON q.vn=o.vn
                JOIN kskdepartment room ON room.depcode=q.depcode
                WHERE o.vstdate BETWEEN :s AND :e
                AND q.queue_datetime>=:qs AND q.queue_datetime<DATE_ADD(:qe,INTERVAL 1 DAY)
                GROUP BY q.vn,q.depcode
            ) q
            LEFT JOIN opitemrece i ON i.vn=q.vn AND i.dep_code=q.depcode
                AND i.rxdate>'0000-00-00' AND i.rxtime>'00:00:00'
                AND TIMESTAMP(i.rxdate,i.rxtime)>=q.queue_at
                AND TIMESTAMP(i.rxdate,i.rxtime)<=DATE_ADD(q.queue_at,INTERVAL 8 HOUR)
                AND EXISTS (SELECT 1 FROM drugitems di WHERE di.icode=i.icode)
            GROUP BY q.vn,q.depcode,q.queue_at
        ) pairs LEFT JOIN kskdepartment k ON k.depcode=pairs.depcode
        GROUP BY pairs.depcode,k.department HAVING wait_samples > 0 AND avg_wait_min IS NOT NULL
        ORDER BY total_visit DESC,pairs.depcode LIMIT 5");
    $stmt->execute([':s'=>$start,':e'=>$end,':qs'=>$start,':qe'=>$end]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
function opdDoctorSaveStats(PDO $conn, string $startDate, string $endDate): array {
    $serviceColumns=opdTableColumns($conn,'service_time');
    if (!in_array('service4',$serviceColumns,true) || !in_array('service6',$serviceColumns,true)) {
        return ['samples'=>0,'avg_minutes'=>null,'clinics'=>[]];
    }
    // This HOSxP database has no visits with both service4 and service5 populated.
    // service4 -> service6 has usable real pairs and is used for this duration KPI.
    $duration="TIMESTAMPDIFF(MINUTE,s.service4,s.service6)";
    $sampleCondition="$duration BETWEEN 1 AND 120";
    $stmt=$conn->prepare("SELECT IFNULL(k.department,'ไม่ระบุแผนก') AS clinic_name,
                                 COUNT(DISTINCT o.vn) AS total_visit,
                                 COUNT(DISTINCT CASE WHEN $sampleCondition THEN o.vn END) AS consult_samples,
                                 ROUND(AVG(CASE WHEN $sampleCondition THEN $duration END),0) AS avg_consult_min
                          FROM ovst o
                          LEFT JOIN kskdepartment k ON k.depcode=o.main_dep
                          LEFT JOIN service_time s ON s.vn=o.vn
                          WHERE o.vstdate BETWEEN :s AND :e
                          GROUP BY o.main_dep,k.department
                          ORDER BY total_visit DESC
                          LIMIT 5");
    $stmt->execute([':s'=>$startDate,':e'=>$endDate]);
    $clinics=$stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmtTotal=$conn->prepare("SELECT COUNT(DISTINCT CASE WHEN $sampleCondition THEN o.vn END) AS samples,
                                      ROUND(AVG(CASE WHEN $sampleCondition THEN $duration END),0) AS avg_minutes
                               FROM ovst o LEFT JOIN service_time s ON s.vn=o.vn
                               WHERE o.vstdate BETWEEN :s AND :e");
    $stmtTotal->execute([':s'=>$startDate,':e'=>$endDate]);
    $total=$stmtTotal->fetch(PDO::FETCH_ASSOC)?:[];
    return ['samples'=>(int)($total['samples']??0),'avg_minutes'=>isset($total['avg_minutes'])?(int)$total['avg_minutes']:null,'clinics'=>$clinics];
}
set_exception_handler(static function(Throwable $error): void {
    error_log('OPD API failure: '.get_class($error).' code='.$error->getCode());
    apiJson(['status'=>'error','message'=>'API ทำงานไม่สำเร็จ ตรวจ PHP error log เพื่อดูรายละเอียดโดยไม่เปิดเผยข้อมูลเชื่อมต่อ'], 500);
});

// Keep credentials outside DocumentRoot. Set OPD_CONFIG_FILE to an absolute config path.
$defaultConfig = DIRECTORY_SEPARATOR === '\\'
    ? dirname(__DIR__, 2) . '/opd-command-center-private/config.php'
    : dirname(__DIR__, 2) . '/opd_dashboard_config.php';
$configFile = getenv('OPD_CONFIG_FILE') ?: $defaultConfig;
if (!is_file($configFile) || !is_readable($configFile)) {
    apiJson(['status'=>'error','message'=>'ไม่พบ config.php ที่อ่านได้: วางไฟล์นอกเว็บรูทที่ '.$defaultConfig.' หรือกำหนด OPD_CONFIG_FILE ให้ชี้ไปยัง path จริง'], 503);
}
$webRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? dirname(__DIR__));
$realConfig = realpath($configFile);
if ($webRoot && $realConfig && strpos(strtolower($realConfig), strtolower(rtrim($webRoot, '/\\')) . DIRECTORY_SEPARATOR) === 0) {
    apiJson(['status'=>'error','message'=>'ตำแหน่ง config ต้องอยู่นอกเว็บรูท'], 503);
}
$appConfig = require $realConfig;
if (!is_array($appConfig)) apiJson(['status'=>'error','message'=>'config.php ต้อง return array'], 503);
$action = isset($_GET['action']) && $_GET['action'] !== '' ? (string)$_GET['action'] : 'opd_management';
$dbConfig = $appConfig['database'] ?? [];
$host = $dbConfig['host'] ?? '';
$port = (int)($dbConfig['port'] ?? 3306);
$dbname = $dbConfig['name'] ?? '';
$username = $dbConfig['user'] ?? '';
$password = $dbConfig['password'] ?? '';
if ($host === '' || $dbname === '' || $username === '' || $username === 'root') {
    apiJson(['status'=>'error','message'=>'config.php ยังตั้งค่าฐานข้อมูลไม่ครบ หรือบัญชีฐานข้อมูลไม่เหมาะสำหรับอ่านรายงาน'], 503);
}

try {
    if (!extension_loaded('pdo_mysql')) apiJson(["status"=>"error","message"=>"PHP ยังไม่ได้เปิดส่วนขยาย pdo_mysql"], 503);
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
    ];
    $conn = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8", $username, $password, $options);
    $conn->exec('SET SESSION TRANSACTION READ ONLY');
} catch(PDOException $e) {
    error_log('OPD HOSxP connection failed: ' . $e->getCode());
    apiJson(["status" => "error", "message" => "เชื่อมต่อฐาน HOSxP ไม่สำเร็จ ตรวจ IP/port, firewall และบัญชีอ่านข้อมูล"], 503);
}

// รับค่าพารามิเตอร์จาก URL (ถ้าไม่ส่ง action มา ให้เป็น opd_management อัตโนมัติ)
$start_date = isset($_GET['start_date']) && !empty($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d');
$end_date   = isset($_GET['end_date']) && !empty($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

$data = [];

try {
    switch ($action) {
        case 'fiscal_service_times':
            $fiscalYear = (string)($_GET['fiscal_year'] ?? 'all');
            if ($fiscalYear !== 'all' && !preg_match('/^25[0-9]{2}$/D', $fiscalYear)) {
                apiJson(['status'=>'error','message'=>'ปีงบประมาณต้องเป็น พ.ศ. 2500–2599'],400);
            }
            $firstYear = $fiscalYear === 'all' ? 2568 : (int)$fiscalYear;
            $lastYear = $fiscalYear === 'all' ? 2569 : (int)$fiscalYear;
            $reportStart = ($firstYear-544).'-10-01';
            $reportEnd = ($lastYear-543).'-09-30';
            $dates = ['start'=>$reportStart,'end'=>$reportEnd,
                'exclusive_end'=>($lastYear-543).'-10-01','dispense_end'=>($lastYear-543).'-10-02'];
            $sql = file_get_contents(__DIR__.'/sql/fiscal-service-times.sql');
            if ($sql === false) apiJson(['status'=>'error','message'=>'ไม่พบไฟล์ SQL รายงานปีงบประมาณ'],500);
            $params = [];
            $sql = preg_replace_callback('/\{\{(start|end|exclusive_end|dispense_end)\}\}/',static function($match) use ($dates,&$params) {
                $params[]=$dates[$match[1]]; return '?';
            },$sql);
            $stmt=$conn->prepare($sql);
            $stmt->execute($params);
            $rows=$stmt->fetchAll(PDO::FETCH_ASSOC);
            apiJson(['status'=>'success','data'=>['fiscal_year'=>$fiscalYear,'start_date'=>$reportStart,'end_date'=>$reportEnd,'rows'=>$rows]]);

        
        // =================================================================
        // Action หลักสำหรับหน้า opd_dashboard.html (ดึงครบทั้ง 10 ส่วน)
        // =================================================================
        case 'opd_management':
            
            // 1. KPI Summary (ผู้ป่วยทั้งหมด, ผู้ป่วยใหม่, ผู้ป่วยเก่า)
            $sqlKpi = "SELECT 
                        COUNT(DISTINCT o.vn) AS total_opd,
                        COUNT(DISTINCT o.hn) AS unique_patients,
                        SUM(CASE WHEN p.firstday = o.vstdate THEN 1 ELSE 0 END) AS new_patient,
                        SUM(CASE WHEN p.firstday < o.vstdate THEN 1 ELSE 0 END) AS old_patient,
                        SUM(CASE WHEN p.firstday IS NULL OR p.firstday > o.vstdate THEN 1 ELSE 0 END) AS unknown_patient
                       FROM ovst o
                       LEFT JOIN patient p ON p.hn = o.hn
                       WHERE o.vstdate BETWEEN :s3 AND :e3";
            $stmtKpi = $conn->prepare($sqlKpi);
            $stmtKpi->execute([':s3' => $start_date, ':e3' => $end_date]);
            $kpi = $stmtKpi->fetch(PDO::FETCH_ASSOC);

            // Read each available service-time pair independently: HOSxP schemas
            // differ, so one absent timestamp must not blank every duration KPI.
            $serviceTime = [];
            $paymentWait = null;
            $clinicEfficiency = [];
            try {
                $serviceColumns=opdTableColumns($conn,'service_time');
                $ovstColumns=opdTableColumns($conn,'ovst');
                $hasServiceTable=(bool)$serviceColumns;
                $serviceJoin=$hasServiceTable?' LEFT JOIN service_time s ON s.vn=o.vn':'';
                $durationExpr=static function(string $from,string $to,int $max) use($serviceColumns,$ovstColumns,$hasServiceTable): string {
                    $validFrom=$from==='o.vsttime'?in_array('vsttime',$ovstColumns,true):($hasServiceTable&&in_array(substr($from,2),$serviceColumns,true));
                    $validTo=$to==='o.vsttime'?in_array('vsttime',$ovstColumns,true):($hasServiceTable&&in_array(substr($to,2),$serviceColumns,true));
                    if(!$validFrom||!$validTo)return 'NULL';
                    $diff="TIMESTAMPDIFF(MINUTE,$from,$to)";
                    return "ROUND(AVG(CASE WHEN $diff BETWEEN 1 AND $max THEN $diff END),0)";
                };
                $sampleExpr=static function(string $from,string $to,int $max) use($serviceColumns,$ovstColumns,$hasServiceTable): string {
                    $validFrom=$from==='o.vsttime'?in_array('vsttime',$ovstColumns,true):($hasServiceTable&&in_array(substr($from,2),$serviceColumns,true));
                    $validTo=$to==='o.vsttime'?in_array('vsttime',$ovstColumns,true):($hasServiceTable&&in_array(substr($to,2),$serviceColumns,true));
                    if(!$validFrom||!$validTo)return '0';
                    $diff="TIMESTAMPDIFF(MINUTE,$from,$to)";
                    return "COUNT(DISTINCT CASE WHEN $diff BETWEEN 1 AND $max THEN o.vn END)";
                };
                $service7Available=$hasServiceTable&&in_array('service7',$serviceColumns,true);
                $sqlTime = "SELECT ".$durationExpr('o.vsttime','s.service1',240)." AS wait_registration,
                                ".$durationExpr('o.vsttime','s.service1',240)." AS wait_triage,
                                ".$sampleExpr('o.vsttime','s.service1',240)." AS samples_registration,
                                ".$durationExpr('s.service1','s.service4',300)." AS wait_doctor,
                                ".$sampleExpr('s.service1','s.service4',300)." AS samples_triage,
                                NULL AS consult_time, 0 AS samples_consult,
                                NULL AS wait_pharmacy, 0 AS samples_pharmacy,
                                ".$durationExpr('o.vsttime','s.service7',480)." AS total_wait_avg,
                                ".($service7Available?"COUNT(DISTINCT CASE WHEN s.service7 IS NOT NULL AND s.service7<>'' THEN o.vn END)":'0')." AS timed_visits
                            FROM ovst o $serviceJoin WHERE o.vstdate BETWEEN :s AND :e";
                $stmtTime = $conn->prepare($sqlTime);
                $stmtTime->execute([':s' => $start_date, ':e' => $end_date]);
                $serviceTime = $stmtTime->fetch(PDO::FETCH_ASSOC);

            } catch (PDOException $e) {
                // หากไม่มีตาราง service_time หรือฟิลด์ไม่ตรง ให้แสดงว่าไม่มีคู่เวลาจริง
                error_log('OPD service-time query failed: ' . $e->getCode());
                $serviceTime = [];
            }
            // Keep clinic-efficiency stats independent from optional service_time
            // fields used by other cards; those fields can differ between HOSxP versions.
            try {
                $doctorSaveStats=opdDoctorSaveStats($conn,$start_date,$end_date);
                $serviceTime['samples_consult']=$doctorSaveStats['samples'];
                $serviceTime['consult_time']=$doctorSaveStats['avg_minutes'];
            } catch (PDOException $e) {
                error_log('OPD consultation detail query failed: '.$e->getCode());
            }

            try {
                $clinicEfficiency=opdClinicWaitStats($conn,$start_date,$end_date);
            } catch (PDOException $e) {
                error_log('OPD clinic wait query failed: '.$e->getCode());
            }
            $recordedStages=opdRecordedStages($conn,$start_date,$end_date);
            $pharmacyQueue=opdPharmacyQueueStats($conn,$start_date,$end_date);
            $serviceTime['wait_pharmacy']=$pharmacyQueue['avg_minutes'];
            $serviceTime['samples_pharmacy']=$pharmacyQueue['samples'];
            $cashierReceipt=opdCashierToReceipt($conn,$start_date,$end_date);
            $closeValues=$cashierReceipt['minutes'];
            $serviceTime['samples_payment']=count($closeValues);
            $paymentWait=$closeValues ? round(array_sum($closeValues)/count($closeValues),1) : null;

            // 2 & 9. จำนวนผู้ป่วยรายชั่วโมง แยก Walk-in กับ Appointment (08:00 - 16:00)
            $hourlyData = [];
            try {
                $sqlHourly = "SELECT 
                                SUBSTRING(o.vsttime, 1, 2) AS hr,
                                COUNT(DISTINCT o.vn) AS total_cnt,
                                COUNT(DISTINCT CASE WHEN a.oapp_id IS NOT NULL THEN o.vn END) AS app_cnt,
                                COUNT(DISTINCT CASE WHEN a.oapp_id IS NULL THEN o.vn END) AS walkin_cnt
                              FROM ovst o
                              LEFT JOIN oapp a ON a.hn = o.hn AND a.nextdate = o.vstdate
                              WHERE o.vstdate BETWEEN :s AND :e
                                AND SUBSTRING(o.vsttime, 1, 2) BETWEEN '08' AND '16'
                              GROUP BY SUBSTRING(o.vsttime, 1, 2)
                              ORDER BY hr ASC";
                $stmtHourly = $conn->prepare($sqlHourly);
                $stmtHourly->execute([':s' => $start_date, ':e' => $end_date]);
                $hourlyData = $stmtHourly->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                $hourlyData = [];
            }

            // 3. จำนวนผู้ป่วยตามแผนก (Top 10)
            $sqlClinic = "SELECT IFNULL(k.department, 'ไม่ระบุแผนก') AS clinic_name, COUNT(DISTINCT o.vn) AS total_visit 
                          FROM ovst o 
                          LEFT JOIN kskdepartment k ON k.depcode = o.main_dep 
                          WHERE o.vstdate BETWEEN :s AND :e 
                          GROUP BY o.main_dep, k.department 
                          ORDER BY total_visit DESC
                          LIMIT 10";
            $stmtClinic = $conn->prepare($sqlClinic);
            $stmtClinic->execute([':s' => $start_date, ':e' => $end_date]);
            $topClinics = $stmtClinic->fetchAll(PDO::FETCH_ASSOC);

            // 4. Patient flow counts only stages with a database event marker.
            $patientFlow = [];
            $vnStatColumns=opdTableColumns($conn,'vn_stat');
            $serviceColumns=opdTableColumns($conn,'service_time');
            $departmentColumns=opdTableColumns($conn,'kskdepartment');
            $flowOvstColumns=opdTableColumns($conn,'ovst');
            $hasService4=in_array('service4',$serviceColumns,true);
            $hasService7=in_array('service7',$serviceColumns,true);
            $hasDepartmentName=in_array('depcode',$departmentColumns,true)&&in_array('department',$departmentColumns,true);
            $hasCurrentDepartment=in_array('cur_dep',$flowOvstColumns,true);
            $stepWaitExpr=$hasService4?"COUNT(DISTINCT CASE WHEN s.service4 IS NOT NULL AND s.service4<>'' THEN o.vn END)":"NULL";
            $stepPharmacyExpr=$hasService7?"COUNT(DISTINCT CASE WHEN s.service7 IS NOT NULL AND s.service7<>'' THEN o.vn END)":"NULL";
            $receiptSource=opdReceiptVisitSource($conn);
            $receiptJoin='';
            if($receiptSource){
                $receiptTable=$receiptSource['table'];$receiptVn=$receiptSource['vn'];
                $validReceipt=$receiptSource['status']?" AND r.`{$receiptSource['status']}`='OK'":'';
                $receiptJoin="LEFT JOIN `$receiptTable` r ON r.`$receiptVn`=o.vn$validReceipt";
                $stepPayExpr="COUNT(DISTINCT CASE WHEN r.`$receiptVn` IS NOT NULL THEN o.vn END)";
            }else{
                $receiptNumberColumn=null;
                foreach(['rcp_no','rcpno','rcpno_list'] as $candidate)if(in_array($candidate,$vnStatColumns,true)){$receiptNumberColumn=$candidate;break;}
                $stepPayExpr=$receiptNumberColumn?"COUNT(DISTINCT CASE WHEN v.`$receiptNumberColumn` IS NOT NULL AND v.`$receiptNumberColumn`<>'' THEN o.vn END)":"NULL";
            }
            $stepTreatmentExpr=$hasDepartmentName?"COUNT(DISTINCT CASE WHEN k.depcode='009' OR k.department LIKE '%หัตถการ%' THEN o.vn END)":"NULL";
            $stepDischargeExpr=($hasDepartmentName&&$hasCurrentDepartment)?"COUNT(DISTINCT CASE WHEN kh.depcode='017' THEN o.vn END)":"NULL";
            $serviceJoin=$serviceColumns?"LEFT JOIN service_time s ON s.vn=o.vn":"";
            $departmentJoin=$hasDepartmentName?"LEFT JOIN kskdepartment k ON k.depcode=o.main_dep":"";
            $currentDepartmentJoin=$hasDepartmentName&&$hasCurrentDepartment?"LEFT JOIN kskdepartment kh ON kh.depcode=o.cur_dep":"";
            $sqlFlow="SELECT COUNT(DISTINCT o.vn) AS step_reg, COUNT(DISTINCT sc.vn) AS step_screen,
                             $stepWaitExpr AS step_wait,
                             COUNT(DISTINCT CASE WHEN o.doctor IS NOT NULL AND o.doctor<>'' THEN o.vn END) AS step_doc,
                             COUNT(DISTINCT CASE WHEN v.pdx IS NOT NULL AND v.pdx<>'' THEN o.vn END) AS step_diag,
                             $stepTreatmentExpr AS step_treatment, $stepPharmacyExpr AS step_pharmacy, $stepPayExpr AS step_pay,
                             $stepDischargeExpr AS step_discharge
                      FROM ovst o LEFT JOIN opdscreen sc ON sc.vn=o.vn LEFT JOIN vn_stat v ON v.vn=o.vn $serviceJoin $departmentJoin $currentDepartmentJoin $receiptJoin
                      WHERE o.vstdate BETWEEN :s AND :e";
            $stmtFlow=$conn->prepare($sqlFlow);$stmtFlow->execute([':s'=>$start_date,':e'=>$end_date]);
            $patientFlow=$stmtFlow->fetch(PDO::FETCH_ASSOC) ?: [];

            // The efficiency table is populated only from the same real timed visits
            // returned by opdDoctorSaveStats; never substitute counts as time data.

            // 7. ข้อมูลการนัดหมาย (Appointment & No-Show)
            $appStat = ['total_appointed'=>0,'came_on_time'=>0,'no_show'=>0,'pending'=>0,'cancelled'=>null];
            $appointmentClinics = [];
            $appointmentDaily = [];
            try {
                // Use the dashboard's local calendar date, not the database server timezone.
                $todayBangkok=(new DateTimeImmutable('now',new DateTimeZone('Asia/Bangkok')))->format('Y-m-d');
                $todaySql=$conn->quote($todayBangkok);
                $oappColumns=opdTableColumns($conn,'oapp');
                $hasOappStatus=in_array('oapp_status_id',$oappColumns,true);
                $isCancelled=$hasOappStatus?'a.oapp_status_id=4':'0=1';
                $isActiveAppointment=$hasOappStatus?'(a.oapp_status_id IS NULL OR a.oapp_status_id<>4)':'1=1';
                $sqlApp = "SELECT 
                            COUNT(DISTINCT a.oapp_id) AS total_appointed,
                            COUNT(DISTINCT CASE WHEN $isCancelled THEN a.oapp_id END) AS cancelled,
                            COUNT(DISTINCT CASE WHEN $isActiveAppointment AND o.vn IS NOT NULL THEN a.oapp_id END) AS came_on_time,
                            COUNT(DISTINCT CASE WHEN $isActiveAppointment AND o.vn IS NULL AND a.nextdate < $todaySql THEN a.oapp_id END) AS no_show,
                            COUNT(DISTINCT CASE WHEN $isActiveAppointment AND o.vn IS NULL AND a.nextdate >= $todaySql THEN a.oapp_id END) AS pending
                           FROM oapp a
                           LEFT JOIN ovst o ON o.hn = a.hn AND o.vstdate = a.nextdate
                           WHERE a.nextdate BETWEEN :s AND :e";
                $stmtApp = $conn->prepare($sqlApp);
                $stmtApp->execute([':s' => $start_date, ':e' => $end_date]);
                $appStat = $stmtApp->fetch(PDO::FETCH_ASSOC);
                $activeAppSubquery=$hasOappStatus?' AND (a.oapp_status_id IS NULL OR a.oapp_status_id<>4)':'';
                $stmtWalkIn=$conn->prepare("SELECT COUNT(DISTINCT o.vn) AS walk_in FROM ovst o WHERE o.vstdate BETWEEN :s AND :e AND NOT EXISTS (SELECT 1 FROM oapp a WHERE a.hn=o.hn AND a.nextdate=o.vstdate $activeAppSubquery)");
                $stmtWalkIn->execute([':s'=>$start_date,':e'=>$end_date]);$walkInRow=$stmtWalkIn->fetch(PDO::FETCH_ASSOC);$appStat['walk_in']=(int)($walkInRow['walk_in']??0);
                $oappColumns=opdTableColumns($conn,'oapp');
                $clinicColumn=null;$clinicJoin='';$clinicNameExpr="'ไม่ระบุคลินิก'";$clinicCodeExpr='NULL';$clinicGroup='GROUP BY clinic_name';
                if(in_array('clinic',$oappColumns,true)){
                    // oapp.clinic references clinic.clinic; it is not a department code.
                    $clinicColumns=opdTableColumns($conn,'clinic');
                    $clinicColumn='clinic';$clinicCodeExpr="NULLIF(TRIM(CAST(a.`clinic` AS CHAR)),'')";
                    if(in_array('clinic',$clinicColumns,true)&&in_array('name',$clinicColumns,true)){
                        $clinicNameExpr="COALESCE(NULLIF(TRIM(c.name),''),$clinicCodeExpr,'ไม่ระบุคลินิก')";
                        $clinicJoin='LEFT JOIN clinic c ON c.clinic=a.clinic';
                        $clinicGroup='GROUP BY a.clinic,c.name';
                    }else{
                        $clinicNameExpr="COALESCE($clinicCodeExpr,'ไม่ระบุคลินิก')";
                        $clinicGroup='GROUP BY a.clinic';
                    }
                }else{
                    foreach(['depcode','main_dep'] as $candidate)if(in_array($candidate,$oappColumns,true)){$clinicColumn=$candidate;break;}
                    if($clinicColumn){
                        $clinicCodeExpr="NULLIF(TRIM(CAST(a.`$clinicColumn` AS CHAR)),'')";
                        $clinicNameExpr="COALESCE(NULLIF(TRIM(k.department),''),$clinicCodeExpr,'ไม่ระบุคลินิก')";
                        $clinicJoin="LEFT JOIN kskdepartment k ON k.depcode=a.`$clinicColumn`";
                        $clinicGroup="GROUP BY a.`$clinicColumn`,k.department";
                    }
                }
                $sqlAppClinic = "SELECT $clinicNameExpr AS clinic_name,
                                        $clinicCodeExpr AS clinic_code,
                                        COUNT(DISTINCT a.oapp_id) AS total_appointed,
                                        COUNT(DISTINCT CASE WHEN $isActiveAppointment AND o.vn IS NOT NULL THEN a.oapp_id END) AS attended,
                                        COUNT(DISTINCT CASE WHEN $isActiveAppointment AND o.vn IS NULL AND a.nextdate < $todaySql THEN a.oapp_id END) AS no_show,
                                        COUNT(DISTINCT CASE WHEN $isActiveAppointment AND o.vn IS NULL AND a.nextdate >= $todaySql THEN a.oapp_id END) AS pending,
                                        COUNT(DISTINCT CASE WHEN $isCancelled THEN a.oapp_id END) AS cancelled,
                                        CASE WHEN COUNT(DISTINCT CASE WHEN $isActiveAppointment AND a.nextdate < $todaySql THEN a.oapp_id END) > 0
                                             THEN ROUND(100 * COUNT(DISTINCT CASE WHEN $isActiveAppointment AND o.vn IS NULL AND a.nextdate < $todaySql THEN a.oapp_id END) /
                                                        COUNT(DISTINCT CASE WHEN $isActiveAppointment AND a.nextdate < $todaySql THEN a.oapp_id END), 1)
                                             ELSE NULL END AS no_show_rate
                                   FROM oapp a
                                   LEFT JOIN ovst o ON o.hn = a.hn AND o.vstdate = a.nextdate
                                   $clinicJoin
                                   WHERE a.nextdate BETWEEN :s AND :e
                                   $clinicGroup
                                   ORDER BY total_appointed DESC";
                $stmtAppClinic = $conn->prepare($sqlAppClinic);
                $stmtAppClinic->execute([':s' => $start_date, ':e' => $end_date]);
                $appointmentClinics = $stmtAppClinic->fetchAll(PDO::FETCH_ASSOC);
                $sqlAppDaily = "SELECT DATE_FORMAT(a.nextdate, '%Y-%m-%d') AS appointment_date,
                                       COUNT(DISTINCT a.oapp_id) AS total_appointed,
                                       COUNT(DISTINCT CASE WHEN $isActiveAppointment AND o.vn IS NOT NULL THEN a.oapp_id END) AS attended,
                                       COUNT(DISTINCT CASE WHEN $isActiveAppointment AND o.vn IS NULL AND a.nextdate < $todaySql THEN a.oapp_id END) AS no_show,
                                       COUNT(DISTINCT CASE WHEN $isActiveAppointment AND o.vn IS NULL AND a.nextdate >= $todaySql THEN a.oapp_id END) AS pending,
                                       COUNT(DISTINCT CASE WHEN $isCancelled THEN a.oapp_id END) AS cancelled
                                FROM oapp a LEFT JOIN ovst o ON o.hn = a.hn AND o.vstdate = a.nextdate
                                WHERE a.nextdate BETWEEN :s AND :e
                                GROUP BY a.nextdate ORDER BY a.nextdate";
                $stmtAppDaily = $conn->prepare($sqlAppDaily);
                $stmtAppDaily->execute([':s' => $start_date, ':e' => $end_date]);
                $appointmentDaily = $stmtAppDaily->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                error_log('OPD appointment detail query failed: ' . $e->getCode());
                $appointmentClinics = [];
                $appointmentDaily = [];
            }

            // Same-length preceding period for KPI comparisons.
            $comparison=['start_date'=>null,'end_date'=>null,'total_opd'=>null,'new_patient'=>null,'old_patient'=>null,'avg_wait'=>null,'avg_consult'=>null,'dispensed'=>null];
            try {
                $rangeStart=DateTimeImmutable::createFromFormat('!Y-m-d',$start_date);
                $rangeEnd=DateTimeImmutable::createFromFormat('!Y-m-d',$end_date);
                if($rangeStart&&$rangeEnd){
                    $span=(int)$rangeEnd->diff($rangeStart)->format('%a')+1;
                    $previousEnd=$rangeStart->modify('-1 day');$previousStart=$previousEnd->modify('-'.($span-1).' days');
                    $comparison['start_date']=$previousStart->format('Y-m-d');$comparison['end_date']=$previousEnd->format('Y-m-d');
                    $stmtPrevious=$conn->prepare("SELECT COUNT(DISTINCT o.vn) AS total_opd,
                        SUM(CASE WHEN p.firstday=o.vstdate THEN 1 ELSE 0 END) AS new_patient,
                        SUM(CASE WHEN p.firstday<o.vstdate THEN 1 ELSE 0 END) AS old_patient
                        FROM ovst o LEFT JOIN patient p ON p.hn=o.hn WHERE o.vstdate BETWEEN :s AND :e");
                    $stmtPrevious->execute([':s'=>$comparison['start_date'],':e'=>$comparison['end_date']]);$previous=$stmtPrevious->fetch(PDO::FETCH_ASSOC)?:[];
                    foreach(['total_opd','new_patient','old_patient'] as $field)$comparison[$field]=(int)($previous[$field]??0);
                    if($serviceColumns&&in_array('service7',$serviceColumns,true)){
                        $stmtPreviousTime=$conn->prepare("SELECT
                            ROUND(AVG(CASE WHEN TIMESTAMPDIFF(MINUTE,o.vsttime,s.service7) BETWEEN 1 AND 480 THEN TIMESTAMPDIFF(MINUTE,o.vsttime,s.service7) END),1) AS avg_wait,
                            COUNT(DISTINCT CASE WHEN s.service7 IS NOT NULL AND s.service7<>'' THEN o.vn END) AS dispensed
                            FROM ovst o INNER JOIN service_time s ON s.vn=o.vn WHERE o.vstdate BETWEEN :s AND :e");
                        $stmtPreviousTime->execute([':s'=>$comparison['start_date'],':e'=>$comparison['end_date']]);$previousTime=$stmtPreviousTime->fetch(PDO::FETCH_ASSOC)?:[];
                        $comparison['avg_wait']=isset($previousTime['avg_wait'])?(float)$previousTime['avg_wait']:null;
                        $comparison['dispensed']=(int)($previousTime['dispensed']??0);
                    }
                    try {
                        $previousDoctorStats=opdDoctorSaveStats($conn,$comparison['start_date'],$comparison['end_date']);
                        $comparison['avg_consult']=$previousDoctorStats['avg_minutes'];
                    } catch (PDOException $e) { error_log('OPD consultation comparison query failed: '.$e->getCode()); }
                }
            } catch (PDOException $e) { error_log('OPD comparison query failed: '.$e->getCode()); }

            // Seven-day department trend ending on the selected report end date.
            $departmentTrend=['start_date'=>null,'end_date'=>$end_date,'dates'=>[],'departments'=>[]];
            try {
                $trendEnd=DateTimeImmutable::createFromFormat('!Y-m-d',$end_date);
                if($trendEnd){
                    $trendStart=$trendEnd->modify('-6 days');$dateIndex=[];
                    for($i=0;$i<7;$i++){$day=$trendStart->modify('+'.$i.' days')->format('Y-m-d');$dateIndex[]=$day;}
                    $departmentTrend['start_date']=$dateIndex[0];$departmentTrend['dates']=$dateIndex;
                    $stmtTrend=$conn->prepare("SELECT DATE_FORMAT(o.vstdate,'%Y-%m-%d') AS visit_date,
                        COALESCE(o.main_dep,'') AS department_code,COALESCE(k.department,'ไม่ระบุแผนก') AS department_name,
                        COUNT(DISTINCT o.vn) AS visits FROM ovst o LEFT JOIN kskdepartment k ON k.depcode=o.main_dep
                        WHERE o.vstdate BETWEEN :s AND :e GROUP BY o.vstdate,o.main_dep,k.department ORDER BY k.department");
                    $stmtTrend->execute([':s'=>$dateIndex[0],':e'=>$dateIndex[6]]);$trendRows=$stmtTrend->fetchAll(PDO::FETCH_ASSOC);$groups=[];
                    foreach($trendRows as $trendRow){$code=(string)$trendRow['department_code'];$key=$code!==''?$code:(string)$trendRow['department_name'];if(!isset($groups[$key]))$groups[$key]=['key'=>$key,'code'=>$code,'name'=>$trendRow['department_name'],'total'=>0,'values'=>array_fill(0,7,0)];$position=array_search($trendRow['visit_date'],$dateIndex,true);if($position!==false){$visits=(int)$trendRow['visits'];$groups[$key]['values'][$position]+=$visits;$groups[$key]['total']+=$visits;}}
                    $departmentTrend['departments']=array_values($groups);
                }
            } catch (PDOException $e) { error_log('OPD department trend query failed: '.$e->getCode()); }

            // จัดเตรียมโครงสร้างข้อมูลส่งกลับ
            $data = [
                "kpi" => [
                    "total_opd"    => (int)($kpi['total_opd'] ?? 0),
                    "unique_patients" => (int)($kpi['unique_patients'] ?? 0),
                    "new_patient"  => (int)($kpi['new_patient'] ?? 0),
                    "old_patient"  => (int)($kpi['old_patient'] ?? 0),
                    "unknown_patient" => (int)($kpi['unknown_patient'] ?? 0),
                    "avg_wait"     => isset($serviceTime['total_wait_avg']) ? (int)$serviceTime['total_wait_avg'] : null,
                    "avg_consult"  => isset($serviceTime['consult_time']) ? (int)$serviceTime['consult_time'] : null
                ],
                "cashier_receipt" => array_diff_key($cashierReceipt,['minutes'=>true]),
                "sla_stages" => $recordedStages,
                "sla_wait" => [
                    "registration" => isset($serviceTime['wait_registration']) ? (int)$serviceTime['wait_registration'] : null,
                    "triage"   => isset($serviceTime['wait_doctor']) ? (int)$serviceTime['wait_doctor'] : null,
                    "doctor"   => isset($serviceTime['wait_doctor']) ? (int)$serviceTime['wait_doctor'] : null,
                    "consult"  => isset($serviceTime['consult_time']) ? (int)$serviceTime['consult_time'] : null,
                    "pharmacy" => isset($serviceTime['wait_pharmacy']) ? (float)$serviceTime['wait_pharmacy'] : null,
                    "payment" => $paymentWait
                ],
                "quality" => ["visits_without_time" => max(0,(int)($kpi['total_opd']??0)-(int)($serviceTime['timed_visits']??0)), "time_samples" => [
                    "registration" => (int)($serviceTime['samples_registration'] ?? 0),
                    "triage" => (int)($serviceTime['samples_triage'] ?? 0),
                    "consult" => (int)($serviceTime['samples_consult'] ?? 0),
                    "pharmacy" => (int)($serviceTime['samples_pharmacy'] ?? 0),
                    "payment" => (int)($serviceTime['samples_payment'] ?? 0)
                ]],
                "hourly"      => $hourlyData,
                "clinics"     => $topClinics,
                "flow"        => $patientFlow,
                "efficiency"  => $clinicEfficiency,
                "comparison"  => $comparison,
                "department_trend" => $departmentTrend,
                "appointment" => [
                    "total_appointed" => (int)($appStat['total_appointed'] ?? 0),
                    "came_on_time" => (int)($appStat['came_on_time'] ?? 0),
                    "no_show"      => (int)($appStat['no_show'] ?? 0),
                    "pending"      => (int)($appStat['pending'] ?? 0),
                    "cancelled"    => isset($appStat['cancelled']) ? (int)$appStat['cancelled'] : null,
                    "clinics"      => $appointmentClinics,
                    "daily"        => $appointmentDaily,
                    "walk_in"      => (int)($appStat['walk_in'] ?? 0)
                ]
            ];
            break;

        // =================================================================
        // Historical service quality report used by section 11.
        case 'analytics':
            $todayBangkok=(new DateTimeImmutable('now',new DateTimeZone('Asia/Bangkok')))->format('Y-m-d');
            $todaySql=$conn->quote($todayBangkok);
            $stmt = $conn->prepare("SELECT COUNT(DISTINCT o.vn) AS total_opd,
                                           SUM(CASE WHEN p.firstday IS NULL THEN 1 ELSE 0 END) AS unknown_patient
                                    FROM ovst o LEFT JOIN patient p ON p.hn=o.hn
                                    WHERE o.vstdate BETWEEN :s AND :e");
            $stmt->execute([':s'=>$start_date, ':e'=>$end_date]);
            $analyticsKpi = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
            $hourly = [];
            $stmt = $conn->prepare("SELECT SUBSTRING(o.vsttime,1,2) AS hr, COUNT(DISTINCT o.vn) AS total_cnt
                                    FROM ovst o WHERE o.vstdate BETWEEN :s AND :e
                                      AND SUBSTRING(o.vsttime,1,2) BETWEEN '00' AND '23'
                                    GROUP BY SUBSTRING(o.vsttime,1,2) ORDER BY hr");
            $stmt->execute([':s'=>$start_date, ':e'=>$end_date]);
            $hourly = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($hourly as &$hourRow) { $hourRow['hr']=(int)$hourRow['hr']; $hourRow['total_cnt']=(int)$hourRow['total_cnt']; }
            unset($hourRow);

            $durations = ['registration'=>[], 'triage'=>[], 'consult'=>[], 'payment'=>[]];
            try {
                $stmt = $conn->prepare("SELECT o.vstdate, o.vsttime, s.service1, s.service4, s.service7
                                        FROM ovst o INNER JOIN service_time s ON s.vn=o.vn
                                        WHERE o.vstdate BETWEEN :s AND :e");
                $stmt->execute([':s'=>$start_date, ':e'=>$end_date]);
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $pairs = [
                        'registration'=>[$row['vsttime'], $row['service1'], 480],
                        'triage'=>[$row['service1'], $row['service4'], 480],
                        'consult'=>[$row['service4'], $row['service7'], 480]
                    ];
                    foreach ($pairs as $key=>$pair) {
                        $minutes = opdElapsedServiceMinutes($pair[0], $pair[1], (string)$row['vstdate'], $pair[2]);
                        if ($minutes !== null) $durations[$key][] = $minutes;
                    }
                }
            } catch (PDOException $e) {
                error_log('OPD analytics service-time query failed: '.$e->getCode());
            }
            $cashierReceipt=opdCashierToReceipt($conn,$start_date,$end_date);
            $durations['payment']=$cashierReceipt['minutes'];
            $stages = [];
            foreach ($durations as $key=>$values) {
                sort($values, SORT_NUMERIC); $n=count($values); $sum=array_sum($values);
                $at = static function(array $items, int $rank) { return $items ? $items[max(0,min(count($items)-1,$rank))] : null; };
                $median = $n ? (($n%2) ? $at($values,(int)floor($n/2)) : ($at($values,(int)($n/2)-1)+$at($values,(int)($n/2)))/2) : null;
                $stages[$key] = [
                    'samples'=>$n, 'avg_minutes'=>$n ? round($sum/$n,1) : null, 'median_minutes'=>$median,
                    'p90_minutes'=>$n ? $at($values,(int)ceil($n*0.9)-1) : null, 'max_minutes'=>$n ? max($values) : null,
                    'over_30'=>count(array_filter($values,static function($v){return $v>30;})),
                    'over_60'=>count(array_filter($values,static function($v){return $v>60;})),
                    'over_120'=>count(array_filter($values,static function($v){return $v>120;}))
                ];
            }
            $appointments = [];
            $stmt = $conn->prepare("SELECT DATE_FORMAT(a.nextdate,'%Y-%m-%d') AS appointment_date,
                                           COUNT(DISTINCT a.oapp_id) AS total_appointed,
                                           COUNT(DISTINCT CASE WHEN o.vn IS NOT NULL THEN a.oapp_id END) AS attended,
                                           COUNT(DISTINCT CASE WHEN o.vn IS NULL AND a.nextdate < $todaySql THEN a.oapp_id END) AS no_show
                                    FROM oapp a LEFT JOIN ovst o ON o.hn=a.hn AND o.vstdate=a.nextdate
                                    WHERE a.nextdate BETWEEN :s AND :e GROUP BY a.nextdate ORDER BY a.nextdate");
            $stmt->execute([':s'=>$start_date, ':e'=>$end_date]); $appointments=$stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($appointments as &$appointmentRow) { foreach (['total_appointed','attended','no_show'] as $field) $appointmentRow[$field]=(int)$appointmentRow[$field]; }
            unset($appointmentRow);
            apiJson(['status'=>'success','data'=>[
                'start_date'=>$start_date,'end_date'=>$end_date,'today'=>(new DateTimeImmutable('now',new DateTimeZone('Asia/Bangkok')))->format('Y-m-d'),
                'updated_at'=>(new DateTimeImmutable('now',new DateTimeZone('Asia/Bangkok')))->format('Y-m-d H:i:s'),
                'kpi'=>['total_opd'=>(int)($analyticsKpi['total_opd']??0),'unknown_patient'=>(int)($analyticsKpi['unknown_patient']??0)],
                'cashier_receipt'=>array_diff_key($cashierReceipt,['minutes'=>true]),'stages'=>$stages,'hourly'=>$hourly,'appointments'=>$appointments,'sla_defaults'=>[]
            ]]);

        // Long-range trend report for section 11.
        case 'trend_report':
            $endObj = DateTimeImmutable::createFromFormat('!Y-m-d', $end_date);
            if (!$endObj) apiJson(['status'=>'error','message'=>'รูปแบบวันที่ไม่ถูกต้อง'],400);
            $period = isset($_GET['period']) ? (string)$_GET['period'] : '30days';
            $periodDays = ['7days'=>7,'30days'=>30,'12months'=>365,'5years'=>1826];
            $days = isset($periodDays[$period]) ? $periodDays[$period] : 30;
            $trendStart = $endObj->modify('-'.($days-1).' days')->format('Y-m-d');
            $grain = isset($_GET['grain']) ? (string)$_GET['grain'] : 'day';
            if (!in_array($grain,['day','week','month','year'],true)) $grain='day';
            $department = isset($_GET['department']) ? trim((string)$_GET['department']) : '';
            $service = isset($_GET['service']) ? (string)$_GET['service'] : 'all';
            if (!in_array($service,['all','appointment','walkin'],true)) $service='all';
            $where = 'o.vstdate BETWEEN :s AND :e'; $params=[':s'=>$trendStart, ':e'=>$end_date];
            if ($department !== '') { $where .= ' AND o.main_dep=:dep'; $params[':dep']=$department; }
            if ($service === 'appointment') $where .= ' AND EXISTS (SELECT 1 FROM oapp a WHERE a.hn=o.hn AND a.nextdate=o.vstdate)';
            if ($service === 'walkin') $where .= ' AND NOT EXISTS (SELECT 1 FROM oapp a WHERE a.hn=o.hn AND a.nextdate=o.vstdate)';
            $stmt=$conn->prepare("SELECT DATE_FORMAT(o.vstdate,'%Y-%m-%d') AS day,
                                         COUNT(DISTINCT o.vn) AS visits,
                                         SUM(CASE WHEN p.firstday=o.vstdate THEN 1 ELSE 0 END) AS new_visits,
                                         SUM(CASE WHEN p.firstday<o.vstdate THEN 1 ELSE 0 END) AS old_visits,
                                         SUM(CASE WHEN p.firstday IS NULL OR p.firstday>o.vstdate THEN 1 ELSE 0 END) AS unknown_visits
                                  FROM ovst o LEFT JOIN patient p ON p.hn=o.hn WHERE $where
                                  GROUP BY o.vstdate ORDER BY o.vstdate");
            $stmt->execute($params); $dailyRows=$stmt->fetchAll(PDO::FETCH_ASSOC); $buckets=[];
            foreach ($dailyRows as $row) {
                $date=DateTimeImmutable::createFromFormat('!Y-m-d',$row['day']);
                if ($grain==='year') $key=$date->format('Y');
                elseif ($grain==='month') $key=$date->format('Y-m');
                elseif ($grain==='week') $key=$date->modify('monday this week')->format('Y-m-d');
                else $key=$date->format('Y-m-d');
                if (!isset($buckets[$key])) $buckets[$key]=['start_date'=>$key,'end_date'=>$key,'visits'=>0,'new_visits'=>0,'old_visits'=>0,'unknown_visits'=>0];
                foreach (['visits','new_visits','old_visits','unknown_visits'] as $field) $buckets[$key][$field]+=(int)$row[$field];
                $buckets[$key]['end_date']=$date->format('Y-m-d');
            }
            $rows=[]; foreach($buckets as $key=>$row){$row['label']=$grain==='week'?$key.' ถึง '.$row['end_date']:$key;$rows[]=$row;}
            $stmt=$conn->prepare("SELECT DISTINCT k.depcode AS code,k.department AS name FROM ovst o LEFT JOIN kskdepartment k ON k.depcode=o.main_dep WHERE o.vstdate BETWEEN :s AND :e AND k.depcode IS NOT NULL ORDER BY k.department");
            $stmt->execute([':s'=>$trendStart, ':e'=>$end_date]); $departments=$stmt->fetchAll(PDO::FETCH_ASSOC);
            apiJson(['status'=>'success','data'=>['start_date'=>$trendStart,'end_date'=>$end_date,'updated_at'=>(new DateTimeImmutable('now',new DateTimeZone('Asia/Bangkok')))->format('Y-m-d H:i:s'),'department'=>$department,'service'=>$service,'grain'=>$grain,'departments'=>$departments,'rows'=>$rows]]);

        // Action เดิม (เผื่อหน้า index.html แรกสุดยังเรียกใช้งานอยู่)
        // =================================================================
        case 'overview':
            $stmtOPD = $conn->query("SELECT COUNT(vn) AS total_opd FROM vn_stat WHERE vstdate = CURRENT_DATE");
            $stmtIPD = $conn->query("SELECT COUNT(an) AS total_ipd FROM ipt WHERE dchdate IS NULL OR dchdate = ''");
            $data = [
                "total_opd"        => $stmtOPD->fetch(PDO::FETCH_ASSOC)['total_opd'],
                "total_active_ipd" => $stmtIPD->fetch(PDO::FETCH_ASSOC)['total_ipd']
            ];
            break;

        case 'opd_clinic':
            $sql = "SELECT IFNULL(k.department, 'ไม่ระบุแผนก') AS clinic_name, COUNT(o.vn) AS total_visit 
                    FROM ovst o 
                    LEFT JOIN kskdepartment k ON k.depcode = o.main_dep 
                    WHERE o.vstdate = CURRENT_DATE 
                    GROUP BY o.main_dep, k.department 
                    ORDER BY total_visit DESC";
            $stmt = $conn->query($sql);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'ipd_ward':
            $sql = "SELECT IFNULL(w.name, 'ไม่ระบุวอร์ด') AS ward_name, COUNT(i.an) AS total_admit 
                    FROM ipt i 
                    LEFT JOIN ward w ON w.ward = i.ward 
                    WHERE i.dchdate IS NULL OR i.dchdate = '' 
                    GROUP BY i.ward, w.name 
                    ORDER BY total_admit DESC";
            $stmt = $conn->query($sql);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        default:
            echo json_encode(["status" => "error", "message" => "Invalid action: " . $action]);
            exit();
    }
} catch (PDOException $e) {
    error_log('OPD report query failed: ' . $e->getCode());
    apiJson(["status" => "error", "message" => "คำสั่ง SQL ทำงานไม่สำเร็จ ตรวจโครงสร้างตารางและ PHP error log"], 500);
}

// ส่งออกข้อมูลเป็น JSON
$json_output = json_encode(["status" => "success", "data" => $data], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);

if ($json_output === false) {
    echo json_encode(["status" => "error", "message" => "JSON Encode Error: " . json_last_error_msg()]);
} else {
    echo $json_output;
}
?>
