<?php
// Independent section 12 endpoint; the existing OPD endpoint is untouched.
declare(strict_types=1);
ini_set('display_errors','0');
header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('Asia/Bangkok');
function reply12(array $body,int $code=200): void {http_response_code($code);echo json_encode($body,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);exit;}
$clinics=['014'=>'ฝ่ายส่งเสริมสุขภาพ PCU','019'=>'วางแผนครอบครัว FP','021'=>'ฝากครรภ์/ตรวจครรภ์ ANC','035'=>'งานอนามัยโรงเรียน','036'=>'คัดกรองมะเร็งเต้านมและปากมดลูกและลำไส้','037'=>'งานอาชีวอนามัยและสิ่งแวดล้อม','039'=>'คัดกรองผู้สูงอายุ','040'=>'คัดกรอง DM/HT 35 ปี','059'=>'คลินิกเด็กดี','060'=>'ฝากครรภ์ free schedule','080'=>'วางแผนครอบครัว free schedule','082'=>'ตรวจสุขภาพประจำปี'];
if(($_GET['action']??'')==='clinics')reply12(['status'=>'success','clinics'=>$clinics]);
$dep=(string)($_GET['department']??'014');$start=(string)($_GET['start_date']??date('Y-m-d'));$end=(string)($_GET['end_date']??$start);
$validDate=static function(string $v):bool{$d=DateTimeImmutable::createFromFormat('!Y-m-d',$v);return $d&&$d->format('Y-m-d')===$v;};
if(!isset($clinics[$dep])||!$validDate($start)||!$validDate($end)||$start>$end||(strtotime($end)-strtotime($start))/86400>366)reply12(['status'=>'error','message'=>'เลือกคลินิกและช่วงวันที่ให้ถูกต้อง (ไม่เกิน 366 วัน)'],400);
try{
$file=getenv('OPD_CONFIG_FILE')?:(DIRECTORY_SEPARATOR==='\\'?dirname(__DIR__,2).'/opd-command-center-private/config.php':dirname(__DIR__,2).'/opd_dashboard_config.php');
$real=realpath($file);$web=realpath($_SERVER['DOCUMENT_ROOT']??__DIR__);
if(!$real||($web&&strpos(strtolower($real),strtolower(rtrim($web,'/\\')).DIRECTORY_SEPARATOR)===0))throw new RuntimeException('config');
$c=require $real;$d=$c['database']??[];if(empty($d['host'])||empty($d['name'])||empty($d['user'])||$d['user']==='root')throw new RuntimeException('config');
$db=new PDO('mysql:host='.$d['host'].';port='.($d['port']??3306).';dbname='.$d['name'].';charset=utf8',$d['user'],$d['password']??'',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_TIMEOUT=>8]);
$db->exec('SET SESSION TRANSACTION READ ONLY');
$columns=static function(string $t)use($db):array{try{return $db->query("SHOW COLUMNS FROM `$t`")->fetchAll(PDO::FETCH_COLUMN);}catch(PDOException $e){return [];}};
$query=static function(string $sql,array $params)use($db):array{$q=$db->prepare($sql);$q->execute($params);return $q->fetchAll();};
$params=[':s'=>$start,':e'=>$end,':dep'=>$dep];$where='o.vstdate BETWEEN :s AND :e AND o.main_dep=:dep';
$summary=$query("SELECT COUNT(DISTINCT o.vn) visits,COUNT(DISTINCT o.hn) patients FROM ovst o WHERE $where",$params)[0];
$notes=['นับครั้งรับบริการตาม ovst.main_dep (รหัสห้องตรวจในรูป) และผู้รับบริการไม่ซ้ำตาม HN ภายในคลินิกที่เลือก'];
$proc=$columns('doctor_operation');$summary['procedures']=null;
if(in_array('vn',$proc,true))$summary['procedures']=(int)$query("SELECT COUNT(DISTINCT o.vn) n FROM ovst o WHERE $where AND EXISTS(SELECT 1 FROM doctor_operation x WHERE x.vn=o.vn)",$params)[0]['n'];else $notes[]='หัตถการ: ไม่มีตาราง doctor_operation.vn ที่รองรับ';
$items=$columns('opitemrece');$drugs=$columns('drugitems');$summary['medications']=null;
if(in_array('vn',$items,true)&&in_array('icode',$items,true)&&in_array('icode',$drugs,true))$summary['medications']=(int)$query("SELECT COUNT(DISTINCT o.vn) n FROM ovst o WHERE $where AND EXISTS(SELECT 1 FROM opitemrece i JOIN drugitems di ON di.icode=i.icode WHERE i.vn=o.vn".(in_array('qty',$items,true)?' AND i.qty>0':'').')',$params)[0]['n'];else $notes[]='ยา: ไม่พบโครงสร้าง opitemrece / drugitems ที่รองรับ';
$appointments=null;$ac=$columns('oapp');
if(count(array_intersect(['depcode','hn','nextdate','oapp_id'],$ac))===4){
$cancel=in_array('oapp_status_id',$ac,true)?'a.oapp_status_id=4':'0=1';
$active=in_array('oapp_status_id',$ac,true)?'(a.oapp_status_id IS NULL OR a.oapp_status_id<>4)':'1=1';
$came='EXISTS(SELECT 1 FROM ovst av WHERE av.hn=a.hn AND av.vstdate=a.nextdate AND av.main_dep=a.depcode)';$today=$db->quote(date('Y-m-d'));
$appointments=$query("SELECT COUNT(DISTINCT a.oapp_id) total,COUNT(DISTINCT a.hn) patients,COUNT(DISTINCT CASE WHEN $active AND $came THEN a.oapp_id END) came,COUNT(DISTINCT CASE WHEN $active AND NOT $came AND a.nextdate >= $today THEN a.oapp_id END) pending,COUNT(DISTINCT CASE WHEN $active AND NOT $came AND a.nextdate < $today THEN a.oapp_id END) missed,COUNT(DISTINCT CASE WHEN $cancel THEN a.oapp_id END) cancelled FROM oapp a WHERE a.nextdate BETWEEN :s AND :e AND a.depcode=:dep",$params)[0];
$notes[]='นัดหมาย: นับรายการนัดตามวันนัดและ oapp.depcode; มาตามนัดเมื่อพบ HN + วัน + ห้องตรวจตรงกัน ไม่ยืนยันเวลานัด; รอมา = วันนี้/อนาคต ยกเลิกแยกต่างหาก';
}else $notes[]='นัดหมาย: ไม่พบ oapp.depcode ที่ใช้ยืนยันห้องตรวจ ห้ามใช้ oapp.clinic แทนรหัสแผนก';
$daily=$query("SELECT o.vstdate date,COUNT(DISTINCT o.vn) visits,COUNT(DISTINCT o.hn) patients FROM ovst o WHERE $where GROUP BY o.vstdate ORDER BY o.vstdate",$params);
$hourly=$query("SELECT HOUR(o.vsttime) hour,COUNT(DISTINCT o.vn) visits FROM ovst o WHERE $where GROUP BY HOUR(o.vsttime) ORDER BY hour",$params);
// Use actual arrival records at examination service point 003.
$stages=[['label'=>'เปิด Visit → ซักประวัติ','avg'=>null,'samples'=>0],['label'=>'ซักประวัติ → เข้าห้องตรวจ','avg'=>null,'samples'=>0],['label'=>'เข้าห้องตรวจ → จ่ายยา','avg'=>null,'samples'=>0],['label'=>'เข้าห้องจ่ายยา → ใบเสร็จ','avg'=>null,'samples'=>0]];
$sc=$columns('service_time');$totals=array_fill(0,4,[]);$completeTotals=[];$timeline=[];$examVisits=0;
$at=static function($v,string $day):?int{if(!$v||strpos((string)$v,'0000-00-00')!==false)return null;$v=trim((string)$v);try{return (new DateTimeImmutable(preg_match('/^\d{1,2}:\d{2}(:\d{2})?$/',$v)?"$day $v":$v))->getTimestamp();}catch(Throwable $e){return null;}};
$qc=$columns('opd_dep_queue');$hasQueue=count(array_intersect(['vn','depcode','queue_datetime'],$qc))===3;
$examJoin=$hasQueue?" LEFT JOIN (SELECT vn,MIN(queue_datetime) exam FROM opd_dep_queue WHERE depcode='003' AND queue_datetime IS NOT NULL GROUP BY vn) eq ON eq.vn=o.vn":"";
$examExpr=$hasQueue?'eq.exam':'NULL';$sj=in_array('vn',$sc,true)?' LEFT JOIN service_time s ON s.vn=o.vn':'';
$fields=[];foreach(['service1'=>'screen','service5'=>'pharmacy','service7'=>'dispensed'] as $field=>$alias)$fields[]=in_array('vn',$sc,true)&&in_array($field,$sc,true)?"MIN(NULLIF(s.$field,'')) $alias":"NULL AS $alias";
$times=$query("SELECT o.vn,o.vstdate,o.vsttime,$examExpr exam,".implode(',',$fields)." FROM ovst o $sj $examJoin WHERE $where GROUP BY o.vn,o.vstdate,o.vsttime".($hasQueue?',eq.exam':''),$params);
$duration=static function(?int $a,?int $b):?float{return $a!==null&&$b!==null&&$b>=$a&&$b-$a<=86400?($b-$a)/60:null;};
foreach($times as $r){$t=['registered'=>$at($r['vsttime'],$r['vstdate']),'screen'=>$at($r['screen'],$r['vstdate']),'exam'=>$at($r['exam'],$r['vstdate']),'pharmacy'=>$at($r['pharmacy'],$r['vstdate']),'dispensed'=>$at($r['dispensed'],$r['vstdate'])];if($t['exam']!==null)$examVisits++;
foreach([['registered','screen'],['screen','exam'],['exam','dispensed']] as $i=>$pair){$m=$duration($t[$pair[0]],$t[$pair[1]]);if($m!==null)$totals[$i][]=$m;}$timeline[$r['vn']]=$t;}
$summary['exam_entries']=$hasQueue?$examVisits:null;
// Receipt duration uses pharmacy entry service5 only; never substitute drug dispensing service7.
foreach(['rcpt_print','rcp_print'] as $table){$rc=$columns($table);$vn=in_array('vn',$rc,true)?'vn':(in_array('visitno',$rc,true)?'visitno':null);$tc=null;$dc=null;foreach(['bill_time','rcp_time','rcpt_time','payment_time','print_time'] as $f)if(in_array($f,$rc,true)){$tc=$f;break;}foreach(['bill_date','rcp_date','rcpt_date','payment_date','print_date'] as $f)if(in_array($f,$rc,true)){$dc=$f;break;}
if($vn&&$tc&&in_array('service5',$sc,true)){$dateExpr=$dc?"r.`$dc`":'o.vstdate';$status=in_array('status',$rc,true)?" AND r.status='OK'":'';
$rows=$query("SELECT o.vn,o.vstdate,MIN(NULLIF(s.service5,'')) pharmacy,MIN(CONCAT($dateExpr,' ',r.`$tc`)) receipt FROM ovst o JOIN service_time s ON s.vn=o.vn JOIN `$table` r ON r.`$vn`=o.vn$status WHERE $where GROUP BY o.vn,o.vstdate",$params);
foreach($rows as $r){$a=$at($r['pharmacy'],$r['vstdate']);$b=$at($r['receipt'],$r['vstdate']);if($a!==null&&$b!==null&&$b>=$a&&$b-$a<=86400)$totals[3][]=($b-$a)/60;
}break;}}
// Total elapsed time depends only on registration and a valid receipt, not intermediate events.
foreach(['rcpt_print','rcp_print'] as $table){
$rc=$columns($table);$vn=in_array('vn',$rc,true)?'vn':(in_array('visitno',$rc,true)?'visitno':null);$tc=null;$dc=null;
foreach(['bill_time','rcp_time','rcpt_time','payment_time','print_time'] as $f)if(in_array($f,$rc,true)){$tc=$f;break;}
foreach(['bill_date','rcp_date','rcpt_date','payment_date','print_date'] as $f)if(in_array($f,$rc,true)){$dc=$f;break;}
if(!$vn||!$tc)continue;
$receiptDate=$dc?"COALESCE(NULLIF(NULLIF(r.`$dc`,''),'0000-00-00'),o.vstdate)":'o.vstdate';
$status=in_array('status',$rc,true)?" AND r.status='OK'":'';
$receiptRows=$query("SELECT o.vn,o.vstdate,o.vsttime,$receiptDate receipt_date,r.`$tc` receipt_time FROM ovst o JOIN `$table` r ON r.`$vn`=o.vn$status WHERE $where",$params);
$firstReceipt=[];
foreach($receiptRows as $r){$registered=$at($r['vsttime'],$r['vstdate']);$receipt=$at($r['receipt_time'],$r['receipt_date']);$m=$duration($registered,$receipt);if($m!==null&&(!isset($firstReceipt[$r['vn']])||$m<$firstReceipt[$r['vn']]))$firstReceipt[$r['vn']]=$m;}
$completeTotals=array_values($firstReceipt);break;
}
foreach($stages as $i=>&$stage){$stage['samples']=count($totals[$i]);$stage['avg']=$stage['samples']?round(array_sum($totals[$i])/$stage['samples'],1):null;}unset($stage);
$notes[]='เวลา: เปิด Visit = vsttime, ซักประวัติ = service1, เข้าห้องยา = service5; เฉลี่ยคู่เวลาจริงที่เรียงลำดับภายในวันเดียวและไม่เกิน 24 ชม. ไม่คาดเดาเวลาข้ามวัน';
$notes[]='เข้าห้องตรวจ = opd_dep_queue.queue_datetime รหัส 003 ห้องตรวจโรค ครั้งแรกของ VN; จ่ายยา = service7; ไม่ใช้เวลาจากจุดซักประวัติแทน';
$notes[]='เวลารอคอยรวม = เปิด Visit ovst.vstdate + vsttime ถึงใบเสร็จที่มีสถานะ OK ใบแรกหลังเปิด Visit นับหนึ่งครั้งต่อ VN เฉลี่ยเฉพาะคู่เวลาที่เรียงลำดับและไม่เกิน 24 ชั่วโมง ไม่ต้องมีเวลาจุดบริการระหว่างทางครบ';
reply12(['status'=>'success','data'=>['department'=>$dep,'name'=>$clinics[$dep],'start_date'=>$start,'end_date'=>$end,'summary'=>$summary,'appointments'=>$appointments,'daily'=>$daily,'hourly'=>$hourly,'stages'=>$stages,'total_avg'=>count($completeTotals)?round(array_sum($completeTotals)/count($completeTotals),1):null,'total_samples'=>count($completeTotals),'notes'=>$notes,'updated_at'=>date('Y-m-d H:i:s')]]);
}catch(Throwable $e){error_log('Clinic12: '.$e->getCode());reply12(['status'=>'error','message'=>'ส่วนคลินิกเชื่อมต่อข้อมูลไม่ได้ โปรดตรวจ config บัญชีอ่านข้อมูล และการเชื่อมต่อ HOSxP'],503);}
