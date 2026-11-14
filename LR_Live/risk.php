<?php
require_once __DIR__.'/security.php';require_once __DIR__.'/report.php';require_once __DIR__.'/infant-risk.php';
header('Content-Type: application/json; charset=UTF-8');
function riskResponse(int $status,array $data): never {http_response_code($status);echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE|JSON_THROW_ON_ERROR);exit;}
$from=$_GET['from']??'2025-01-01';$to=$_GET['to']??'2025-12-31';
foreach([$from,$to] as $v){if(!is_string($v)||!preg_match('/\A[0-9]{4}-[0-9]{2}-[0-9]{2}\z/',$v))riskResponse(400,['ok'=>false,'error'=>'วันที่ไม่ถูกต้อง']);$d=DateTimeImmutable::createFromFormat('!Y-m-d',$v);if(!$d||$d->format('Y-m-d')!==$v)riskResponse(400,['ok'=>false,'error'=>'วันที่ไม่ถูกต้อง']);}
$days=(new DateTimeImmutable($from))->diff(new DateTimeImmutable($to));if($to<$from||$days->days>370)riskResponse(400,['ok'=>false,'error'=>'เลือกช่วงวันเกิดไม่เกิน 370 วัน']);
try{
 $p=reportConnection($cfg);$p->exec('SET SESSION TRANSACTION READ ONLY');$p->beginTransaction();$params=[':start'=>$from,':end'=>(new DateTimeImmutable($to))->modify('+1 day')->format('Y-m-d')];
 $s=$p->prepare(reportSql('f.birth_date>=:start AND f.birth_date<:end AND '.infantRiskPredicate()));$s->execute($params);$primary=[];
 foreach($s->fetchAll() as $r){$r=reportNormalize($r,$cfg['db_charset']);$r['_risk']=infantRiskReasons($r['น้ำหนักทารก กรัม'],[1=>$r['APGAR 1 นาที'],5=>$r['APGAR 5 นาที'],10=>$r['APGAR 10 นาที']]);$primary[]=$r;}
 usort($primary,static fn($a,$b)=>[reportMissing($a['APGAR 5 นาที']),$a['APGAR 5 นาที'],$a['น้ำหนักทารก กรัม'],-strtotime($a['วันที่คลอด'])]<=>[reportMissing($b['APGAR 5 นาที']),$b['APGAR 5 นาที'],$b['น้ำหนักทารก กรัม'],-strtotime($b['วันที่คลอด'])]);
 $s=$p->prepare(newbornRiskSql());$s->execute($params);$newborns=[];foreach($s->fetchAll() as $r){$r=reportNormalizeLinked($r,$cfg['db_charset']);$r['_risk']=infantRiskReasons($r['birth_weight'],[1=>$r['apgar1'],5=>$r['apgar2']]);$newborns[]=$r;}
 if(count($primary)>10000||count($newborns)>10000)riskResponse(422,['ok'=>false,'error'=>'เกิน 10,000 รายการ กรุณาลดช่วงวันที่']);
 $ids=array_column($primary,'_record_id');if(count($ids)!==count(array_unique($ids)))throw new RuntimeException('Duplicate infant risk records');
 $s=$p->prepare(infantRiskMonthlySql());$s->execute($params);$monthly=$s->fetchAll();$p->commit();
 usageLog('data_read',['scope'=>'infant_risk','from'=>$from,'to'=>$to,'count'=>count($primary)+count($newborns)]);
 riskResponse(200,['ok'=>true,'primary'=>$primary,'newborns'=>$newborns,'monthly'=>$monthly,'period'=>['from'=>$from,'to'=>$to,'basis'=>'birth_date']]);
}catch(Throwable $e){if(isset($p)&&$p->inTransaction())$p->rollBack();error_log('[LR infant risk] code '.$e->getCode());riskResponse(500,['ok'=>false,'error'=>'อ่านข้อมูลคัดกรองไม่สำเร็จ กรุณาตรวจสอบการเชื่อมต่อ']);}
