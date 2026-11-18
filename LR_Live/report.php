<?php
function reportNewbornAdmissionCount(PDO $pdo,string $from,string $to): int {
    $names=static fn(string $name): string=>'0x'.bin2hex($name).',0x'.bin2hex(iconv('UTF-8','TIS-620',$name));
    $sql='SELECT COUNT(DISTINCT i.an) FROM ipt i WHERE i.regdate>=:start AND i.regdate<:end'
        .' AND EXISTS (SELECT 1 FROM spclty s WHERE s.spclty=i.spclty AND CAST(s.name AS BINARY) IN ('.$names('กุมารเวชกรรม').'))'
        .' AND EXISTS (SELECT 1 FROM ipt_admit_type a WHERE a.ipt_admit_type_id=i.ipt_admit_type_id AND CAST(a.ipt_admit_type_name AS BINARY) IN ('.$names('แรกเกิด').'))';
    $stmt=$pdo->prepare($sql);
    $stmt->execute([':start'=>$from,':end'=>(new DateTimeImmutable($to))->modify('+1 day')->format('Y-m-d')]);
    return (int)$stmt->fetchColumn();
}
function reportConnection(array $cfg): PDO {
    // MySQL converts source table encodings to UTF-8, including Thai aliases.
    $pdo=new PDO(sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',$cfg['db_host'],$cfg['db_port'],$cfg['db_name']),$cfg['db_user'],$cfg['db_password'],[
        PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false,PDO::ATTR_TIMEOUT=>8]);
    $pdo->exec('SET SESSION group_concat_max_len=1048576');
    return $pdo;
}
function reportFields(): array {
    $fields=require __DIR__.'/report-fields.php';
    $options=require __DIR__.'/field-options.php';
    foreach($fields as &$field)if(isset($options[$field['label']]))$field['options']=$options[$field['label']];
    unset($field);return $fields;
}
function reportValidateChoice(array $field,string $value,?string $previous=null): void {
    if($value!==''&&isset($field['options'])&&!in_array($value,$field['options'],true)&&$value!==$previous)throw new InvalidArgumentException('กรุณาเลือกค่าจากรายการที่กำหนด: '.$field['label']);
}
function reportDeliveryAdmissionWhere(): string {
    // Some HOSxP installations store TIS-620 text in columns with another charset.
    $name='คลอดบุตร';
    $names=['0x'.bin2hex($name),'0x'.bin2hex(iconv('UTF-8','TIS-620',$name))];
    return 'EXISTS (SELECT 1 FROM ipt_admit_type admit_type WHERE admit_type.ipt_admit_type_id=i.ipt_admit_type_id AND CAST(admit_type.ipt_admit_type_name AS BINARY) IN ('.implode(',',$names).'))';
}
function reportSql(string $where,bool $currentWard=false): string {
    $select=['l.ipt_labour_id AS `_labour_id`','f.ipt_labour_infant_id AS `_infant_id`',reportDeliveryAdmissionWhere().' AS `_delivery_admission`'];
    foreach(reportFields() as $index=>$field)$select[]=$field['sql'].' AS `field_'.$index.'`';
    $query='SELECT '.implode(",\n",$select).<<<'SQL'

FROM ipt_labour l
JOIN ipt i ON i.an=l.an
LEFT JOIN patient p ON p.hn=i.hn
LEFT JOIN dchstts ds ON ds.dchstts=i.dchstts
LEFT JOIN ward w ON w.ward=i.ward
LEFT JOIN pttype pt ON pt.pttype=i.pttype
LEFT JOIN spclty sp ON sp.spclty=i.spclty
LEFT JOIN ipt_labour_anc_type at ON at.ipt_labour_anc_type_id=l.ipt_labour_anc_type_id
LEFT JOIN ipt_labour_place ipl ON ipl.ipt_labour_place_id=l.ipt_labour_place_id
LEFT JOIN membrane_type mt ON mt.membrane_type_id=l.membrane_type_id
LEFT JOIN membrane_explode_type met ON met.membrane_explode_type_id=l.membrane_explode_type_id
LEFT JOIN ipt_labour_infant f ON f.ipt_labour_id=l.ipt_labour_id
LEFT JOIN sex sx ON sx.code=f.sex
LEFT JOIN infant_delivery_type idt ON idt.infant_delivery_type_id=f.infant_delivery_type_id
LEFT JOIN infant_indication_type iit ON iit.infant_indication_type_id=f.infant_indication_type_id
LEFT JOIN infant_adaptor_type iad ON iad.infant_adaptor_type_id=f.infant_adaptor_type_id
LEFT JOIN ipt_newborn nb ON nb.an=COALESCE(NULLIF(f.infant_an,''),
    (SELECT MIN(n2.an) FROM ipt_newborn n2
     WHERE n2.mother_an=l.an AND n2.born_date=f.birth_date
       AND (SELECT COUNT(*) FROM ipt_labour_infant f2 WHERE f2.ipt_labour_id=l.ipt_labour_id AND f2.birth_date=f.birth_date)=1
     HAVING COUNT(*)=1))
LEFT JOIN ipt_newborn_condition_type1 nc1 ON nc1.id=nb.birthcondition1
LEFT JOIN ipt_newborn_condition_type2 nc2 ON nc2.id=nb.birthcondition2
LEFT JOIN newborn_death_cuase ndc ON ndc.newborn_death_cause_id=nb.newborn_death_cause_id
LEFT JOIN nationality nt ON nt.nationality=p.nationality
LEFT JOIN labor lg ON lg.laborid=(SELECT MIN(lg2.laborid) FROM labor lg2 WHERE lg2.an=l.an HAVING COUNT(*)=1)
LEFT JOIN ipt_pregnancy pg ON pg.an=(SELECT pg2.an FROM ipt_pregnancy pg2 WHERE pg2.an=i.an GROUP BY pg2.an HAVING COUNT(*)=1)
LEFT JOIN doctor dd ON dd.code=f.labour_doctor
LEFT JOIN doctor sd ON sd.code=lg.sulture_doctor
LEFT JOIN person_anc pa ON pa.person_anc_id=(
    SELECT MIN(pa2.person_anc_id) FROM person_anc pa2 JOIN person p2 ON p2.person_id=pa2.person_id
    WHERE p2.patient_hn=i.hn
      AND (l.preg_no IS NULL OR l.preg_no=0 OR pa2.preg_no=l.preg_no)
      AND (pa2.anc_register_date IS NULL OR CAST(pa2.anc_register_date AS CHAR)='0000-00-00' OR pa2.anc_register_date<=COALESCE(f.birth_date,i.regdate))
      AND ((NULLIF(CAST(l.lmp AS CHAR),'0000-00-00') IS NOT NULL AND pa2.lmp=l.lmp)
        OR (NULLIF(CAST(l.edc AS CHAR),'0000-00-00') IS NOT NULL AND pa2.edc=l.edc)
        OR (f.birth_date IS NOT NULL AND pa2.labor_date=f.birth_date))
    HAVING COUNT(*)=1
)
LEFT JOIN (
    SELECT ipt_labour_id,
      MAX(CASE WHEN lab_id=1 AND value_count=1 THEN single_value END) AS vdrl1,
      MAX(CASE WHEN lab_id=2 AND value_count=1 THEN single_value END) AS hiv1,
      MAX(CASE WHEN lab_id=3 AND value_count=1 THEN single_value END) AS hbsag,
      MAX(CASE WHEN lab_id=4 AND value_count=1 THEN single_value END) AS hct1,
      MAX(CASE WHEN lab_id=5 AND value_count=1 THEN single_value END) AS thal,
      MAX(CASE WHEN lab_id=7 AND value_count=1 THEN single_value END) AS abo,
      MAX(CASE WHEN lab_id=8 AND value_count=1 THEN single_value END) AS rh,
      MAX(CASE WHEN lab_id=10 AND value_count=1 THEN single_value END) AS vdrl2,
      MAX(CASE WHEN lab_id=11 AND value_count=1 THEN single_value END) AS hct2,
      MAX(CASE WHEN lab_id=12 AND value_count=1 THEN single_value END) AS hiv2,
      GROUP_CONCAT(CASE WHEN value_count>1 THEN lab_id END ORDER BY lab_id SEPARATOR ', ') AS conflict_ids
    FROM (
      SELECT ipt_labour_id,lab_id,COUNT(DISTINCT BINARY NULLIF(TRIM(lab_value),'')) AS value_count,
             MIN(NULLIF(TRIM(lab_value),'')) AS single_value
      FROM ipt_labour_lab GROUP BY ipt_labour_id,lab_id
    ) lab_values GROUP BY ipt_labour_id
) lab ON lab.ipt_labour_id=l.ipt_labour_id
WHERE 
SQL
        .$where.' ORDER BY i.regdate DESC,i.an,l.ipt_labour_id,f.infant_number,f.ipt_labour_infant_id LIMIT 10001';
    if($currentWard)$query=str_replace("FROM ipt_labour l\nJOIN ipt i ON i.an=l.an","FROM ipt i\nLEFT JOIN ipt_labour l ON l.an=i.an",$query);
    return $query;
}
function reportNormalize(array $row,string $charset): array {
    foreach(reportFields() as $index=>$field){
        $value=$row['field_'.$index];
        if(is_string($value)&&(trim($value)===''||($field['type']==='date'&&$value==='0000-00-00')))$value=null;
        $row[$field['label']]=$value;unset($row['field_'.$index]);
    }
    $encodingIssues=[];
    foreach($row as $key=>&$value)if(is_string($value)&&preg_match('//u',$value)!==1){$raw=$value;$issue=false;$value=reportDecodeText($value,$charset,$issue);if($issue)$encodingIssues[$key]=bin2hex($raw);}unset($value);
    if($encodingIssues)$row['_encoding_issues']=$encodingIssues;
    // Numeric HOSxP sex codes remain reliable when the lookup label has damaged encoding.
    $sexCode=trim((string)($row['เพศทารก (รหัส)']??''));
    if(in_array($sexCode,['1','2'],true)){
        $row['_source_sex_label']=$row['เพศทารก']??null;
        $row['เพศทารก']=$sexCode==='1'?'ชาย':'หญิง';
    }
    $row['_record_id']=$row['_labour_id']===null?'a'.$row['AN มารดา']:'l'.$row['_labour_id'].'-f'.($row['_infant_id']??'none');
    return $row;
}
function reportMissing(mixed $value): bool { return $value===null || (is_string($value)&&trim($value)===''); }
function reportRecordLookup(string $id): array {
    if(preg_match('/\Al([0-9]+)-f([0-9]+|none)\z/',$id,$m)){
        $params=[':labour'=>$m[1]];
        if($m[2]!=='none')$params[':infant']=$m[2];
        return ['where'=>'l.ipt_labour_id=:labour AND '.($m[2]==='none'?'f.ipt_labour_infant_id IS NULL':'f.ipt_labour_infant_id=:infant'),'params'=>$params,'admission'=>false];
    }
    if(preg_match('/\Aa([0-9A-Za-z-]{1,30})\z/',$id,$m))return ['where'=>'i.an=:an AND l.ipt_labour_id IS NULL','params'=>[':an'=>$m[1]],'admission'=>true];
    throw new InvalidArgumentException('รหัสรายการไม่ถูกต้อง');
}
function reportAllowsOverride(string $key): bool { return in_array($key,['G','TP','Pre','A','L','ชื่อผู้ทำคลอด','รหัสผู้ทำคลอด (ทารก)','เพศทารก'],true); }
function reportValidateCount(string $value): void {
    if($value!==''&&!preg_match('/\A[0-9]{1,3}\z/',$value))throw new InvalidArgumentException('G / TP / Pre / A / L ต้องเป็นจำนวนเต็ม 0–999 หรือเว้นว่าง');
}
function reportNormalizeLinked(array $row,string $charset): array {
    $issues=[];
    foreach($row as $key=>&$value)if(is_string($value)&&preg_match('//u',$value)!==1){$raw=$value;$issue=false;$value=reportDecodeText($value,$charset,$issue);if($issue)$issues[$key]=bin2hex($raw);}unset($value);
    if($issues)$row['หมายเหตุการเข้ารหัส (ช่อง:ข้อมูลดิบ hex)']=json_encode($issues,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
    return $row;
}

function reportDecodeText(string $value,string $charset,?bool &$issue=null): string {
    $issue=false;
    if(preg_match('//u',$value)===1)return $value;
    $converted=@iconv($charset,'UTF-8',$value);if($converted!==false)return $converted;
    $issue=true;
    // Preserve undefined legacy bytes visibly; never silently drop malformed history.
    if(in_array(strtolower(str_replace(['-','_'],'',$charset)),['tis620','cp874','windows874','iso885911'],true)){
        $out='';foreach(str_split($value) as $byte){$part=@iconv($charset,'UTF-8',$byte);$out.=$part===false?'\\x'.strtoupper(bin2hex($byte)):$part;}return $out;
    }
    return '[raw hex:'.bin2hex($value).']';
}

function reportCurrentWardWhere(): string { return "i.ward=:ward AND (i.dchdate IS NULL OR CAST(i.dchdate AS CHAR) IN ('','0000-00-00'))"; }
