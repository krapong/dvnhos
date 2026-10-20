<?php
/**
 * api/patients.php — HOSxP ER Monitor API
 */

// ── 1. จับ error ทุกชนิด ให้ออกมาเป็น JSON ──────────────────────
ob_start();

set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => "PHP Error [$errno]: $errstr",
        'file' => basename($errfile) . ':' . $errline
    ], JSON_UNESCAPED_UNICODE);
    exit;
});

set_exception_handler(function ($e) {
    ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => basename($e->getFile()) . ':' . $e->getLine()
    ], JSON_UNESCAPED_UNICODE);
    exit;
});

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// ── 2. หา config path ───────────────────────────────────────────
$configPath = dirname(__DIR__) . '/config/database.php';
if (!file_exists($configPath)) {
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'error' => 'ไม่พบ config/database.php',
        'path' => $configPath
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
require_once $configPath;

// ── 3. เชื่อมต่อ DB ─────────────────────────────────────────────
try {
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8',
        DB_HOST,
        DB_PORT,
        DB_NAME
    );
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",
        PDO::ATTR_TIMEOUT => 5,
    ]);
} catch (PDOException $e) {
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'error' => 'DB Error: ' . $e->getMessage(),
        'hint' => 'ตรวจสอบ DB_HOST/DB_USER/DB_PASS/DB_NAME ใน config/database.php'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ── 4. Security ──────────────────────────────────────────────────
if (defined('API_TOKEN') && API_TOKEN !== '') {
    $tok = $_GET['token'] ?? $_SERVER['HTTP_X_API_TOKEN'] ?? '';
    if ($tok !== API_TOKEN) {
        http_response_code(401);
        ob_end_clean();
        echo json_encode(['success' => false, 'error' => 'Unauthorized'], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// ── 5. Parameters ────────────────────────────────────────────────
$date = $_GET['date'] ?? date('Y-m-d');
$summary = isset($_GET['summary']);
$debug = isset($_GET['debug']);

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    ob_end_clean();
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid date, use YYYY-MM-DD'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ── 6. Debug mode ────────────────────────────────────────────────
if ($debug) {
    ob_end_clean();
    echo json_encode([
        'success' => true,
        'debug' => true,
        'php_version' => PHP_VERSION,
        'db_host' => DB_HOST,
        'db_name' => DB_NAME,
        'db_user' => DB_USER,
        'date' => $date,
        'config_path' => $configPath,
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// ── 7. SQL Query ─────────────────────────────────────────────────
$sql = "SELECT 
    o.hn AS hn,
    MAX(o.oqueue) AS qn,
    MAX(o.vstdate) AS date,
    MAX(TIME(er.enter_er_time)) AS time,
    CONCAT(MAX(p.pname), MAX(p.fname)) AS p_name,
    MAX(p.lname) AS l_name,
    MAX(el.er_emergency_level_name) AS level,
    MAX(eet.export_code) AS color,
    MAX(ed.name) AS dch_list,
    GROUP_CONCAT(DISTINCT lh.lab_order_number) AS lab,
    MAX(v.pdx) AS dx,
    GROUP_CONCAT(DISTINCT xh.xray_order_number) AS xr,
    MAX(er.observe) AS observe,
    MAX(er.er_dch_type) AS er_dch_type,
    MAX(oot.name) AS ovst_status,
    MAX(erl.er_leave_status_name) AS er_leave_status_name,
    TIMESTAMPDIFF(MINUTE, MAX(er.enter_er_time), NOW()) AS elapsed_min
FROM er_regist er
INNER JOIN ovst o ON o.vn = er.vn
INNER JOIN patient p ON p.hn = o.hn
INNER JOIN vn_stat v ON v.vn = o.vn
LEFT JOIN er_leave_status erl ON erl.er_leave_status_id = er.er_leave_status_id
LEFT JOIN er_emergency_type eet ON eet.er_emergency_type = er.er_emergency_type
LEFT JOIN er_emergency_level el ON el.er_emergency_level_id = er.er_emergency_level_id
LEFT JOIN er_dch_type ed ON ed.er_dch_type = er.er_dch_type
LEFT JOIN lab_head lh ON lh.vn = o.vn
LEFT JOIN xray_head xh ON xh.vn = o.vn
LEFT JOIN ovstost oot ON oot.ovstost = o.ovstost
WHERE o.vstdate = :date
  AND (er.er_leave_status_id IS NULL OR er.er_leave_status_id = '')
GROUP BY o.hn
ORDER BY color ASC, time ASC;";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':date' => $date]);
    $rows = $stmt->fetchAll();
} catch (PDOException $e) {
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'error' => 'SQL Error: ' . $e->getMessage(),
        'hint' => 'ตรวจสอบว่า database รองรับ GROUP BY หรือตารางมีอยู่จริง',
        'sql_debug' => $debug ? $sql : null
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// ── 8. ประมวลผล ──────────────────────────────────────────────────
$losLimit = defined('LOS_LIMIT_MIN') ? (int) LOS_LIMIT_MIN : 120;
$esiMap = [
    '1' => ['esi1', 'Level 1 Resuscitation'],
    'R' => ['esi1', 'Level 1 Resuscitation'],
    '2' => ['esi2', 'Level 2 Emergent'],
    'E' => ['esi2', 'Level 2 Emergent'],
    '3' => ['esi3', 'Level 3 Urgent'],
    'U' => ['esi3', 'Level 3 Urgent'],
    '4' => ['esi4', 'Level 4 Less Urgent'],
    'S' => ['esi4', 'Level 4 Less Urgent'],
    '5' => ['esi5', 'Level 5 Non-Urgent'],
    'N' => ['esi5', 'Level 5 Non-Urgent'],
];

foreach ($rows as &$row) {
    $e = (int) ($row['elapsed_min'] ?? 0);
    $r = $losLimit - $e;
    $row['elapsed_min'] = $e;
    $row['remaining_min'] = $r;
    $row['los_status'] = $r <= 0 ? 'over' : ($r <= 20 ? 'critical' : ($r <= 45 ? 'warning' : 'ok'));
    $c = strtoupper(trim($row['color'] ?? ''));
    [$row['esi_class'], $row['esi_label']] = $esiMap[$c] ?? ['esi3', 'Level 3 Urgent'];
}
unset($row);

// ── 9. Summary mode ──────────────────────────────────────────────
if ($summary) {
    $byLevel = [];
    $alerts = 0;
    foreach ($rows as $r) {
        $byLevel[$r['color'] ?? '?'] = ($byLevel[$r['color'] ?? '?'] ?? 0) + 1;
        if ($r['remaining_min'] < 20)
            $alerts++;
    }
    ob_end_clean();
    echo json_encode([
        'success' => true,
        'date' => $date,
        'total' => count($rows),
        'alerts' => $alerts,
        'by_level' => $byLevel,
        'updated_at' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ── 10. Output ───────────────────────────────────────────────────
ob_end_clean();
echo json_encode([
    'success' => true,
    'date' => $date,
    'total' => count($rows),
    'updated_at' => date('Y-m-d H:i:s'),
    'data' => $rows,
], JSON_UNESCAPED_UNICODE);
