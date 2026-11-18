<?php
require_once __DIR__.'/auth-bootstrap.php';
require_once __DIR__.'/hosxp-auth.php';
require_once __DIR__.'/login-throttle.php';
if(isset($_SESSION['auth_user'])){header('Location: index.php',true,302);exit;}
$error='';$username='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $username=is_string($_POST['username']??null)?trim($_POST['username']):'';
    $password=is_string($_POST['password']??null)?$_POST['password']:'';
    $token=$_POST['csrf']??'';
    if(!is_string($token)||!hash_equals($_SESSION['csrf'],$token)){$error='หน้าเข้าสู่ระบบหมดอายุ กรุณาลองอีกครั้ง';http_response_code(403);}
    elseif($username===''||$password===''||strlen($username)>250||strlen($password)>1024){$error='กรุณากรอกชื่อผู้ใช้และรหัสผ่านให้ครบถ้วน';http_response_code(400);}
    else{
        try{
            if(!loginThrottle($username)){$error='ลองเข้าสู่ระบบเกินจำนวนที่กำหนด กรุณารอ 5 นาที';http_response_code(429);header('Retry-After: 300');}
            elseif(authenticateHosxpNurse($cfg,$username,$password)){
                loginThrottle($username,true);clearAuthSession();
                $_SESSION['mfa_pending_user']=$username;$_SESSION['mfa_pending_at']=time();
                usageLog('password_verified',[],$username);
                header('Location: mfa.php',true,303);exit;
            }else{$error='ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง หรือบัญชีไม่มีสิทธิ์ใช้งาน';http_response_code(401);}
        }catch(Throwable $e){error_log('[LR login] unavailable; code '.$e->getCode());$error='ไม่สามารถตรวจสอบบัญชี HOSxP ได้ กรุณาลองใหม่';http_response_code(503);}
    }
    usageLog('login_failed',['status'=>http_response_code()],substr($username,0,250));
    unset($password);
}
header('Content-Type: text/html; charset=UTF-8');
$escape=static fn($v)=>htmlspecialchars($v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
?>
<!doctype html><html lang="th"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>เข้าสู่ระบบ | LR EMR</title><style>
*{box-sizing:border-box}body{margin:0;font:15px 'Segoe UI',Tahoma,sans-serif;color:#26334b;background:#fff;min-height:100vh;display:grid;place-items:center;padding:24px}.login-shell{width:min(920px,100%);display:grid;grid-template-columns:1fr 1fr;border:1px solid #e7edf4;border-radius:24px;overflow:hidden;box-shadow:0 15px 70px #4755690c}.intro{background:linear-gradient(145deg,#fff2f8,#f5f3ff);padding:55px 40px;display:flex;flex-direction:column;justify-content:center}.mark{display:grid;place-items:center;width:62px;height:62px;color:white;font-size:33px;border-radius:18px;background:linear-gradient(135deg,#ec4899,#8b5cf6);margin-bottom:27px}.eyebrow{font-size:11px;letter-spacing:2px;color:#a21caf}.intro h1{font-size:31px;line-height:1.4;margin:14px 0}.intro p{line-height:1.9;color:#64748b;font-size:14px}.tags{display:flex;gap:8px;margin-top:20px}.tags span{border-radius:8px;padding:8px 12px;font-size:12px;background:#fff;color:#9d174d}.form{padding:55px 40px}.form h2{font-size:24px;margin:0 0 9px}.sub{color:#64748b;font-size:13px;line-height:1.8;margin-bottom:30px}label{display:block;font-size:13px;margin:20px 0 8px}input{font:inherit;border:1px solid #dce4ed;border-radius:10px;width:100%;padding:13px;color:#26334b}input:focus-visible,button:focus-visible{outline:2px solid #be185d;outline-offset:3px}.password-wrap{position:relative}.password-wrap input{padding-right:65px}.show{position:absolute;right:8px;top:9px;border:0;background:#f8fafc;padding:7px;border-radius:6px;color:#64748b;cursor:pointer}.submit{font:inherit;border:0;border-radius:10px;background:#be185d;color:#fff;width:100%;padding:14px;margin-top:25px;cursor:pointer}.error{background:#fff1f2;border:1px solid #fecdd3;border-radius:9px;padding:12px;color:#9f1239;line-height:1.7;font-size:13px}.foot{font-size:11px;color:#64748b;line-height:1.8;margin-top:23px}@media(max-width:650px){.login-shell{grid-template-columns:1fr}.intro{padding:25px}.intro h1{font-size:23px}.intro p,.tags{display:none}.mark{margin-bottom:15px;width:45px;height:45px;font-size:25px}.form{padding:30px 25px}}
</style></head><body><main class="login-shell"><section class="intro"><div class="mark" aria-hidden="true">✚</div><span class="eyebrow">MATERNAL & CHILD CARE</span><h1>LR EMR<br>ดูแลแม่และเด็ก<br>ในทุกช่วงสำคัญ</h1><p>เวชระเบียนห้องคลอด<br>เชื่อมข้อมูลจาก HOSxP</p><div class="tags"><span>มารดา</span><span>ทารก</span><span>ห้องคลอด</span></div></section><section class="form"><h2>เข้าสู่ระบบ</h2><div class="sub">ใช้ชื่อผู้ใช้และรหัสผ่าน HOSxP<br>สำหรับเจ้าหน้าที่ที่ได้รับสิทธิ์</div><?php if($error!==''): ?><div class="error" role="alert"><?php echo $escape($error); ?></div><?php endif; ?><form method="post" action="login.php"><input type="hidden" name="csrf" value="<?php echo $escape($_SESSION['csrf']); ?>"><label for="username">ชื่อผู้ใช้ HOSxP</label><input id="username" name="username" autocomplete="username" maxlength="250" value="<?php echo $escape($username); ?>" required autofocus><label for="password">รหัสผ่าน</label><div class="password-wrap"><input id="password" type="password" name="password" autocomplete="current-password" maxlength="1024" required><button class="show" type="button" aria-label="แสดงรหัสผ่าน" aria-pressed="false" onclick="const p=document.getElementById('password');const show=p.type==='password';p.type=show?'text':'password';this.textContent=show?'ซ่อน':'แสดง';this.setAttribute('aria-pressed',String(show));this.setAttribute('aria-label',show?'ซ่อนรหัสผ่าน':'แสดงรหัสผ่าน')">แสดง</button></div><button class="submit" type="submit">เข้าสู่ระบบ →</button></form><div class="foot">ใช้การตรวจรหัสผ่านเว็บ HOSxP (passweb) ตามระบบเดิม<br>ออกจากระบบเมื่อใช้งานเสร็จ · หมดอายุเมื่อไม่ใช้งาน 30 นาที</div></section></main></body></html>
