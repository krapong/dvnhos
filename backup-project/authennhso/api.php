<?php
/**
 * Backend API Engine
 * NHSO Claim & Authen Code Detection System
 * Connected to HOSxP Database (Read-Only)
 * 
 * Key HOSxP Tables:
 *   - ovst              : Outpatient visits (vn, hn, vstdate, vsttime, pttype, cur_dep, doctor, spclty)
 *   - patient           : Patient master (hn, cid, pname, fname, lname, birthday, sex, hometel)
 *   - pttype            : Patient type / rights (pttype, name, nhso_code)
 *   - kskdepartment     : Department master (depcode, department)
 *   - nhso_confirm_privilege : NHSO confirm/authen records (vn, nhso_authen_code, nhso_status, nhso_total_amount)
 *   - opitemrece        : OPD charge items (vn, sum_price) -> SUM for total income
 *   - doctor            : Doctor master (code, name, licenseno)
 *   - ovstdiag          : OPD diagnosis (vn, icd10, diagtype)
 *   - spclty            : Specialty (spclty, name)
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/config.php';

// Secure API endpoint: restrict access to authenticated users only
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';

try {
    $pdo = getDbConnection();
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

/**
 * Build WHERE clause from filters
 * Filters NHSO-eligible pttypes only (pttype.nhso_code IS NOT NULL and in UCS/OFC groups)
 */
function buildFilters($params) {
    $where = [];
    $binds = [];
    
    // Date Range
    if (!empty($params['vstdate_start'])) {
        $where[] = "o.vstdate >= :vstdate_start";
        $binds[':vstdate_start'] = $params['vstdate_start'];
    }
    if (!empty($params['vstdate_end'])) {
        $where[] = "o.vstdate <= :vstdate_end";
        $binds[':vstdate_end'] = $params['vstdate_end'];
    }
    
    // Right Type (pttype filter)
    if (!empty($params['pttype']) && $params['pttype'] !== 'all') {
        if ($params['pttype'] === 'UCS') {
            // nhso_code = '01' (UC/UCS/บัตรทอง)
            $where[] = "pt.nhso_code = '01'";
        } else if ($params['pttype'] === 'OFC') {
            // nhso_code = '02' (ข้าราชการ/รัฐวิสาหกิจ)
            $where[] = "pt.nhso_code = '02'";
        } else if ($params['pttype'] === 'SSS') {
            // nhso_code = '03' (ประกันสังคม)
            $where[] = "pt.nhso_code = '03'";
        } else if ($params['pttype'] === 'LGO') {
            // nhso_code = '04' (อปท)
            $where[] = "pt.nhso_code = '04'";
        } else {
            $where[] = "pt.nhso_code = :nhso_code_filter";
            $binds[':nhso_code_filter'] = $params['pttype'];
        }
    }
    
    // Department
    if (!empty($params['department']) && $params['department'] !== 'all') {
        $where[] = "o.cur_dep = :department";
        $binds[':department'] = $params['department'];
    }
    
    // Search query (HN, Name, CID)
    if (!empty($params['search'])) {
        $searchTerm = '%' . $params['search'] . '%';
        $where[] = "(o.hn LIKE :search1 OR p.fname LIKE :search2 OR p.lname LIKE :search3 OR p.cid LIKE :search4)";
        $binds[':search1'] = $searchTerm;
        $binds[':search2'] = $searchTerm;
        $binds[':search3'] = $searchTerm;
        $binds[':search4'] = $searchTerm;
    }
    
    // Issue Type Filter
    if (!empty($params['issue_type'])) {
        switch ($params['issue_type']) {
            case 'missing_authen':
                $where[] = "(vp.auth_code IS NULL OR vp.auth_code = '') AND pt.name NOT LIKE '%ออกหน่วย%' AND pt.name NOT LIKE '%ชำระเงิน%'";
                break;
            case 'not_closed_visit':
                $where[] = "r.finance_number IS NULL AND pt.name NOT LIKE '%ออกหน่วย%' AND pt.name NOT LIKE '%ชำระเงิน%'";
                break;
            case 'not_closed_nhso':
                $where[] = "vp.auth_code LIKE 'EP%' AND pt.name NOT LIKE '%ออกหน่วย%' AND pt.name NOT LIKE '%ชำระเงิน%'";
                break;
            case 'closed_nhso':
                $where[] = "vp.auth_code LIKE 'PP%' AND pt.name NOT LIKE '%ออกหน่วย%' AND pt.name NOT LIKE '%ชำระเงิน%'";
                break;
            case 'all_issues':
                $where[] = "(
                    (vp.auth_code IS NULL OR vp.auth_code = '')
                    OR (r.finance_number IS NULL)
                    OR (vp.auth_code LIKE 'EP%')
                ) AND pt.name NOT LIKE '%ออกหน่วย%' AND pt.name NOT LIKE '%ชำระเงิน%'";
                break;
        }
    }
    
    return [
        'sql' => count($where) > 0 ? "WHERE " . implode(" AND ", $where) : "",
        'binds' => $binds
    ];
}

switch ($action) {

    case 'get_stats':
        try {
            $statParams = $_GET;
            unset($statParams['issue_type']);
            $filters = buildFilters($statParams);
            
            $sql = "SELECT 
                COUNT(o.vn) as total_visits,
                COUNT(DISTINCT o.hn) as total_patients,
                SUM(CASE WHEN (vp.auth_code IS NULL OR vp.auth_code = '') AND pt.name NOT LIKE '%ออกหน่วย%' AND pt.name NOT LIKE '%ชำระเงิน%' THEN 1 ELSE 0 END) as no_authen,
                SUM(CASE WHEN vp.auth_code LIKE 'PP%' AND pt.name NOT LIKE '%ออกหน่วย%' AND pt.name NOT LIKE '%ชำระเงิน%' THEN 1 ELSE 0 END) as authen_pp,
                SUM(CASE WHEN vp.auth_code LIKE 'EP%' AND pt.name NOT LIKE '%ออกหน่วย%' AND pt.name NOT LIKE '%ชำระเงิน%' THEN 1 ELSE 0 END) as authen_ep,
                SUM(CASE WHEN r.finance_number IS NULL AND pt.name NOT LIKE '%ออกหน่วย%' AND pt.name NOT LIKE '%ชำระเงิน%' THEN 1 ELSE 0 END) as not_closed_hosxp,
                SUM(CASE WHEN vp.auth_code LIKE 'EP%' AND pt.name NOT LIKE '%ออกหน่วย%' AND pt.name NOT LIKE '%ชำระเงิน%' THEN 1 ELSE 0 END) as not_closed_nhso
            FROM ovst o
            JOIN patient p ON o.hn = p.hn
            LEFT JOIN pttype pt ON o.pttype = pt.pttype
            LEFT JOIN visit_pttype vp ON o.vn = vp.vn
            LEFT JOIN rcpt_print r ON o.vn = r.vn AND (r.status IS NULL OR r.status != 'ABORT')
            {$filters['sql']}";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($filters['binds']);
            $stats = $stmt->fetch();
            
            // Calculate loss prevention value (sum income for missing visits)
            $lossSql = "SELECT 
                IFNULL(SUM(sub.total_income), 0) as loss_value
            FROM (
                SELECT o.vn, IFNULL((SELECT SUM(oi.sum_price) FROM opitemrece oi WHERE oi.vn = o.vn), 0) as total_income
                FROM ovst o
                JOIN patient p ON o.hn = p.hn
                LEFT JOIN pttype pt ON o.pttype = pt.pttype
                LEFT JOIN visit_pttype vp ON o.vn = vp.vn
                LEFT JOIN rcpt_print r ON o.vn = r.vn AND (r.status IS NULL OR r.status != 'ABORT')
                {$filters['sql']}
                AND (
                    (vp.auth_code IS NULL OR vp.auth_code = '')
                    OR (r.finance_number IS NULL)
                    OR (vp.auth_code LIKE 'EP%')
                ) AND pt.name NOT LIKE '%ออกหน่วย%' AND pt.name NOT LIKE '%ชำระเงิน%'
            ) sub";
            
            $lossStmt = $pdo->prepare($lossSql);
            $lossStmt->execute($filters['binds']);
            $lossResult = $lossStmt->fetch();
            
            // Department breakdown
            $deptSql = "SELECT 
                IFNULL(d.department, CONCAT('แผนก ', o.cur_dep)) as department,
                COUNT(o.vn) as total,
                SUM(CASE WHEN (vp.auth_code IS NULL OR vp.auth_code = '') AND pt.name NOT LIKE '%ออกหน่วย%' AND pt.name NOT LIKE '%ชำระเงิน%' THEN 1 
                         WHEN r.finance_number IS NULL AND pt.name NOT LIKE '%ออกหน่วย%' AND pt.name NOT LIKE '%ชำระเงิน%' THEN 1
                         WHEN vp.auth_code LIKE 'EP%' AND pt.name NOT LIKE '%ออกหน่วย%' AND pt.name NOT LIKE '%ชำระเงิน%' THEN 1
                         ELSE 0 END) as issues
            FROM ovst o
            JOIN patient p ON o.hn = p.hn
            LEFT JOIN pttype pt ON o.pttype = pt.pttype
            LEFT JOIN visit_pttype vp ON o.vn = vp.vn
            LEFT JOIN rcpt_print r ON o.vn = r.vn AND (r.status IS NULL OR r.status != 'ABORT')
            LEFT JOIN kskdepartment d ON o.cur_dep = d.depcode
            {$filters['sql']}
            GROUP BY o.cur_dep, d.department
            ORDER BY issues DESC
            LIMIT 8";
            
            $deptStmt = $pdo->prepare($deptSql);
            $deptStmt->execute($filters['binds']);
            $statsDept = $deptStmt->fetchAll();
            
            // PtType breakdown
            $pttypeSql = "SELECT 
                pt.name as pttype_name,
                pt.nhso_code,
                COUNT(o.vn) as total,
                SUM(CASE WHEN (vp.auth_code IS NULL OR vp.auth_code = '') AND pt.name NOT LIKE '%ออกหน่วย%' AND pt.name NOT LIKE '%ชำระเงิน%' THEN 1 
                         WHEN r.finance_number IS NULL AND pt.name NOT LIKE '%ออกหน่วย%' AND pt.name NOT LIKE '%ชำระเงิน%' THEN 1
                         WHEN vp.auth_code LIKE 'EP%' AND pt.name NOT LIKE '%ออกหน่วย%' AND pt.name NOT LIKE '%ชำระเงิน%' THEN 1
                         ELSE 0 END) as issues
            FROM ovst o
            JOIN patient p ON o.hn = p.hn
            LEFT JOIN pttype pt ON o.pttype = pt.pttype
            LEFT JOIN visit_pttype vp ON o.vn = vp.vn
            LEFT JOIN rcpt_print r ON o.vn = r.vn AND (r.status IS NULL OR r.status != 'ABORT')
            {$filters['sql']}
            GROUP BY pt.pttype, pt.name, pt.nhso_code
            ORDER BY issues DESC";
            
            $pttypeStmt = $pdo->prepare($pttypeSql);
            $pttypeStmt->execute($filters['binds']);
            $statsPttype = $pttypeStmt->fetchAll();

            echo json_encode([
                'status' => 'success',
                'data' => [
                    'kpis' => [
                        'total_visits' => intval($stats['total_visits'] ?? 0),
                        'total_patients' => intval($stats['total_patients'] ?? 0),
                        'no_authen' => intval($stats['no_authen'] ?? 0),
                        'authen_pp' => intval($stats['authen_pp'] ?? 0),
                        'authen_ep' => intval($stats['authen_ep'] ?? 0),
                        'not_closed_hosxp' => intval($stats['not_closed_hosxp'] ?? 0),
                        'not_closed_nhso' => intval($stats['not_closed_nhso'] ?? 0),
                        'closed_nhso' => intval($stats['authen_pp'] ?? 0),
                        'loss_prevention_value' => floatval($lossResult['loss_value'] ?? 0.0)
                    ],
                    'by_department' => $statsDept,
                    'by_pttype' => $statsPttype
                ]
            ]);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;
        
    case 'get_list':
        try {
            $filters = buildFilters($_GET);
            
            // Total records count query
            $countSql = "SELECT COUNT(o.vn) as total
            FROM ovst o
            JOIN patient p ON o.hn = p.hn
            LEFT JOIN pttype pt ON o.pttype = pt.pttype
            LEFT JOIN visit_pttype vp ON o.vn = vp.vn
            LEFT JOIN rcpt_print r ON o.vn = r.vn AND (r.status IS NULL OR r.status != 'ABORT')
            {$filters['sql']}";
            
            $countStmt = $pdo->prepare($countSql);
            $countStmt->execute($filters['binds']);
            $totalCount = intval($countStmt->fetch()['total'] ?? 0);
            
            $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
            if ($page < 1) $page = 1;
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
            if ($limit < 1) $limit = 20;
            $totalPages = ceil($totalCount / $limit);
            if ($page > $totalPages && $totalPages > 0) $page = $totalPages;
            $offset = ($page - 1) * $limit;
            
            // Sorting
            $allowedSort = ['vstdate', 'hn', 'fname'];
            $sortBy = $_GET['sort_by'] ?? 'vstdate';
            $sortOrder = $_GET['sort_order'] ?? 'DESC';
            
            if (!in_array($sortBy, $allowedSort)) $sortBy = 'vstdate';
            $sortOrder = ($sortOrder === 'ASC') ? 'ASC' : 'DESC';
            
            if ($sortBy === 'fname') {
                $orderBy = "ORDER BY p.fname {$sortOrder}";
            } else if ($sortBy === 'hn') {
                $orderBy = "ORDER BY o.hn {$sortOrder}";
            } else {
                $orderBy = "ORDER BY o.vstdate {$sortOrder}, o.vsttime DESC";
            }
            
            $sql = "SELECT 
                o.vn, o.hn, o.vstdate, o.vsttime, o.pttype, o.cur_dep, o.spclty,
                pt.name as pttype_name, pt.nhso_code,
                p.cid, p.pname, p.fname, p.lname, p.birthday, p.sex, p.hometel,
                IFNULL(d.department, CONCAT('แผนก ', o.cur_dep)) as dept_name,
                vp.auth_code as nhso_authen_code,
                r.finance_number,
                c.nhso_status, c.nhso_total_amount, c.nhso_requst_datetime, c.nhso_privilege_amount, c.confirm_staff,
                IFNULL((SELECT SUM(oi.sum_price) FROM opitemrece oi WHERE oi.vn = o.vn), 0) as total_income
            FROM ovst o
            JOIN patient p ON o.hn = p.hn
            LEFT JOIN pttype pt ON o.pttype = pt.pttype
            LEFT JOIN visit_pttype vp ON o.vn = vp.vn
            LEFT JOIN rcpt_print r ON o.vn = r.vn AND (r.status IS NULL OR r.status != 'ABORT')
            LEFT JOIN kskdepartment d ON o.cur_dep = d.depcode
            LEFT JOIN nhso_confirm_privilege c ON o.vn = c.vn
            {$filters['sql']}
            {$orderBy}
            LIMIT " . intval($offset) . ", " . intval($limit);
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($filters['binds']);
            $list = $stmt->fetchAll();
            
            foreach ($list as &$row) {
                // Calculate age
                if (!empty($row['birthday']) && $row['birthday'] !== '0000-00-00') {
                    $birth = new DateTime($row['birthday']);
                    $today = new DateTime();
                    $row['age'] = $today->diff($birth)->y;
                } else {
                    $row['age'] = '-';
                }
                
                // Fullname
                $row['fullname'] = $row['pname'] . $row['fname'] . ' ' . $row['lname'];
                
                // Issue flags
                $row['has_confirm'] = !empty($row['nhso_authen_code']) ? 1 : 0;
                $row['is_nhso_confirmed'] = ($row['nhso_status'] === 'Y') ? 1 : 0;
            }
            
            echo json_encode([
                'status' => 'success',
                'data' => $list,
                'pagination' => [
                    'total_records' => $totalCount,
                    'total_pages' => $totalPages,
                    'current_page' => $page,
                    'limit' => $limit
                ]
            ], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;
        
    case 'get_details':
        $vn = $_GET['vn'] ?? '';
        if (empty($vn)) {
            echo json_encode(['status' => 'error', 'message' => 'Missing Visit Number (VN)']);
            exit;
        }
        
        try {
            $sql = "SELECT 
                o.vn, o.hn, o.vstdate, o.vsttime, o.pttype, o.cur_dep, o.spclty, o.doctor,
                pt.name as pttype_name, pt.nhso_code,
                p.cid, p.pname, p.fname, p.lname, p.birthday, p.sex, p.hometel,
                IFNULL(d.department, CONCAT('แผนก ', o.cur_dep)) as dept_name,
                vp.auth_code as nhso_authen_code,
                r.finance_number,
                c.nhso_status, c.nhso_total_amount, c.nhso_requst_datetime, c.nhso_response_datetime,
                c.nhso_privilege_amount, c.nhso_cash_amount, c.confirm_staff, c.nhso_seq,
                IFNULL((SELECT SUM(oi.sum_price) FROM opitemrece oi WHERE oi.vn = o.vn), 0) as total_income,
                IFNULL((SELECT GROUP_CONCAT(CONCAT(od.icd10, ' (', IFNULL(od.diagtype,''), ')') SEPARATOR ', ') FROM ovstdiag od WHERE od.vn = o.vn), '-') as diagnoses,
                IFNULL((SELECT doc.name FROM doctor doc WHERE doc.code = o.doctor), '-') as doctor_name,
                IFNULL((SELECT sp.name FROM spclty sp WHERE sp.spclty = o.spclty), '-') as spclty_name
            FROM ovst o
            JOIN patient p ON o.hn = p.hn
            LEFT JOIN pttype pt ON o.pttype = pt.pttype
            LEFT JOIN visit_pttype vp ON o.vn = vp.vn
            LEFT JOIN rcpt_print r ON o.vn = r.vn AND (r.status IS NULL OR r.status != 'ABORT')
            LEFT JOIN kskdepartment d ON o.cur_dep = d.depcode
            LEFT JOIN nhso_confirm_privilege c ON o.vn = c.vn
            WHERE o.vn = :vn";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':vn' => $vn]);
            $details = $stmt->fetch();
            
            if (!$details) {
                echo json_encode(['status' => 'error', 'message' => 'ไม่พบข้อมูล Visit Number: ' . $vn]);
                exit;
            }
            
            // Age
            if (!empty($details['birthday']) && $details['birthday'] !== '0000-00-00') {
                $birth = new DateTime($details['birthday']);
                $today = new DateTime();
                $details['age'] = $today->diff($birth)->y;
            } else {
                $details['age'] = '-';
            }
            $details['fullname'] = $details['pname'] . $details['fname'] . ' ' . $details['lname'];
            $details['has_confirm'] = !empty($details['nhso_authen_code']) ? 1 : 0;
            $details['is_nhso_confirmed'] = ($details['nhso_status'] === 'Y') ? 1 : 0;
            
            echo json_encode([
                'status' => 'success',
                'data' => $details
            ], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;
        
     case 'get_departments':
        try {
            $sql = "SELECT DISTINCT d.depcode, d.department 
                    FROM kskdepartment d
                    INNER JOIN ovst o ON o.cur_dep = d.depcode
                    WHERE d.department IS NOT NULL AND d.department != ''
                    AND d.depcode_active = 'Y'
                    ORDER BY d.department";
            $stmt = $pdo->query($sql);
            $depts = $stmt->fetchAll();
            echo json_encode(['status' => 'success', 'data' => $depts], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;
 
     case 'export':
        try {
            $filters = buildFilters($_GET);
            
            $sql = "SELECT 
                o.vn, o.hn, o.vstdate, o.vsttime, o.pttype,
                pt.name as pttype_name, pt.nhso_code,
                p.cid, p.pname, p.fname, p.lname, p.hometel,
                IFNULL(d.department, o.cur_dep) as dept_name,
                vp.auth_code as nhso_authen_code,
                r.finance_number,
                c.nhso_status, c.nhso_total_amount,
                IFNULL((SELECT SUM(oi.sum_price) FROM opitemrece oi WHERE oi.vn = o.vn), 0) as total_income
            FROM ovst o
            JOIN patient p ON o.hn = p.hn
            LEFT JOIN pttype pt ON o.pttype = pt.pttype
            LEFT JOIN visit_pttype vp ON o.vn = vp.vn
            LEFT JOIN rcpt_print r ON o.vn = r.vn AND (r.status IS NULL OR r.status != 'ABORT')
            LEFT JOIN kskdepartment d ON o.cur_dep = d.depcode
            LEFT JOIN nhso_confirm_privilege c ON o.vn = c.vn
            {$filters['sql']}
            ORDER BY o.vstdate DESC, o.vsttime DESC";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($filters['binds']);
            $rows = $stmt->fetchAll();
            
            $filename = "nhso_audit_hosxp_" . date('Ymd_His') . ".csv";
            
            header('Content-Description: File Transfer');
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=' . $filename);
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            
            $output = fopen('php://output', 'w');
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            
            fputcsv($output, [
                'VN', 'HN', 'เลขบัตรประชาชน', 'ชื่อ-นามสกุล',
                'วันที่รับบริการ', 'เวลา', 'แผนก',
                'รหัสสิทธิ์', 'สิทธิการรักษา',
                'ค่ารักษาพยาบาล (บาท)',
                'Authen Code', 'เลขใบเสร็จ / Invoice', 'จำนวนเงินที่ Confirm (บาท)',
                'สถานะเคลม', 'เบอร์โทรศัพท์'
            ]);
            
            foreach ($rows as $row) {
                $fullname = $row['pname'] . $row['fname'] . ' ' . $row['lname'];
                
                // Status mapping
                $statusParts = [];
                if (empty($row['nhso_authen_code'])) {
                    if (stripos($row['pttype_name'], 'ออกหน่วย') !== false || stripos($row['pttype_name'], 'ชำระเงิน') !== false) {
                        $statusParts[] = 'ไม่ต้องขอ Authen';
                    } else {
                        $statusParts[] = 'ไม่มี Authen';
                    }
                } else {
                    $statusParts[] = $row['nhso_authen_code'];
                }
                
                if (empty($row['finance_number'])) {
                    $statusParts[] = 'ไม่ปิด visit';
                } else {
                    $statusParts[] = 'มี Invoice ' . $row['finance_number'];
                }
                
                if (empty($row['nhso_authen_code'])) {
                    $statusParts[] = 'ไม่มีสิทธิ์';
                } else if (strpos($row['nhso_authen_code'], 'PP') === 0) {
                    $statusParts[] = 'ปิดสิทธิ์ (PP)';
                } else if (strpos($row['nhso_authen_code'], 'EP') === 0) {
                    $statusParts[] = 'ไม่ปิดสิทธิ์ (EP)';
                }
                
                $statusText = implode(' | ', $statusParts);
                
                fputcsv($output, [
                    $row['vn'],
                    $row['hn'],
                    "'" . $row['cid'],
                    $fullname,
                    $row['vstdate'],
                    $row['vsttime'],
                    $row['dept_name'],
                    $row['pttype'],
                    $row['pttype_name'],
                    $row['total_income'],
                    $row['nhso_authen_code'] ?? '-',
                    $row['finance_number'] ?? '-',
                    $row['nhso_total_amount'] ?? 0,
                    $statusText,
                    $row['hometel'] ?? '-'
                ]);
            }
            
            fclose($output);
            exit;
            
        } catch (Exception $e) {
            header('Content-Type: text/html; charset=utf-8');
            echo "Error: " . $e->getMessage();
        }
        break;
        
    default:
        echo json_encode(['status' => 'error', 'message' => 'Action not defined. Available: get_stats, get_list, get_details, get_departments, export']);
        break;
}
