<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$root=dirname(__DIR__);$failures=0;
function checkServer(bool $ok,string $label): void {global $failures;echo ($ok?'OK   ':'FAIL ').$label.PHP_EOL;if(!$ok)++$failures;}
function supplementDiagnostic(Throwable $e): void {
 $driver=$e instanceof PDOException?($e->errorInfo[1]??null):null;
 $hints=[1045=>'Check supplementary username/password and allowed client host.',1044=>'Grant the app access to the supplementary database.',1049=>'Create the supplementary database and import database/supplement.sql.',1146=>'Import database/supplement.sql into the supplementary database.',1142=>'Check supplementary table privileges using database/grants-example.sql.',2002=>'Check supplementary host/port, MySQL service, firewall and SELinux.',2003=>'Check supplementary host/port, MySQL service, firewall and SELinux.'];
 echo 'INFO Supplementary SQLSTATE: '.$e->getCode().'; driver code: '.($driver??'n/a').PHP_EOL;
 echo 'INFO '.($hints[$driver]??'Check supplement.config.php and PHP-FPM error log; do not copy local test credentials.').PHP_EOL;
}
checkServer(PHP_VERSION_ID>=80400,'PHP 8.4+');
foreach(['pdo_mysql','iconv','session','json','openssl'] as $ext)checkServer(extension_loaded($ext),'PHP extension '.$ext);
checkServer(is_dir($root.'/.private')&&is_writable($root.'/.private'),'.private is writable for the current CLI user');
require_once $root.'/mfa-store.php';
try{mfaStore(static fn($data)=>true);checkServer(true,'MFA storage and encryption key are readable');}catch(Throwable $e){checkServer(false,'MFA storage unavailable; verify OpenSSL, private directory permissions and matching key backup');}
if(!is_file($root.'/config.php')||!is_file($root.'/supplement.config.php')){checkServer(false,'Create config.php and supplement.config.php');exit(1);}
require $root.'/report.php';require $root.'/manual-store.php';require $root.'/providers.php';
try{
 $cfg=require $root.'/config.php';$p=reportConnection($cfg);
 foreach(['ipt','ipt_labour','ipt_labour_infant','ipt_newborn','patient','dchstts','ward','pttype','spclty','ipt_labour_anc_type','ipt_labour_place','membrane_type','membrane_explode_type','sex','infant_delivery_type','infant_indication_type','infant_adaptor_type','ipt_newborn_condition_type1','ipt_newborn_condition_type2','newborn_death_cuase','nationality','labor','doctor','doctor_position','person','person_anc','person_anc_risk','anc_risk','referout','hospcode','ipt_labour_lab','labour_lab','lab_head','lab_order','lab_items','ipt_labour_complication','ipt_labour_vaccine'] as $table){$p->query('SELECT 1 FROM `'.$table.'` LIMIT 1');}
 $p->query('SELECT loginname,passweb,groupname,account_disable,doctorcode FROM opduser LIMIT 0');
 $p->query('SELECT an,ga,ga_day,anc_place_name FROM ipt_pregnancy LIMIT 0');
 checkServer(true,'HOSxP connection and report SELECT permissions');
 $date=$p->query('SELECT MAX(regdate) FROM ipt')->fetchColumn();echo 'INFO Latest Admit date in configured HOSxP database: '.$date.PHP_EOL;
 checkServer(count(deliveryProviders($p,$cfg['db_charset']))>0,'Active doctor/nurse lookup');
}catch(Throwable $e){checkServer(false,'HOSxP connection/schema/permissions; code '.$e->getCode());}
try{$db=supplementConnection();foreach(['supplement_records','supplement_fields','supplement_audit','supplement_meta'] as $t)$db->query('SELECT 1 FROM '.$t.' LIMIT 1');checkServer($db->query("SELECT meta_value FROM supplement_meta WHERE meta_key='write_lock'")->fetchColumn()!==false,'Separate supplementary database and schema');echo 'INFO Supplementary record count: '.$db->query('SELECT COUNT(*) FROM supplement_records')->fetchColumn().PHP_EOL;}catch(Throwable $e){checkServer(false,'Supplementary database/schema; code '.$e->getCode());supplementDiagnostic($e);}
try{$fields=reportFields();$sex=array_values(array_filter($fields,static fn($f)=>$f['label']==='เพศทารก'))[0]??[];checkServer(($sex['editable']??false)===true&&($sex['options']??[])===['ชาย','หญิง','ระบุไม่ได้'],'Infant sex dropdown and edit permission metadata');}catch(Throwable $e){checkServer(false,'Field catalog/options files are missing or inconsistent');}
echo 'INFO Also verify permissions as the Apache/PHP-FPM user, HTTPS and a real HOSxP login.'.PHP_EOL;
exit($failures?1:0);
