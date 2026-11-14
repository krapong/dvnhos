<?php
function report_bootstrap(array $user): array {
    $query='SELECT department,report_date,shift,payload,version FROM reports';
    if($user['unit']==='all')$rows=db()->query($query.' ORDER BY report_date,id')->fetchAll();
    else {$q=db()->prepare($query.' WHERE department=? ORDER BY report_date,id');$q->execute([$user['unit']]);$rows=$q->fetchAll();}
    $result=[];
    foreach($rows as $r)$result[$r['department'].'_'.$r['report_date'].'_'.$r['shift']]=['report'=>json_decode($r['payload'],true,512,JSON_THROW_ON_ERROR),'version'=>(int)$r['version']];
    return $result;
}
function report_reply(int $status,array $body): never {
    http_response_code($status);header('Content-Type: application/json; charset=utf-8');echo json_encode($body,JSON_UNESCAPED_UNICODE);exit;
}
function reports_api(array $user): never {
    if($_SERVER['REQUEST_METHOD']==='GET')report_reply(200,['reports'=>report_bootstrap($user)]);
    if($_SERVER['REQUEST_METHOD']!=='POST')report_reply(405,['error'=>'Method not allowed']);
    if(!allowed($user,'entry'))report_reply(403,['error'=>'ไม่มีสิทธิ์บันทึกรายงาน']);
    $unit=$_POST['department']??'';$date=$_POST['date']??'';$shift=$_POST['shift']??'';
    if(!in_array($unit,NCC_UNITS,true)||($user['unit']!=='all'&&$user['unit']!==$unit))report_reply(403,['error'=>'ไม่มีสิทธิ์ในหน่วยงานนี้']);
    if(str_starts_with($unit,'ipd_'))report_reply(422,['error'=>'กรุณาบันทึก IPD ผ่านแบบฟอร์มหอผู้ป่วยใน']);
    $parsed=DateTimeImmutable::createFromFormat('!Y-m-d',$date);
    if(!$parsed||$parsed->format('Y-m-d')!==$date||!in_array($shift,['day','morning','evening','night'],true))report_reply(422,['error'=>'วันที่หรือเวรไม่ถูกต้อง']);
    $raw=$_POST['report']??'';
    if(!is_string($raw)||strlen($raw)>100000)report_reply(422,['error'=>'ข้อมูลรายงานใหญ่เกินกำหนด']);
    try {$report=json_decode($raw,true,32,JSON_THROW_ON_ERROR);}catch(Throwable $e){report_reply(422,['error'=>'ข้อมูล JSON ไม่ถูกต้อง']);}
    $templates=json_decode(file_get_contents(__DIR__.'/report-templates.json'),true);
    $template=$templates[$unit];
    if(!is_array($report))report_reply(422,['error'=>'ข้อมูลรายงานไม่ถูกต้อง']);
    foreach($report as $key=>$value) {
        if(!in_array($key,['date','period','riskPeriod','shift','note','productivity','services','risks','admissions','staff'],true))report_reply(422,['error'=>'ฟิลด์รายงานไม่ถูกต้อง']);
        if(!in_array($key,['services','risks','admissions','staff'],true)&&(!is_string($value)||mb_strlen($value)>500||preg_match('/[<>]/',$value)))report_reply(422,['error'=>'รายละเอียดรายงานไม่ถูกต้อง']);
    }
    foreach(['date','period'] as $field)if(empty($report[$field]))report_reply(422,['error'=>'รายละเอียดรายงานไม่ครบ']);
    foreach(['services','risks','admissions','staff'] as $group) {
        $expected=$template[$group]??[];$rows=$report[$group]??[];
        if(!is_array($rows)||count($rows)!==count($expected))report_reply(422,['error'=>'รายการรายงานไม่ครบ']);
        foreach($expected as $i=>$row) {
            $actual=$rows[$i]??null;
            if(!is_array($actual)||count($actual)!==count($row)||($actual[0]??null)!==$row[0])report_reply(422,['error'=>'แบบฟอร์มรายงานไม่ตรงกับหน่วยงาน']);
            foreach(array_slice($actual,1) as $column=>$v) {
                if(!is_string($v)||strlen($v)>100||trim($v)===''||(!preg_match('/^\d+(?:\/\d+)*(?:\s*\(\d+(?:\/\d+)*\))?$/D',$v)&&$v!=='-'&&$v!==$row[$column+1]))report_reply(422,['error'=>'ค่ารายการไม่ถูกต้อง']);
            }
        }
    }
    if(isset($report['productivity'])&&(!is_numeric($report['productivity'])||!is_finite((float)$report['productivity'])||(float)$report['productivity']<0))report_reply(422,['error'=>'Productivity ไม่ถูกต้อง']);
    $payload=json_encode($report,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
    $expectedVersion=filter_var($_POST['version']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>0]]);
    if($expectedVersion===false)report_reply(422,['error'=>'กรุณาโหลดรายงานใหม่']);
    $connection=db();$connection->beginTransaction();
    try {
        $q=$connection->prepare('SELECT id,version FROM reports WHERE department=? AND report_date=? AND shift=? FOR UPDATE');$q->execute([$unit,$date,$shift]);$existing=$q->fetch();
        if(($existing?(int)$existing['version']:0)!==$expectedVersion){$connection->rollBack();report_reply(409,['error'=>'มีผู้แก้ไขรายงานนี้แล้ว กรุณาโหลดหน้าใหม่ก่อนบันทึก']);}
        $version=$expectedVersion+1;
        if($existing){$id=$existing['id'];$connection->prepare('UPDATE reports SET payload=?,version=?,updated_by=? WHERE id=?')->execute([$payload,$version,$user['id'],$id]);}
        else {$connection->prepare('INSERT INTO reports(department,report_date,shift,payload,updated_by) VALUES(?,?,?,?,?)')->execute([$unit,$date,$shift,$payload,$user['id']]);$id=$connection->lastInsertId();}
        $connection->prepare('INSERT INTO report_history(report_id,version,payload,changed_by) VALUES(?,?,?,?)')->execute([$id,$version,$payload,$user['id']]);
        $connection->commit();report_reply(200,['report'=>$report,'version'=>$version]);
    } catch(PDOException $e) {
        if($connection->inTransaction())$connection->rollBack();
        if(in_array((string)$e->getCode(),['23000','40001'],true))report_reply(409,['error'=>'รายงานถูกแก้ไขพร้อมกัน กรุณาโหลดหน้าใหม่']);
        throw $e;
    }
}
