<?php
require_once __DIR__.'/security.php';
require_once __DIR__.'/report.php';
require_once __DIR__.'/providers.php';
require_once __DIR__.'/manual-store.php';
header('Content-Type: application/json; charset=UTF-8');
function manualResponse(int $status,array $data): never {http_response_code($status);echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);exit;}
if($_SERVER['REQUEST_METHOD']!=='POST'){header('Allow: POST');manualResponse(405,['ok'=>false,'error'=>'ใช้ POST เท่านั้น']);}
if(!hash_equals($_SESSION['csrf'],$_SERVER['HTTP_X_CSRF_TOKEN']??''))manualResponse(403,['ok'=>false,'error'=>'กรุณาโหลดข้อมูลใหม่ก่อนบันทึก']);
if((int)($_SERVER['CONTENT_LENGTH']??0)>65536)manualResponse(413,['ok'=>false,'error'=>'ข้อมูลเกินขนาด']);
try {
    $body=json_decode(file_get_contents('php://input'),true,32,JSON_THROW_ON_ERROR);
    if(!is_array($body)||!is_string($body['id']??null)||!is_int($body['version']??null)||!is_array($body['fields']??null)||count($body['fields'])>count(reportFields()))throw new InvalidArgumentException('ข้อมูลไม่ถูกต้อง');
    $lookup=reportRecordLookup($body['id']);
    $pdo=reportConnection($cfg);
    $stmt=$pdo->prepare(reportSql($lookup['where'],$lookup['admission']));$stmt->execute($lookup['params']);$rows=$stmt->fetchAll();
    if(count($rows)!==1)manualResponse(409,['ok'=>false,'error'=>'ไม่พบรายการที่ตรงกัน กรุณาโหลดข้อมูลใหม่']);
    $providerCodeKey='รหัสผู้ทำคลอด (ทารก)';$providerNameKey='ชื่อผู้ทำคลอด';
    if(array_key_exists($providerCodeKey,$body['fields'])||array_key_exists($providerNameKey,$body['fields'])){
        $code=$body['fields'][$providerCodeKey]??null;
        if(!is_string($code))throw new InvalidArgumentException('กรุณาเลือกผู้ทำคลอดจากรายชื่อ');
        $body['fields'][$providerNameKey]=$code===''?'':selectedDeliveryProvider($pdo,$cfg['db_charset'],$code)['name'];
    }
    $row=reportNormalize($rows[0],$cfg['db_charset']);$catalog=array_column(reportFields(),null,'label');
    foreach($body['fields'] as $key=>$value){
        $field=$catalog[$key]??null;
        if(!$field||$field['type']==='id'||!is_string($value)||strlen($value)>2000)throw new InvalidArgumentException('ฟิลด์หรือค่าข้อมูลไม่ถูกต้อง');
        if(!reportAllowsOverride($key)&&!reportMissing($row[$key]))manualResponse(409,['ok'=>false,'error'=>'มีข้อมูลจาก HOSxP แล้ว กรุณาโหลดใหม่']);
        $value=trim($value);$body['fields'][$key]=$value;
        if(in_array($key,['G','TP','Pre','A','L'],true))reportValidateCount($value);
        if($value==='')continue;
        if(!empty($field['checkbox'])&&!in_array($value,['Y','N'],true))throw new InvalidArgumentException('กรุณาเลือกให้แล้ว / ไม่ได้ให้ หรือเว้นว่าง');
        if($field['type']==='number'&&(!is_numeric($value)||!is_finite((float)$value)||(float)$value<0))throw new InvalidArgumentException('กรุณากรอกตัวเลขไม่ติดลบ');
        if($field['type']==='date'){
            if(!preg_match('/\A[0-9]{4}-[0-9]{2}-[0-9]{2}\z/',$value))throw new InvalidArgumentException('รูปแบบวันที่ไม่ถูกต้อง');
            $d=DateTimeImmutable::createFromFormat('!Y-m-d',$value);if(!$d||$d->format('Y-m-d')!==$value)throw new InvalidArgumentException('วันที่ไม่ถูกต้อง');
        }
        if($field['type']==='time'&&!preg_match('/\A(?:[01][0-9]|2[0-3]):[0-5][0-9](?::[0-5][0-9])?\z/',$value))throw new InvalidArgumentException('เวลาไม่ถูกต้อง');
        if(preg_match('/\AAPGAR (1|5|10) นาที(?: (hr|rr|reflex|tone|color))?\z/u',$key,$apgar)&&((float)$value>(isset($apgar[2])?2:10)||floor((float)$value)!==(float)$value))throw new InvalidArgumentException('APGAR รวมต้องอยู่ระหว่าง 0–10 และคะแนนย่อย 0–2');
    }
    $actor=$_SESSION['auth_user'];
    $result=manualStore(function(&$data)use($body,$row,$actor,$catalog){
        $entry=$data[$body['id']]??['version'=>0,'fields'=>[],'audit'=>[]];
        if($entry['version']!==$body['version'])throw new RuntimeException('ข้อมูลถูกแก้ไขโดยผู้อื่น กรุณาโหลดใหม่',409);
        $now=gmdate('c');
        foreach($body['fields'] as $key=>$value){
            reportValidateChoice($catalog[$key],$value,$entry['fields'][$key]['value']??null);
            if($value===''&&!isset($entry['fields'][$key]))continue;
            $previous=$entry['fields'][$key]['value']??(reportAllowsOverride($key)?(string)($row[$key]??''):'');if($previous===$value&&!(in_array($key,['ชื่อผู้ทำคลอด','รหัสผู้ทำคลอด (ทารก)'],true)&&($body['fields']['รหัสผู้ทำคลอด (ทารก)']??'')!==''&&!isset($entry['fields'][$key])))continue;
            $entry['audit'][]=['field'=>$key,'before'=>$previous,'after'=>$value,'by'=>$actor,'at'=>$now];
            if($value==='')unset($entry['fields'][$key]);else $entry['fields'][$key]=['value'=>$value,'by'=>$actor,'at'=>$now];
        }
        ++$entry['version'];$data[$body['id']]=$entry;
        return manualMerge($row,$data);
    },true);
    usageLog('record_save',['record_id'=>$body['id'],'fields'=>array_keys($body['fields'])]);
    manualResponse(200,['ok'=>true,'row'=>$result]);
} catch(InvalidArgumentException|JsonException $e){manualResponse(400,['ok'=>false,'error'=>$e->getMessage()]);}
catch(Throwable $e){error_log('[LR manual] '.$e->getMessage());manualResponse($e->getCode()===409?409:500,['ok'=>false,'error'=>$e->getCode()===409?$e->getMessage():'บันทึกไม่สำเร็จ กรุณาตรวจสอบพื้นที่จัดเก็บ']);}
