<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require dirname(__DIR__).'/mfa-store.php';
require dirname(__DIR__).'/export-report.php';
require dirname(__DIR__).'/report.php';
function verifyFeature(bool $ok,string $message): void {if(!$ok)throw new RuntimeException($message);echo 'OK '.$message.PHP_EOL;}
$secret=mfaBase32('12345678901234567890');
foreach([59=>'94287082',1111111109=>'07081804',1111111111=>'14050471',1234567890=>'89005924',2000000000=>'69279037',20000000000=>'65353130'] as $time=>$expected)verifyFeature(mfaTotp($secret,intdiv($time,30),8)===$expected,'RFC 6238 vector '.$time);
verifyFeature(mfaMatch($secret,'287082',-1,59)===1,'Six digit TOTP');
verifyFeature(mfaMatch($secret,'287082',1,59)===null,'Replayed TOTP rejected');
verifyFeature(mfaMatch($secret,'287082',-1,120)===null,'Expired TOTP rejected');
verifyFeature(mfaMatch($secret,' 287082',-1,59)===null,'Malformed TOTP rejected');
$codes=mfaRecoveryCodes();$entry=['secret'=>$secret,'last_counter'=>PHP_INT_MAX,'recovery'=>array_map('mfaRecoveryHash',$codes)];
verifyFeature(mfaVerifyEntry($entry,$codes[0]),'Recovery code accepted');
verifyFeature(!mfaVerifyEntry($entry,$codes[0])&&count($entry['recovery'])===9,'Recovery code consumed once');
$root=dirname(__DIR__);$dir=$root.'/.private/verify-mfa-'.bin2hex(random_bytes(6));
try{
    mfaStore(static function(&$data)use($secret){$data['test']=['secret'=>$secret];},true,$dir);
    verifyFeature(mfaStore(static fn($data)=>$data['test']['secret'],false,$dir)===$secret,'Encrypted store round trip');
    verifyFeature(!str_contains(file_get_contents($dir.'/accounts.php'),$secret),'TOTP secret absent from stored ciphertext');
    $raw=file_get_contents($dir.'/accounts.php');file_put_contents($dir.'/accounts.php',str_replace('ciphertext','broken',$raw));
    $failed=false;set_error_handler(static function(){throw new RuntimeException('Corrupt store');});
    try{mfaStore(static fn($data)=>$data,false,$dir);}catch(Throwable $e){$failed=true;}finally{restore_error_handler();}
    verifyFeature($failed,'Corrupt MFA storage fails closed');
}finally{foreach(['store.lock','accounts.php','key.php'] as $name)if(is_file($dir.'/'.$name))unlink($dir.'/'.$name);if(is_dir($dir))rmdir($dir);}
$fields=reportFields();$row=array_fill_keys(array_column($fields,'label'),null);
$issue=false;$decoded=reportDecodeText("A\x81B",'tis620',$issue);
verifyFeature($issue&&$decoded==='A\\x81B','Undefined TIS-620 bytes preserved visibly');
$linked=reportNormalizeLinked(['ผลตรวจ'=>"A\x81B"],'tis620');
verifyFeature(isset($linked['หมายเหตุการเข้ารหัส (ช่อง:ข้อมูลดิบ hex)']),'Malformed linked values retain raw hex');
verifyFeature(exportXml("A\x00B")==='A\\x00B','Non-XML control bytes preserved visibly');
$row=array_replace($row,['ชื่อ-สกุลมารดา'=>'ตัวอย่างทดสอบระบบ <>&','HN มารดา'=>'000001','AN มารดา'=>'00000002','ลำดับทารก'=>'1','วันที่ Admit'=>'2026-11-17','APGAR 5 นาที'=>0,'ชื่อสามี'=>'=HYPERLINK("https://invalid.example")','_record_id'=>'l1-f1','_manual'=>['G'=>['by'=>'fixture','at'=>'2026-11-17 10:00:00']],'_source'=>['G'=>'1'],'_child_ans'=>['00000003'],'_family_status'=>'ทดสอบ']);
$data=['main'=>[$row],'labs'=>[['AN มารดา'=>'00000002','ผลตรวจ'=>'<negative>']],'lab_results'=>[['AN มารดา'=>'00000002','ผลตรวจ'=>'บรรทัดแรก'."\n".'บรรทัดสอง']],'vaccines'=>[],'complications'=>[]];
$stored=['l1-f1'=>['fields'=>['G'=>['value'=>'2','by'=>'fixture','at'=>'2026-11-17 10:00:00']],'audit'=>[['field'=>'G','before'=>'1','after'=>'2','by'=>'fixture','at'=>'2026-11-17 10:00:00']]]];
$sheets=exportSheets($data,$stored,$fields,['all'=>true,'from'=>'2026-11-17','to'=>'2026-11-17']);
verifyFeature(count($sheets)===10,'Workbook includes ten sheets');
verifyFeature(count(array_diff(array_column($fields,'label'),$sheets[0]['headers']))===0,'Every report field included, including hidden fields');
verifyFeature(count($sheets[5]['rows'])===1&&count($sheets[6]['rows'])===1,'Supplement and audit included');
$csvSheet=['headers'=>['HN','ข้อความ','ศูนย์','ว่าง','สูตร','อาร์เรย์'],'rows'=>[['HN'=>'000001','ข้อความ'=>"ไทย,\"ทดสอบ\"\r\nบรรทัดสอง\\ท้าย",'ศูนย์'=>0,'ว่าง'=>null,'สูตร'=>'=1+1','อาร์เรย์'=>['ก','ข']]]];
$csvPath=exportCsvFile([$csvSheet]);
try{
    $stream=fopen($csvPath,'rb');
    verifyFeature(fread($stream,3)==="\xEF\xBB\xBF",'CSV UTF-8 BOM');
    verifyFeature(fgetcsv($stream,0,',','"','')===$csvSheet['headers'],'CSV headers round trip');
    $values=fgetcsv($stream,0,',','"','');fclose($stream);
    verifyFeature($values===['000001',$csvSheet['rows'][0]['ข้อความ'],'0','',"'=1+1",'["ก","ข"]'],'CSV preserves Thai, leading zeros, quotes, comma, CRLF, backslash, zero, null and arrays');
    foreach(['+cmd','-cmd','@SUM(A1)',"\ttext","\ntext",' =1'] as $value)verifyFeature(exportCsvCell($value)==="'".$value,'CSV formula guard');
}finally{unlink($csvPath);}
$path=exportWorkbook($sheets);
$destination=$root.'/output/playwright/mfa-export';if(!is_dir($destination))mkdir($destination,0700,true);
rename($path,$destination.'/synthetic.xlsx');
ob_start();
// exportPrint exits; write a standalone print fixture in a separate CLI invocation instead.
file_put_contents($destination.'/fixture.php','<?php require '.var_export($root.'/export-report.php',true).'; exportPrint('.var_export($data,true).','.var_export($fields,true).','.var_export($stored,true).');');
ob_end_clean();
verifyFeature(true,'Synthetic XLSX fixture generated (no patient data)');
