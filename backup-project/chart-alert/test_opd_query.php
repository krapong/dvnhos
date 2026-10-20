<?php
require 'db_config.php';

$sql = "
    SELECT 
        v.vstdate,
        v.vsttime,
        v.hn,
        CONCAT(p.pname, p.fname, ' ', p.lname) as ptname,
        os.cc,
        v.pdx,
        k.department as dep,
        o.an,
        CASE 
            WHEN (v.pdx IS NULL OR trim(v.pdx) = '') AND (os.cc IS NULL OR trim(os.cc) = '') THEN 'ไม่มี Dx. และ ไม่มี CC/PI'
            WHEN (v.pdx IS NULL OR trim(v.pdx) = '') THEN 'ไม่มี Dx.'
            WHEN (os.cc IS NULL OR trim(os.cc) = '') THEN 'ไม่มี CC/PI'
            ELSE ''
        END as remark
    FROM vn_stat v
    LEFT JOIN ovst o ON o.vn = v.vn
    LEFT JOIN opdscreen os ON os.vn = v.vn
    LEFT JOIN patient p ON p.hn = v.hn
    LEFT JOIN kskdepartment k ON k.depcode = o.main_dep
    WHERE v.vstdate = CURDATE()
      AND ((v.pdx IS NULL OR trim(v.pdx) = '') OR (os.cc IS NULL OR trim(os.cc) = ''))
    LIMIT 5;
";
$stmt = $pdo->query($sql);
$res = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($res);
