<?php
$branding=is_file(__DIR__.'/branding.config.php')?require __DIR__.'/branding.config.php':[];
if(!is_array($branding))$branding=[];
$hospitalName=trim((string)($branding['hospital_name']??''));
$developerName=trim((string)($branding['developer_name']??''));
$copyrightOwner=trim((string)($branding['copyright_owner']??''))?:$hospitalName;
$brandingEscape=static fn($text)=>htmlspecialchars((string)$text,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
$hospitalLogo='';
foreach(['png','jpg','jpeg','webp'] as $extension){
    $logoPath=__DIR__.'/assets/hospital-logo.'.$extension;
    if(!is_file($logoPath)||filesize($logoPath)>2*1024*1024)continue;
    $info=@getimagesize($logoPath);
    $mime=$info['mime']??'';
    if(!$info||!in_array($mime,['image/png','image/jpeg','image/webp'],true))continue;
    $bytes=@file_get_contents($logoPath);
    if($bytes===false)continue;
    $hospitalLogo='data:'.$mime.';base64,'.base64_encode($bytes);
    break;
}
$copyrightYear=(new DateTimeImmutable('now',new DateTimeZone('Asia/Bangkok')))->format('Y');
