<?php
function loginThrottle(string $username,bool $clear=false): bool {
    $dir=__DIR__.'/.private';if(!is_dir($dir)&&!mkdir($dir,0700,true)&&!is_dir($dir))throw new RuntimeException('Login limiter unavailable');
    $file=fopen($dir.'/login-attempts.php','c+');if(!$file)throw new RuntimeException('Login limiter unavailable');
    try{
        if(!flock($file,LOCK_EX))throw new RuntimeException('Login limiter unavailable');
        $prefix="<?php http_response_code(404); exit; ?>\n";$raw=stream_get_contents($file);$data=$raw===''?[]:json_decode(substr($raw,strlen($prefix)),true,512,JSON_THROW_ON_ERROR);
        $now=time();foreach($data as $k=>$times){$data[$k]=array_values(array_filter($times,fn($t)=>$t>$now-300));if(!$data[$k])unset($data[$k]);}
        $ip=$_SERVER['REMOTE_ADDR']??'unknown';$key=hash('sha256',$ip.'|'.strtolower($username));$ipKey=hash('sha256','ip|'.$ip);
        $allowed=count($data[$key]??[])<5&&count($data[$ipKey]??[])<60;
        if($clear)unset($data[$key]);elseif($allowed){$data[$key][]=$now;$data[$ipKey][]=$now;}
        $encoded=$prefix.json_encode($data,JSON_THROW_ON_ERROR);rewind($file);if(fwrite($file,$encoded)!==strlen($encoded)||!ftruncate($file,strlen($encoded))||!fflush($file))throw new RuntimeException('Login limiter unavailable');
        return $allowed;
    }finally{flock($file,LOCK_UN);fclose($file);}
}
