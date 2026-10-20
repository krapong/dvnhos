<?php
// api.php
require_once 'db.php';

header('Content-Type: application/json');

try {
    $sql = "
        SELECT 
            ipt.an,
            ipt.hn,
            patient.pname,
            patient.fname,
            patient.lname,
            patient.sex,
            ward.name as ward_name,
            ward.ward as ward_code,
            ipt.regdate,
            ipt.regtime,
            COALESCE(icd101.name, ipt.provision_dx) as diag_name,
            COALESCE(icd101.code, ipt.provision_dx_icd) as icd10,
            doctor.name as doctor_name,
            COALESCE(bedno.bedname, iptadm.bedno, ipt.cur_bedno) as bedno,
            ipt.ipd_nurse_eval_range_code
        FROM ipt
        JOIN patient ON ipt.hn = patient.hn
        JOIN ward ON ipt.ward = ward.ward
        LEFT JOIN iptdiag ON ipt.an = iptdiag.an AND iptdiag.diagtype = '1'
        LEFT JOIN icd101 ON iptdiag.icd10 = icd101.code
        LEFT JOIN doctor ON ipt.admdoctor = doctor.code
        LEFT JOIN iptadm ON ipt.an = iptadm.an AND iptadm.outdate IS NULL
        LEFT JOIN bedno ON iptadm.bedno = bedno.bedno
        WHERE ipt.dchdate IS NULL
        AND (ipt.ipd_nurse_eval_range_code LIKE '3%' OR ipt.ipd_nurse_eval_range_code LIKE '4%')
    ";

    $stmt = $pdo->query($sql);
    $patients = $stmt->fetchAll();

    $male = [];
    $female = [];

    foreach ($patients as $p) {
        // Fix encoding for each field
        foreach ($p as $key => $value) {
            if (is_string($value)) {
                $converted = @iconv('TIS-620', 'UTF-8//IGNORE', $value);
                if ($converted !== false) {
                    $p[$key] = $converted;
                }
            }
        }

        $isMale = false;
        $isFemale = false;

        if ($p['ward_code'] == '02') {
            $isMale = true;
        } elseif ($p['ward_code'] == '03') {
            $isFemale = true;
        } else {
            if ($p['sex'] == '1') $isMale = true;
            if ($p['sex'] == '2') $isFemale = true;
        }

        if ($isMale) {
            $male[] = $p;
        } elseif ($isFemale) {
            $female[] = $p;
        }
    }

    $response = [
        'male' => $male,
        'female' => $female,
        'total' => count($patients),
        'last_update' => date('Y-m-d H:i:s'),
    ];

    $json = json_encode($response);
    if ($json === false) {
        echo json_encode(['error' => 'JSON encoding failed: ' . json_last_error_msg()]);
    } else {
        echo $json;
    }

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
} catch (Throwable $t) {
    echo json_encode(['error' => $t->getMessage()]);
}
