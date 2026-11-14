<?php
// One protected JSON-lines file per Bangkok calendar day. No passwords or search text.
function usageRetentionStart(?DateTimeImmutable $now=null): DateTimeImmutable {
    $now=($now??new DateTimeImmutable('now',new DateTimeZone('Asia/Bangkok')))->setTimezone(new DateTimeZone('Asia/Bangkok'));
    return $now->setTime(0,0)->modify('-59 days');
}
function usagePrune(?DateTimeImmutable $now=null): int {
    $cutoff=usageRetentionStart($now)->format('Y-m-d');$removed=0;
    foreach(glob(__DIR__.'/.private/usage-????-??-??.php')?:[] as $path){
        if(is_link($path)||!is_file($path)||!preg_match('/\Ausage-([0-9]{4}-[0-9]{2}-[0-9]{2})\.php\z/',basename($path),$m))continue;
        $date=DateTimeImmutable::createFromFormat('!Y-m-d',$m[1],new DateTimeZone('Asia/Bangkok'));
        if(!$date||$date->format('Y-m-d')!==$m[1]||$m[1]>=$cutoff)continue;
        $handle=@fopen($path,'rb');if(!$handle)continue;
        try {
            if(!flock($handle,LOCK_EX|LOCK_NB))continue;
            if(@unlink($path))++$removed;
            elseif(file_exists($path))error_log('[LR usage] Cannot delete expired usage log');
        }finally{flock($handle,LOCK_UN);fclose($handle);}
    }
    return $removed;
}
function usageLog(string $event,array $details=[],?string $actor=null): bool {
    $now=new DateTimeImmutable('now',new DateTimeZone('Asia/Bangkok'));
    static $pruned=false;
    if(!$pruned){usagePrune($now);$pruned=true;}
    $entry=['time'=>$now->format('c'),'user'=>$actor??($_SESSION['auth_user']??''),
        'event'=>$event,'ip'=>$_SERVER['REMOTE_ADDR']??'','details'=>$details];
    $path=__DIR__.'/.private/usage-'.$now->format('Y-m-d').'.php';
    $handle=@fopen($path,'c+b');
    if(!$handle){error_log('[LR usage] Cannot open usage log');return false;}
    $ok=false;
    try {
        if(!flock($handle,LOCK_EX))return false;
        if(fstat($handle)['size']===0){
            $header="<?php http_response_code(404); exit; ?>\n";
            if(fwrite($handle,$header)!==strlen($header))return false;
        }
        fseek($handle,0,SEEK_END);
        $line=json_encode($entry,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE|JSON_THROW_ON_ERROR)."\n";
        $ok=fwrite($handle,$line)===strlen($line)&&fflush($handle);
    } catch(Throwable $e){error_log('[LR usage] Write failed');}
    finally {flock($handle,LOCK_UN);fclose($handle);}
    if(!$ok)error_log('[LR usage] Incomplete usage log write');
    return $ok;
}
