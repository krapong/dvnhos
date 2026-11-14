<?php
declare(strict_types=1);
require_once __DIR__.'/database.php';
require_once __DIR__.'/thai-date.php';
const NCC_UNITS = ['opd','lr','ht','dm','ckd','warfarin','asthma','ipd_male','ipd_female'];
const NCC_ROLES = ['admin'=>'ผู้ดูแลระบบ','executive'=>'ผู้บริหาร','director'=>'หัวหน้ากลุ่มงาน','head'=>'หัวหน้าหน่วย','staff'=>'ผู้บันทึก'];
function h($v): string { return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8'); }
function base_path(): string {
    $configured=getenv('NCC_BASE_PATH');
    if($configured!==false)return rtrim($configured,'/');
    if(PHP_SAPI==='cli-server')return '';
    return rtrim(str_replace('\\','/',dirname($_SERVER['SCRIPT_NAME']??'/router.php')),'/.');
}
function route_url(string $url): string {
    [$path,$query]=array_pad(explode('?',$url,2),2,'');
    $base=base_path();
    if($base!==''&&str_starts_with($path,$base.'/'))$path=substr($path,strlen($base));
    return $base.'/router.php?route='.rawurlencode($path).($query!==''?'&'.$query:'');
}
function route_links(string $html): string {
    return preg_replace_callback('/(href|action)="(\/[^\"]*)"/',fn($m)=>$m[1].'="'.h(route_url(html_entity_decode($m[2],ENT_QUOTES,'UTF-8'))).'"',$html);
}
function go(string $url): never { header('Location: '.route_url($url)); exit; }
function current_user(): ?array {
    $s=db()->prepare('SELECT * FROM users WHERE id=? AND active=1'); $s->execute([$_SESSION['uid']??0]);
    return $s->fetch(PDO::FETCH_ASSOC) ?: null;
}
function allowed(array $u,string $page): bool {
    if (in_array($page,NCC_UNITS,true)) return $u['unit']==='all'||$u['unit']===$page;
    if ($page==='users') return $u['role']==='admin';
    if ($page==='settings') return $u['role']==='admin';
    if ($page==='approvals') return in_array($u['role'],['admin','director','head'],true);
    if ($page==='entry') return in_array($u['role'],['admin','head','staff'],true);
    return in_array($page,['overview','risks','workforce','reports'],true);
}
function screen(string $title,string $body,bool $appLayout=false): never {
    header('Content-Type: text/html; charset=utf-8');
    $account='';$viewer=current_user();
    if($viewer)$account='<div class="account-bar"><span>'.h($viewer['name']).' · '.h(NCC_ROLES[$viewer['role']]).'</span><a class="btn" href="'.h(base_path()).'/">หน้าหลัก</a><a class="btn" href="'.h(base_path()).'/password">เปลี่ยนรหัสผ่าน</a><form method="post" action="'.h(base_path()).'/logout">'.csrf().'<button class="btn logout-button">ออกจากระบบ</button></form></div>';
    if($appLayout){
        echo '<!doctype html><html lang="th"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.h($title).'</title><link rel="stylesheet" href="'.h(base_path()).'/style.css"><script src="'.h(base_path()).'/thai-date.js"></script></head><body><div class="app">'.route_links($account).route_links($body).'</div></body></html>';exit;
    }
    $body='<div class="screen-brand"><img class="hospital-logo" src="'.h(base_path()).'/hospital-logo.jpg" alt="ตราโรงพยาบาล"><strong>โรงพยาบาลเทพรัตนเวชชานุกูล<br>เฉลิมพระเกียรติ 60 พรรษา</strong></div>'.$body;
    $body=str_replace('href="/','href="'.h(base_path()).'/',$body);
    $body=route_links($body);$account=route_links($account);
    echo '<!doctype html><html lang="th"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.h($title).'</title><link rel="stylesheet" href="'.h(base_path()).'/style.css"><style>body{background:#eef4fa}.auth{max-width:950px;margin:40px auto;padding:28px;background:white;border-radius:16px;box-shadow:0 6px 30px #16365720}.auth input,.auth select{padding:10px;border:1px solid #bdcbdc;border-radius:6px;max-width:100%}.auth form{display:flex;flex-wrap:wrap;gap:12px;align-items:end;margin:20px 0}.auth label{display:grid;gap:6px}.auth table{width:100%;border-collapse:collapse}.auth td,.auth th{padding:10px;text-align:left;border-bottom:1px solid #ddd}.auth a{color:#0966bd}.message{color:#a22920}.auth button{cursor:pointer}</style>'.$account.'<main class="auth"><h1>'.h($title).'</h1>'.$body.'</main></html>'; exit;
}
function csrf(): string { return '<input type="hidden" name="csrf" value="'.h($_SESSION['csrf']).'">'; }
function ncc_route(): void {
    session_name('ncc_session');
    session_start(['cookie_httponly'=>true,'cookie_samesite'=>'Lax','use_strict_mode'=>true,'cookie_secure'=>!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off']);
    header('Cache-Control: no-store'); header('X-Content-Type-Options: nosniff'); header('X-Frame-Options: DENY');
    $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
    if (isset($_SESSION['last']) && time()-$_SESSION['last']>1800) { unset($_SESSION['uid']); }
    $_SESSION['last']=time();
    $path=rawurldecode(parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH)??'/');
    $base=base_path();
    if($base!==''&&str_starts_with($path,$base.'/'))$path=substr($path,strlen($base));
    if($path==='/router.php'&&isset($_GET['route'])) {
        $path=(string)$_GET['route'];
        if(!preg_match('~^/[a-zA-Z0-9/_.-]*$~D',$path)){http_response_code(400);echo 'Invalid route';return;}
    }
    if ($path==='/hospital-logo.jpg' && $_SERVER['REQUEST_METHOD']==='GET') { header('Content-Type: image/jpeg');readfile(__DIR__.'/hospital-logo.jpg');return; }
    if ($path==='/style.css' && $_SERVER['REQUEST_METHOD']==='GET') { header('Content-Type: text/css; charset=utf-8');readfile(__DIR__.'/style.css');return; }
    if ($_SERVER['REQUEST_METHOD']==='POST' && !hash_equals($_SESSION['csrf'],(string)($_POST['csrf']??''))) { http_response_code(403); screen('คำขอไม่ถูกต้อง','กรุณาโหลดหน้าใหม่'); }
    $user=current_user(); $message='';
    if ($path==='/logout' && $_SERVER['REQUEST_METHOD']==='POST') { $_SESSION=[]; session_destroy(); go('/login'); }
    if ($path==='/login') {
        if ($user) go('/');
        if ($_SERVER['REQUEST_METHOD']==='POST') {
            $ip=$_SERVER['REMOTE_ADDR']??'local'; $q=db()->prepare('SELECT * FROM attempts WHERE ip=?');$q->execute([$ip]);$attempt=$q->fetch(PDO::FETCH_ASSOC);
            if ($attempt && $attempt['failures']>=10 && time()-(int)$attempt['updated']<900) $message='ลองเข้าสู่ระบบหลายครั้ง กรุณารอ 15 นาที';
            else {
                $q=db()->prepare('SELECT * FROM users WHERE username=? AND active=1');$q->execute([trim($_POST['username']??'')]);$candidate=$q->fetch(PDO::FETCH_ASSOC);
                $valid=password_verify((string)($_POST['password']??''),$candidate['password']??'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.');
                if ($candidate && $valid) { db()->prepare('DELETE FROM attempts WHERE ip=?')->execute([$ip]);session_regenerate_id(true);$_SESSION['uid']=$candidate['id'];$_SESSION['csrf']=bin2hex(random_bytes(32));go($candidate['must_change']?'/password':'/'); }
                $failures=$attempt && time()-(int)$attempt['updated']<900 ? (int)$attempt['failures']+1 : 1;
                db()->prepare('INSERT INTO attempts(ip,failures,updated) VALUES(?,?,?) ON DUPLICATE KEY UPDATE failures=?,updated=?')->execute([$ip,$failures,time(),$failures,time()]);$message='ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
            }
        }
        screen('เข้าสู่ระบบ Nursing Command Center','<p>กรุณาใช้บัญชีที่ได้รับจากผู้ดูแลระบบ</p><p class="message">'.h($message).'</p><form method="post">'.csrf().'<label>ชื่อผู้ใช้<input name="username" autocomplete="username" required></label><label>รหัสผ่าน<input type="password" name="password" autocomplete="current-password" required></label><button class="btn primary">เข้าสู่ระบบ</button></form>');
    }
    if (!$user) go('/login');
    if ($user['must_change'] && $path!=='/password') go('/password');
    if(str_starts_with($user['unit'],'ipd_')&&in_array($path,['/','/index.html','/entry.html'],true))go('/ipd');
    if ($path==='/api/reports') { require_once __DIR__.'/reports-api.php';reports_api($user); }
    if ($path==='/password') {
        if ($_SERVER['REQUEST_METHOD']==='POST') {
            $password=(string)($_POST['password']??'');
            if (!password_verify((string)($_POST['current']??''),$user['password'])) $message='รหัสผ่านเดิมไม่ถูกต้อง';
            elseif (mb_strlen($password, 'UTF-8')<8 || strlen($password)>72 || $password!==($_POST['confirm']??'') || password_verify($password,$user['password'])) $message='รหัสผ่านใหม่ต้องต่างจากเดิม อย่างน้อย 8 ตัวอักษร และไม่เกิน 72 ไบต์ และยืนยันตรงกัน';
            else { db()->prepare('UPDATE users SET password=?,must_change=0 WHERE id=?')->execute([password_hash($password,PASSWORD_DEFAULT),$user['id']]);session_regenerate_id(true);go('/'); }
        }
        screen('เปลี่ยนรหัสผ่าน','<p>บัญชีใหม่ต้องเปลี่ยนรหัสผ่านก่อนใช้งาน</p><p class="message">'.h($message).'</p><form method="post">'.csrf().'<label>รหัสผ่านเดิม<input type="password" name="current" autocomplete="current-password" required></label><label>รหัสผ่านใหม่<input type="password" name="password" autocomplete="new-password" minlength="8" required></label><label>ยืนยันรหัสผ่าน<input type="password" name="confirm" autocomplete="new-password" required></label><button class="btn primary">บันทึก</button></form><a href="/">กลับหน้าหลัก</a>');
    }
    if ($path==='/users') { if (!allowed($user,'users')) { http_response_code(403);screen('ไม่มีสิทธิ์เข้าถึง','<a href="/">กลับหน้าหลัก</a>'); } users_screen($user); }
    if (in_array($path,['/ipd','/ipd.html'],true)) { require_once __DIR__.'/ipd.php'; ipd_screen($user); }
    if (in_array($path,['/ipd-dashboard','/ipd-dashboard.html'],true)) { require_once __DIR__.'/ipd-dashboard.php'; ipd_dashboard($user); }
    $pages=['index'=>'overview','opd'=>'opd','lr'=>'lr','ht'=>'ht','dm'=>'dm','ckd'=>'ckd','warfarin'=>'warfarin','asthma'=>'asthma','entry'=>'entry','risks'=>'risks','workforce'=>'workforce','reports'=>'reports','approvals'=>'approvals','settings'=>'settings'];
    $file=$path==='/'?'index.html':ltrim($path,'/');
    if (preg_match('/^([a-z]+)\.html$/D',$file,$m) && isset($pages[$m[1]])) {
        if (!allowed($user,$pages[$m[1]])) { http_response_code(403);screen('ไม่มีสิทธิ์เข้าถึงหน้านี้','<a href="/">กลับหน้าหลัก</a>'); }
        $public=array_intersect_key($user,array_flip(['id','username','name','role','unit']));
        $html=file_get_contents(__DIR__.'/'.$file);
        require_once __DIR__.'/reports-api.php';
        $data=report_bootstrap($user);
        $html=str_replace('<head>','<head><base href="'.h($base).'/"><script>window.NCC_ROUTED=true;</script>',$html);
        $inject='<script src="'.h($base).'/thai-date.js"></script><script>window.NCC_BASE='.json_encode($base,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT).';window.NCC_USER='.json_encode($public,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT).';window.NCC_CSRF='.json_encode($_SESSION['csrf']).';window.NCC_REPORTS='.json_encode($data,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT).';</script><script src="auth-ui.js?v='.filemtime(__DIR__.'/auth-ui.js').'"></script>';
        header('Content-Type: text/html; charset=utf-8');echo str_replace('<script src="reference-dashboard.js">',$inject.'<script src="reference-dashboard.js">',$html);return;
    }
    $assets=['thai-date.js'=>'text/javascript','ipd.css'=>'text/css','ipd.js'=>'text/javascript','style.css'=>'text/css','config.js'=>'text/javascript','app.js'=>'text/javascript','reference-dashboard.js'=>'text/javascript','auth-ui.js'=>'text/javascript'];
    if (isset($assets[$file])) { header('Content-Type: '.$assets[$file].'; charset=utf-8');readfile(__DIR__.'/'.$file);return; }
    http_response_code(404);echo 'Not found';
}
require_once __DIR__.'/users-admin.php';
