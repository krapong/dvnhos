<?php
require_once __DIR__.'/security.php';
require_once __DIR__.'/usage-log.php';
header('Content-Type: application/json; charset=UTF-8');
function usageResponse(int $status,array $data): never {http_response_code($status);echo json_encode($data,JSON_UNESCAPED_UNICODE);exit;}
if($_SERVER['REQUEST_METHOD']!=='POST'){header('Allow: POST');usageResponse(405,['ok'=>false]);}
$token=$_SERVER['HTTP_X_CSRF_TOKEN']??'';
if(!is_string($token)||!hash_equals($_SESSION['csrf'],$token))usageResponse(403,['ok'=>false]);
try{$body=json_decode(file_get_contents('php://input',false,null,0,4096),true,8,JSON_THROW_ON_ERROR);}catch(Throwable $e){usageResponse(400,['ok'=>false]);}
if(!is_array($body)||!in_array($body['event']??null,['record_open','csv_export'],true))usageResponse(400,['ok'=>false]);
$details=['source'=>'browser'];
if($body['event']==='record_open'){
    $id=$body['record_id']??'';
    if(!is_string($id)||!preg_match('/\A[a-zA-Z0-9-]{1,100}\z/',$id))usageResponse(400,['ok'=>false]);
    $details['record_id']=$id;
}else{
    if(!is_int($body['count']??null)||$body['count']<1||$body['count']>10000)usageResponse(400,['ok'=>false]);
    $details['count']=$body['count'];
}
$ok=usageLog($body['event'],$details);
usageResponse($ok?200:503,['ok'=>$ok]);
