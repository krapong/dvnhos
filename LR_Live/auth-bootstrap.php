<?php
header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');header('X-Frame-Options: DENY');header('Referrer-Policy: no-referrer');
if(!is_file(__DIR__.'/config.php')){http_response_code(503);exit('Application is not configured.');}
$cfg=require __DIR__.'/config.php';
$https=(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')||((string)($_SERVER['SERVER_PORT']??'')==='443');
$local=in_array($_SERVER['REMOTE_ADDR']??'',['127.0.0.1','::1'],true);
if(!$https&&!$local){http_response_code(403);exit('HTTPS required.');}
if(session_status()!==PHP_SESSION_ACTIVE){session_name('LR_EMR_SESSION');session_start(['use_strict_mode'=>true,'use_only_cookies'=>true,'cookie_httponly'=>true,'cookie_secure'=>$https,'cookie_samesite'=>'Strict','cookie_path'=>'/']);}
require_once __DIR__.'/usage-log.php';
function clearAuthSession(): void {$_SESSION=[];session_regenerate_id(true);$_SESSION['csrf']=bin2hex(random_bytes(32));}
function authSessionExpired(array $session,int $now): bool {return isset($session['auth_user'])&&($now-(int)($session['last_activity']??0)>1800||$now-(int)($session['authenticated_at']??0)>28800);}
if(authSessionExpired($_SESSION,time())){usageLog('session_expired');clearAuthSession();}
if(!isset($_SESSION['csrf']))$_SESSION['csrf']=bin2hex(random_bytes(32));
