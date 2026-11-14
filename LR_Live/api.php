<?php
require_once __DIR__.'/security.php';
header('Content-Type: application/json; charset=UTF-8');
function respond(int $status, array $payload): never {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR);
    exit;
}
if(isset($_GET['lookup'])){
 if($_GET['lookup']!=='providers')respond(400,['ok'=>false,'error'=>'รายการค้นหาไม่ถูกต้อง']);
 require_once __DIR__.'/report.php';require_once __DIR__.'/providers.php';
 try{respond(200,['ok'=>true,'providers'=>deliveryProviders(reportConnection($cfg),$cfg['db_charset'])]);}
 catch(Throwable $e){error_log('[LR providers] code '.$e->getCode());respond(503,['ok'=>false,'error'=>'โหลดรายชื่อผู้ทำคลอดไม่สำเร็จ กรุณาลองใหม่']);}
}
$from = $_GET['from'] ?? date('Y').'-10-01';
$to = $_GET['to'] ?? date('Y').'-12-31';
foreach ([$from,$to] as $date) {
    if (!is_string($date) || !preg_match('/\A[0-9]{4}-[0-9]{2}-[0-9]{2}\z/', $date)) {
        respond(400,['ok'=>false,'error'=>'รูปแบบวันที่ไม่ถูกต้อง']);
    }
    $d = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
    if (!$d || $d->format('Y-m-d') !== $date) respond(400,['ok'=>false,'error'=>'รูปแบบวันที่ไม่ถูกต้อง']);
}
if ($to < $from || (strtotime($to)-strtotime($from))/86400 > 370) {
    respond(400,['ok'=>false,'error'=>'เลือกช่วงวันที่ไม่เกิน 370 วัน']);
}
$scope=$_GET['scope']??'period';
if(!is_string($scope)||!in_array($scope,['period','ward','search'],true))respond(400,['ok'=>false,'error'=>'ขอบเขตข้อมูลไม่ถูกต้อง']);
$query=$_GET['q']??'';
if(!is_string($query)||strlen($query)>300)respond(400,['ok'=>false,'error'=>'คำค้นไม่ถูกต้อง']);
$basis=$_GET['basis']??'admission';
if(!is_string($basis)||!in_array($basis,['admission','discharge','current','all'],true))respond(400,['ok'=>false,'error'=>'ประเภทช่วงเวลาไม่ถูกต้อง']);
$currentWard=$scope==='ward'||($scope==='search'&&$basis==='current');
$includeAdmissions=true;
$identity=[];
foreach(['hn','an'] as $key){
    $value=$_GET[$key]??'';
    if(!is_string($value)||($value!==''&&!preg_match('/\A[0-9A-Za-z-]{1,30}\z/',$value)))respond(400,['ok'=>false,'error'=>'รูปแบบ HN/AN ไม่ถูกต้อง']);
    if($value!=='')$identity[$key]=$value;
}
require_once __DIR__.'/report.php';
require_once __DIR__.'/family.php';
require_once __DIR__.'/report-labs.php';
require_once __DIR__.'/manual-store.php';
if(!function_exists('reportDeliveryAdmissionWhere'))respond(503,['ok'=>false,'error'=>'ไฟล์ระบบอัปเดตไม่ครบ กรุณาอัปเดต report.php พร้อม api.php และ index.php']);
$readStage='connection';
try {
    $pdo=reportConnection($cfg);
    $pdo->exec('SET SESSION TRANSACTION READ ONLY');
    $params=[':start'=>$from,':end'=>(new DateTimeImmutable($to))->modify('+1 day')->format('Y-m-d')];
    $where='i.regdate>=:start AND i.regdate<:end';
    if($scope==='search'&&$basis==='all'){$where='1=1';$params=[];}
    if($scope==='search'&&$basis==='discharge')$where='i.dchdate>=:start AND i.dchdate<:end';
    if($currentWard){$where=reportCurrentWardWhere();$params=[':ward'=>'04'];}
    if($scope==='period')$where.=' AND '.reportDeliveryAdmissionWhere();
    if($scope==='search'){
      $where.=' AND '.reportDeliveryAdmissionWhere();
      if(trim($query)!==''){
        $term='%'.strtr(trim($query),['!'=>'!!','%'=>'!%','_'=>'!_']).'%';
        $fields=['i.hn','i.an','f.hn',"COALESCE(NULLIF(f.infant_an,''),nb.an)","CONCAT(COALESCE(p.pname,''),COALESCE(p.fname,''),' ',COALESCE(p.lname,''))"];
        $conditions=[];
        foreach($fields as $index=>$field){
            $parameter=':search'.$index;
            $conditions[]="$field LIKE $parameter ESCAPE '!'";
            $params[$parameter]=$term;
        }
        $where.=' AND ('.implode(' OR ',$conditions).')';
      }
    }
    foreach($identity as $key=>$value){$where.=' AND i.'.$key.'=:'.$key;$params[':'.$key]=$value;}
    $pdo->beginTransaction();
    $readStage='main';
    $stmt=$pdo->prepare(reportSql($where,$includeAdmissions));
    $stmt->execute($params);
    $rows=array_map(fn($r)=>reportNormalize($r,$cfg['db_charset']),$stmt->fetchAll());
    if($scope==='search'){
        if(count($rows)>200)respond(422,['ok'=>false,'error'=>'พบมากกว่า 200 รายการ กรุณาระบุคำค้นให้เจาะจงขึ้น']);
        $ans=array_values(array_unique(array_column($rows,'AN มารดา')));
        $params=[];
        foreach($ans as $index=>$an)$params[':an'.$index]=$an;
        $where=$params?'i.an IN ('.implode(',',array_keys($params)).')':'1=0';
    }
    if(count($rows)>10000)respond(422,['ok'=>false,'error'=>'มีข้อมูลเกิน 10,000 รายการ กรุณาลดช่วงวันที่']);
    $readStage='supplement';
    $warnings=[];$supplementAvailable=true;
    try{$stored=manualStore(static fn($data)=>$data);}
    catch(Throwable $e){
        $stored=[];$supplementAvailable=false;
        $reference=bin2hex(random_bytes(4));
        error_log('[LR API '.$reference.'] stage=supplement '.$e->getMessage());
        $warnings[]='โหลดฐานข้อมูลเสริมไม่สำเร็จ แสดงเฉพาะข้อมูล HOSxP ข้อมูลที่กรอกเพิ่มเติมและประวัติแก้ไขยังไม่แสดง กรุณาโหลดใหม่หลังแก้การเชื่อมต่อ (รหัส '.$reference.')';
    }
    $newborns=$scope==='period'?familyNewbornAdmissions($pdo,$from,$to):[];
    $links=familyLinks($pdo,array_column($rows,'AN มารดา'),array_column($newborns,'an'));
    $rows=familyEnrich($rows,$links);
    foreach($newborns as &$newborn){
        $an=(string)$newborn['an'];
        $newborn['mother_an']=$links[$an]??null;
        $newborn['relationship_status']=array_key_exists($an,$links)?($links[$an]===null?'ข้อมูล AN มารดาขัดแย้ง':'ยืนยันจาก AN ใน HOSxP'):'ยังไม่พบ AN มารดา';
    }unset($newborn);
    $rows=array_map(function($r)use($stored,$supplementAvailable){$r=manualMerge($r,$stored);$r['_supplement_available']=$supplementAvailable;return $r;},$rows);
    $sql = [
      'labs'=> 'SELECT m.an AS `AN มารดา`,m.hn AS `HN มารดา`,x.lab_id AS `รหัส Lab`,x.lab_value AS `ผล Lab 1`,x.lab_value2 AS `ผล Lab 2` FROM ipt_labour m JOIN ipt_labour_lab x ON x.ipt_labour_id=m.ipt_labour_id WHERE EXISTS (SELECT 1 FROM ipt i WHERE i.an=m.an AND i.regdate>=:start AND i.regdate<:end) ORDER BY m.an,x.lab_id LIMIT 20001',
      'complications'=> 'SELECT m.an AS `AN มารดา`,c.labour_complication_id AS `รหัสภาวะแทรกซ้อน`,c.labour_stage_id AS `รหัสระยะคลอด`,c.complication_note AS `รายละเอียด` FROM ipt_labour m JOIN ipt_labour_complication c ON c.ipt_labour_id=m.ipt_labour_id WHERE EXISTS (SELECT 1 FROM ipt i WHERE i.an=m.an AND i.regdate>=:start AND i.regdate<:end) ORDER BY m.an,c.ipt_labour_complication_id LIMIT 20001',
      'vaccines'=> 'SELECT m.an AS `AN มารดา`,v.wbc_vaccine_id AS `รหัสวัคซีน`,v.vaccine_lotno AS `Lot`,v.vaccine_expire_date AS `วันหมดอายุ`,v.doctor_code AS `รหัสผู้ให้` FROM ipt_labour m JOIN ipt_labour_vaccine v ON v.ipt_labour_id=m.ipt_labour_id WHERE EXISTS (SELECT 1 FROM ipt i WHERE i.an=m.an AND i.regdate>=:start AND i.regdate<:end) ORDER BY m.an,v.ipt_labour_vaccine_id LIMIT 20001'
    ];
    $sql=array_replace($sql,detailedLabQueries($where,$includeAdmissions));
    $data=['main'=>$rows];
    if($scope==='period'){
        $readStage='main';
        $data['newborn_admissions']=count($newborns);
        $data['newborns']=$newborns;
    }
    foreach($sql as $key=>$query){
      $readStage=$key;
      if(!in_array($key,['labs','lab_results'],true))$query=str_replace('i.regdate>=:start AND i.regdate<:end',$where,$query);
      preg_match_all('/AS \x60([^\x60]+)\x60/',$query,$aliases);
      $aliasIndex=0;
      $query=preg_replace_callback('/AS \x60([^\x60]+)\x60/',static function($m)use(&$aliasIndex){return 'AS field_'.($aliasIndex++);},$query);
      $stmt=$pdo->prepare($query);$stmt->execute($params);$data[$key]=array_map(fn($row)=>reportNormalizeLinked(array_combine($aliases[1],array_values($row)),$cfg['db_charset']),$stmt->fetchAll());
      if(count($data[$key])>20000)respond(422,['ok'=>false,'error'=>'ข้อมูลประกอบเกินขนาด กรุณาลดช่วงวันที่']);
    }
    $pdo->commit();
    usageLog('data_read',['scope'=>$scope,'basis'=>$currentWard?'current':$basis,'from'=>$from,'to'=>$to,'keyword_used'=>trim($_GET['q']??'')!=='','count'=>count($rows)]);
    respond(200,['ok'=>true,'warnings'=>$warnings,'data'=>$data,'fields'=>array_map(static fn($f)=>array_diff_key($f,['sql'=>true]),reportFields()),'csrf'=>$_SESSION['csrf'],'period'=>['from'=>$from,'to'=>$to,'basis'=>$currentWard?'current_ward':($scope==='search'?$basis:'admission'),'ward'=>$currentWard?'04':null]]);
} catch(Throwable $e) {
    if(isset($pdo)&&$pdo->inTransaction())$pdo->rollBack();
    usageLog('data_error',['scope'=>$scope,'code'=>(string)$e->getCode()]);
    $reference=bin2hex(random_bytes(4));
    error_log('[LR API '.$reference.'] stage='.$readStage.' '.$e->getMessage());
    $stageNames=['connection'=>'เชื่อมต่อฐาน HOSxP','main'=>'ทะเบียนมารดาและทารก','supplement'=>'ฐานข้อมูลเสริม','labs'=>'Lab ทะเบียนคลอด','lab_results'=>'ผลห้องปฏิบัติการ','complications'=>'ภาวะแทรกซ้อน','vaccines'=>'วัคซีน'];
    respond(500,['ok'=>false,'error'=>'อ่านข้อมูลไม่สำเร็จ: '.($stageNames[$readStage]??$readStage).' (รหัส '.$reference.') กรุณาตรวจสอบ PHP error log']);
}
