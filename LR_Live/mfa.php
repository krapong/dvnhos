<?php
require_once __DIR__.'/auth-bootstrap.php';
require_once __DIR__.'/hosxp-auth.php';
require_once __DIR__.'/login-throttle.php';
require_once __DIR__.'/mfa-store.php';
if(isset($_SESSION['auth_user'],$_SESSION['mfa_verified_at'])){header('Location: index.php',true,303);exit;}
if(!isset($_SESSION['mfa_pending_user'])||time()-(int)($_SESSION['mfa_pending_at']??0)>300){clearAuthSession();header('Location: login.php',true,303);exit;}
$user=$_SESSION['mfa_pending_user'];$key=mfaUserKey($user);$error='';$ready=false;$entry=null;
if($_SERVER['REQUEST_METHOD']==='POST'&&($_POST['action']??'')==='cancel'&&is_string($_POST['csrf']??null)&&hash_equals($_SESSION['csrf'],$_POST['csrf'])){clearAuthSession();header('Location: login.php',true,303);exit;}
// Keep diagnostics free of OTPs, shared secrets, passwords and recovery codes.
$mfaStage='storage_read';
try {
    $entry=mfaStore(static fn($data)=>$data[$key]??null);$ready=true;
    if(!$entry&&!isset($_SESSION['mfa_setup_secret']))$_SESSION['mfa_setup_secret']=mfaBase32(random_bytes(20));
    if($_SERVER['REQUEST_METHOD']==='POST'){
        $mfaStage='verification';
        $csrf=$_POST['csrf']??'';$code=$_POST['code']??'';
        if(!is_string($csrf)||!hash_equals($_SESSION['csrf'],$csrf)){http_response_code(403);$error='หน้าเว็บหมดอายุ กรุณาลองใหม่';}
        elseif(($_POST['action']??'')==='cancel'){clearAuthSession();header('Location: login.php',true,303);exit;}
        elseif(!is_string($code)||strlen($code)>40){http_response_code(400);$error='รูปแบบรหัสไม่ถูกต้อง';}
        elseif(!loginThrottle('mfa:'.$user)){http_response_code(429);header('Retry-After: 300');$error='ลองรหัสเกินจำนวนที่กำหนด กรุณารอ 5 นาที';}
        elseif(!hosxpSessionAllowed($cfg,$user)){clearAuthSession();header('Location: login.php',true,303);exit;}
        else{
            $codes=[];$secret=$_SESSION['mfa_setup_secret']??'';
            $blocked=false;$credentialId='';
            $mfaStage='storage_verify';
            $valid=mfaStore(function(&$data)use($key,$code,$secret,&$codes,&$blocked,&$credentialId){
                $attemptKey='attempts:'.$key;
                $attempts=array_values(array_filter($data[$attemptKey]??[],static fn($time)=>$time>time()-300));
                if(count($attempts)>=5){$blocked=true;return false;}
                $attempts[]=time();$data[$attemptKey]=$attempts;
                if(isset($data[$key])){
                    $valid=mfaVerifyEntry($data[$key],trim($code));
                    if($valid){unset($data[$attemptKey]);$credentialId=$data[$key]['credential_id'];}return $valid;
                }
                $counter=$secret!==''?mfaMatch($secret,trim($code)):null;if($counter===null)return false;
                $codes=mfaRecoveryCodes();$credentialId=bin2hex(random_bytes(16));$data[$key]=['secret'=>$secret,'credential_id'=>$credentialId,'last_counter'=>$counter,'recovery'=>array_map('mfaRecoveryHash',$codes),'created_at'=>gmdate('c')];unset($data[$attemptKey]);return true;
            },true);
            if($valid){
                $mfaStage='session_complete';loginThrottle('mfa:'.$user,true);clearAuthSession();
                $_SESSION['auth_user']=$user;$_SESSION['authenticated_at']=time();$_SESSION['last_activity']=time();$_SESSION['account_checked_at']=time();$_SESSION['mfa_verified_at']=time();
                $_SESSION['mfa_credential_id']=$credentialId;
                usageLog('mfa_success');usageLog('login_success');
                if(!$codes){header('Location: index.php',true,303);exit;}
                usageLog('mfa_enrolled');
            }else{http_response_code($blocked?429:401);$error=$blocked?'ลองรหัสเกินจำนวนที่กำหนด กรุณารอ 5 นาที':'รหัสไม่ถูกต้อง ถูกใช้แล้ว หรือหมดอายุ กรุณาใช้รหัสใหม่';usageLog('mfa_failed',[],$user);}
        }
    }
}catch(Throwable $e){
    http_response_code(503);$ready=false;$reference=bin2hex(random_bytes(4));
    $reason='ตรวจสอบข้อมูล MFA ไม่สำเร็จ กรุณาลองเข้าสู่ระบบใหม่';
    $diagnostic='unavailable';
    if(!extension_loaded('openssl')){$reason='เซิร์ฟเวอร์ยังไม่ได้เปิด OpenSSL สำหรับ MFA กรุณาให้ผู้ดูแลเปิดผ่าน tools/start-local.ps1';$diagnostic='openssl_missing';}
    elseif($e instanceof PDOException){$reason='เชื่อมต่อ HOSxP เพื่อตรวจสิทธิ์ไม่สำเร็จ กรุณาลองใหม่';$diagnostic='account_unavailable';}
    elseif(in_array($e->getMessage(),['MFA encryption key missing; restore from backup','MFA key invalid','MFA decryption failed','MFA storage invalid'],true)){$reason='อ่านไฟล์ MFA ไม่สำเร็จ กรุณาให้ผู้ดูแลตรวจไฟล์และคีย์สำรอง';$diagnostic='storage_integrity';}
    elseif(in_array($mfaStage,['storage_read','storage_verify','verification','session_complete'],true)){$reason='อ่านหรือบันทึก MFA ไม่สำเร็จ กรุณาให้ผู้ดูแลตรวจสิทธิ์โฟลเดอร์ .private';$diagnostic='storage_access';}
    $error=$reason.' (รหัสอ้างอิง '.$reference.')';
    error_log('[LR MFA '.$reference.'] stage='.$mfaStage.' reason='.$diagnostic.' type='.get_class($e).' code='.$e->getCode());
}
$escape=static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
header('Content-Type: text/html; charset=UTF-8');
?>
<!doctype html><html lang="th"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>ยืนยันตัวตน MFA | LR EMR</title>
<style>body{font:15px Tahoma,sans-serif;color:#26334b;background:#f9f4fa;margin:0;padding:30px}main{max-width:580px;margin:5vh auto;background:white;border:1px solid #e8dfe9;border-radius:20px;padding:32px;box-shadow:0 15px 60px #80507410}h1{font-size:25px}p,li{line-height:1.9;color:#526274}input,button{font:inherit;border:1px solid #dbcddd;border-radius:9px;padding:13px}input{width:100%;box-sizing:border-box;margin:12px 0}button,.button{background:#a53575;color:white;cursor:pointer;border:0;text-decoration:none;display:inline-block;padding:13px;border-radius:9px}.secondary{background:#f2eaf2;color:#644962}code{display:block;overflow-wrap:anywhere;padding:14px;background:#f8f2f8;letter-spacing:2px;user-select:all}.error{color:#a11f3b;background:#fff0f2;padding:12px}.codes{display:grid;grid-template-columns:1fr 1fr;gap:8px}.codes code{font-size:13px;letter-spacing:1px}@media print{button,.button{display:none}main{margin:0;border:0}}</style>
<main><?php if(!empty($codes)): ?>
<h1>ตั้งค่า MFA สำเร็จ</h1><p>เก็บรหัสกู้คืนไว้ในที่ปลอดภัย รหัสแต่ละชุดใช้ได้ครั้งเดียวเมื่อโทรศัพท์สูญหาย หน้านี้แสดงเพียงครั้งเดียว อย่าเก็บไว้ร่วมกับรหัสผ่าน</p>
<div class="codes"><?php foreach($codes as $recovery): ?><code><?=$escape($recovery)?></code><?php endforeach; ?></div><p><button onclick="window.print()">พิมพ์รหัสกู้คืน</button> <a class="button" href="index.php">เก็บรหัสแล้ว · เข้าระบบ</a></p>
<?php else: ?>
<h1><?=$entry?'ยืนยันตัวตน MFA':'ตั้งค่า MFA ครั้งแรก'?></h1><p>บัญชี <?=$escape($user)?> · ยืนยันภายใน 5 นาทีหลังตรวจรหัสผ่าน</p>
<?php if($error): ?><p class="error" role="alert"><?=$escape($error)?></p><?php endif; ?>
<?php if($ready&&!$entry): ?><ol><li>เปิด Google Authenticator, Microsoft Authenticator หรือแอป TOTP ที่รองรับ</li><li>กดเพิ่มบัญชี แล้วเลือกสแกน QR Code ด้านล่าง</li></ol><div id="mfa-qr" role="img" aria-label="QR Code สำหรับตั้งค่า Authenticator" style="max-width:320px;margin:20px auto;background:white"></div><p id="mfa-qr-status" style="text-align:center">กำลังสร้าง QR Code…</p><noscript><p>เปิด JavaScript เพื่อแสดง QR Code หรือป้อนคีย์ด้านล่างแทน</p></noscript><details><summary>สแกนไม่ได้? ป้อนคีย์ด้วยตนเอง</summary><p>ตั้งชื่อ LR EMR: <?=$escape($user)?> และเลือกชนิดตามเวลา</p><code><?=$escape($_SESSION['mfa_setup_secret'])?></code></details>
<script><?php readfile(__DIR__.'/assets/vendor/qrcode.js'); ?></script>
<script>
(()=>{
 const status=document.getElementById('mfa-qr-status');
 try{
  const uri=<?php echo json_encode('otpauth://totp/'.rawurlencode('LR EMR').':'.rawurlencode($user).'?'.http_build_query(['secret'=>$_SESSION['mfa_setup_secret'],'issuer'=>'LR EMR','algorithm'=>'SHA1','digits'=>6,'period'=>30],'','&',PHP_QUERY_RFC3986),JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_THROW_ON_ERROR); ?>;
  const qr=qrcode(0,'M');qr.addData(uri,'Byte');qr.make();
  document.getElementById('mfa-qr').innerHTML=qr.createSvgTag({cellSize:4,margin:16,scalable:true});
  status.textContent='สแกนด้วย Authenticator แล้วกรอกรหัส 6 หลักด้านล่าง';
 }catch(error){status.textContent='แสดง QR Code ไม่สำเร็จ กรุณาป้อนคีย์ด้วยตนเองด้านล่าง'}
})();
</script><p>รหัส 6 หลัก เปลี่ยนทุก 30 วินาที เปิดเวลาของโทรศัพท์เป็นอัตโนมัติ แล้วป้อนรหัสเพื่อยืนยันการตั้งค่า</p>
<?php elseif($ready): ?><p>ป้อนรหัส 6 หลักจาก Authenticator หรือรหัสกู้คืนที่ยังไม่เคยใช้</p><?php endif; ?>
<form method="post"><input type="hidden" name="csrf" value="<?=$escape($_SESSION['csrf'])?>"><?php if($ready): ?><label for="code">รหัสยืนยัน</label><input id="code" name="code" autocomplete="one-time-code" maxlength="40" required <?=$entry?'autofocus':''?>><button name="action" value="verify">ยืนยันตัวตน</button><?php endif; ?> <button class="secondary" name="action" value="cancel" formnovalidate>กลับหน้าเข้าสู่ระบบ</button></form>
<?php endif; ?></main></html>
