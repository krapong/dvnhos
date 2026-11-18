<?php
require_once __DIR__.'/auth-bootstrap.php';
require_once __DIR__.'/hosxp-auth.php';
$isApi=in_array(basename($_SERVER['SCRIPT_NAME']??''),['api.php','manual.php','risk.php','usage.php'],true)||in_array(parse_url($_SERVER['REQUEST_URI']??'',PHP_URL_PATH),['/api.php','/manual.php','/risk.php','/usage.php'],true);
// Sessions established before mandatory MFA must authenticate again.
if(isset($_SESSION['auth_user'])&&!isset($_SESSION['mfa_verified_at']))clearAuthSession();
if(isset($_SESSION['auth_user'])){
    require_once __DIR__.'/mfa-store.php';
    try{
        $mfaKey=mfaUserKey($_SESSION['auth_user']);
        $credential=mfaStore(static fn($data)=>$data[$mfaKey]['credential_id']??null);
        if(!is_string($credential)||!hash_equals($credential,(string)($_SESSION['mfa_credential_id']??'')))clearAuthSession();
    }catch(Throwable $e){http_response_code(503);if($isApi){header('Content-Type: application/json; charset=UTF-8');echo json_encode(['ok'=>false,'error'=>'ตรวจสอบ MFA ไม่สำเร็จ กรุณาติดต่อผู้ดูแล'],JSON_UNESCAPED_UNICODE);}else echo 'MFA verification is unavailable.';exit;}
}
if(isset($_SESSION['auth_user'])&&time()-(int)($_SESSION['account_checked_at']??0)>=60){
    try{
        if(!hosxpSessionAllowed($cfg,$_SESSION['auth_user']))clearAuthSession();
        else $_SESSION['account_checked_at']=time();
    }catch(Throwable $e){error_log('[LR session] account verification unavailable');http_response_code(503);if($isApi){header('Content-Type: application/json; charset=UTF-8');echo json_encode(['ok'=>false,'error'=>'ตรวจสอบบัญชี HOSxP ไม่สำเร็จ กรุณาลองใหม่'],JSON_UNESCAPED_UNICODE);}else echo 'HOSxP authentication is unavailable.';exit;}
}
if(!isset($_SESSION['auth_user'])){
    if($isApi){http_response_code(401);header('Content-Type: application/json; charset=UTF-8');echo json_encode(['ok'=>false,'error'=>'กรุณาเข้าสู่ระบบใหม่','login_url'=>'login.php'],JSON_UNESCAPED_UNICODE);exit;}
    header('Location: login.php',true,302);exit;
}
$_SESSION['last_activity']=time();
