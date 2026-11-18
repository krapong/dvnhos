<?php
require_once __DIR__.'/security.php';
require_once __DIR__.'/usage-log.php';
$today=new DateTimeImmutable('today',new DateTimeZone('Asia/Bangkok'));
$earliest=usageRetentionStart($today);
usagePrune($today);
$day=$_GET['day']??$today->format('Y-m-d');
$date=is_string($day)?DateTimeImmutable::createFromFormat('!Y-m-d',$day,new DateTimeZone('Asia/Bangkok')):false;
if(!$date||$date->format('Y-m-d')!==$day){http_response_code(400);exit('Invalid date');}
if($date<$earliest||$date>$today){http_response_code(400);exit('เลือกวันที่ภายใน 60 วันล่าสุด รวมวันนี้');}
$admins=$cfg['audit_view_users']??[];
$all=is_array($admins)&&in_array($_SESSION['auth_user'],$admins,true);
$rows=[];$limit=500;$truncated=false;
$handle=@fopen(__DIR__.'/.private/usage-'.$day.'.php','rb');
if($handle){
    if(flock($handle,LOCK_SH)){
        fgets($handle);
        while(($line=fgets($handle))!==false){
            $entry=json_decode($line,true);
            if(!is_array($entry)||(!$all&&($entry['user']??'')!==$_SESSION['auth_user']))continue;
            $rows[]=$entry;
            if(count($rows)>$limit){array_shift($rows);$truncated=true;}
        }
        flock($handle,LOCK_UN);
    }
    fclose($handle);
}
usageLog('history_view',['day'=>$day,'scope'=>$all?'all':'self']);
$escape=static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
$labels=['password_verified'=>'รหัสผ่านผ่านการตรวจสอบ (รอ MFA)','mfa_success'=>'ยืนยัน MFA สำเร็จ','mfa_failed'=>'ยืนยัน MFA ไม่สำเร็จ','mfa_enrolled'=>'ตั้งค่า MFA สำเร็จ','patient_print'=>'จัดทำแบบพิมพ์รายบุคคล','xlsx_export'=>'จัดทำ Excel ทุกช่องข้อมูล','login_success'=>'เข้าสู่ระบบสำเร็จ','login_failed'=>'เข้าสู่ระบบไม่สำเร็จ','logout'=>'ออกจากระบบ','session_expired'=>'หมดอายุการใช้งาน','data_read'=>'โหลด / ค้นหาข้อมูล','data_error'=>'โหลดข้อมูลไม่สำเร็จ','record_open'=>'เปิดเวชระเบียน (จากเบราว์เซอร์)','csv_export'=>'ส่งออก CSV','record_save'=>'บันทึกข้อมูลเพิ่มเติม','history_view'=>'ดูประวัติการใช้งาน'];
?>
<!doctype html><html lang="th"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>ประวัติการใช้งาน LR</title>
<style>body{font:14px Tahoma,sans-serif;background:#f8fafc;color:#243447;margin:24px}main{max-width:1200px;margin:auto}a{color:#9d174d}form{display:flex;gap:12px;align-items:center;flex-wrap:wrap;margin:20px 0}input,button{font:inherit;padding:8px}table{width:100%;border-collapse:collapse;background:white}th,td{padding:12px;text-align:left;border-bottom:1px solid #e2e8f0}td{overflow-wrap:anywhere}small{color:#64748b}.tablewrap{overflow:auto}</style>
<main><a href="index.php">← กลับระบบ LR</a><h1>ประวัติการใช้งาน</h1><p><?=$all?'ประวัติผู้ใช้ทั้งหมด':'ประวัติของคุณ'?> · เวลาไทย · แสดงล่าสุดไม่เกิน 500 รายการต่อวัน</p>
<p>เก็บประวัติ 60 วันรวมวันนี้ตามเวลาไทย และลบประวัติที่เกินอายุอัตโนมัติเมื่อระบบทำงาน</p>
<form method="get"><label for="day">วันที่</label><input id="day" name="day" type="date" min="<?=$earliest->format('Y-m-d')?>" max="<?=$today->format('Y-m-d')?>" value="<?=$escape($day)?>" required><button>ค้นหา</button></form>
<?php if($truncated): ?><p>มีประวัติเกิน 500 รายการ แสดงเฉพาะรายการล่าสุด</p><?php endif; ?>
<div class="tablewrap"><table><thead><tr><th>เวลา</th><th>ผู้ใช้</th><th>กิจกรรม</th><th>IP</th><th>รายละเอียด</th></tr></thead><tbody>
<?php foreach(array_reverse($rows) as $row): ?><tr><td><?=$escape($row['time']??'')?></td><td><?=$escape($row['user']??'')?></td><td><?=$escape($labels[$row['event']??'']??($row['event']??''))?></td><td><?=$escape($row['ip']??'')?></td><td><?=$escape(json_encode($row['details']??[],JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE))?></td></tr><?php endforeach; ?>
<?php if(!$rows): ?><tr><td colspan="5">ไม่พบประวัติในวันที่เลือก</td></tr><?php endif; ?>
</tbody></table></div><p><small>บันทึกเริ่มตั้งแต่ติดตั้งรุ่นนี้ ไม่เก็บรหัสผ่านหรือข้อความค้นหา การเปิดเวชระเบียนและเริ่มส่งออกเป็นเหตุการณ์ที่เบราว์เซอร์รายงาน</small></p></main></html>
