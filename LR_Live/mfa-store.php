<?php
// RFC 6238, SHA-1 / six digits / 30 seconds. Never log secrets or OTPs.
function mfaBase32(string $bytes): string {
    $alphabet='ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';$bits='';$out='';
    foreach(str_split($bytes) as $byte)$bits.=str_pad(decbin(ord($byte)),8,'0',STR_PAD_LEFT);
    foreach(str_split($bits,5) as $part)$out.=$alphabet[bindec(str_pad($part,5,'0'))];
    return $out;
}
function mfaDecode(string $secret): string {
    if(!preg_match('/\A[A-Z2-7]+\z/',$secret))throw new RuntimeException('Invalid MFA key');
    $bits='';$out='';foreach(str_split($secret) as $c)$bits.=str_pad(decbin(strpos('ABCDEFGHIJKLMNOPQRSTUVWXYZ234567',$c)),5,'0',STR_PAD_LEFT);
    foreach(str_split($bits,8) as $part)if(strlen($part)===8)$out.=chr(bindec($part));return $out;
}
function mfaTotp(string $secret,int $counter,int $digits=6): string {
    $hash=hash_hmac('sha1',pack('N2',intdiv($counter,4294967296),$counter%4294967296),mfaDecode($secret),true);
    $offset=ord($hash[19])&15;$value=unpack('N',substr($hash,$offset,4))[1]&0x7fffffff;
    return str_pad((string)($value%(10**$digits)),$digits,'0',STR_PAD_LEFT);
}
function mfaMatch(string $secret,string $code,int $last=-1,?int $now=null): ?int {
    if(!preg_match('/\A[0-9]{6}\z/',$code))return null;
    $step=intdiv($now??time(),30);
    foreach([$step,$step-1,$step+1] as $counter)if($counter>$last&&hash_equals(mfaTotp($secret,$counter),$code))return $counter;
    return null;
}
function mfaUserKey(string $username): string {return hash('sha256',strtolower($username));}
function mfaRecoveryCodes(): array {return array_map(static fn()=>strtoupper(bin2hex(random_bytes(8))),range(1,10));}
function mfaRecoveryHash(string $code): string {return hash('sha256',strtoupper(str_replace([' ','-'],'',$code)));}
function mfaStore(callable $callback,bool $write=false,?string $directory=null): mixed {
    if(!extension_loaded('openssl'))throw new RuntimeException('MFA requires the OpenSSL PHP extension');
    $directory??=__DIR__.'/.private/mfa';
    if(!is_dir($directory)&&!mkdir($directory,0700,true)&&!is_dir($directory))throw new RuntimeException('MFA storage unavailable');
    $lock=fopen($directory.'/store.lock','c+b');if(!$lock)throw new RuntimeException('MFA storage unavailable');
    try {
        if(!flock($lock,$write?LOCK_EX:LOCK_SH))throw new RuntimeException('MFA lock unavailable');
        $path=$directory.'/accounts.php';$prefix="<?php http_response_code(404); exit; ?>\n";
        $keyPath=$directory.'/key.php';$encryptionKey=null;
        if(is_file($keyPath)){
            $rawKey=file_get_contents($keyPath);
            if($rawKey===false||!str_starts_with($rawKey,$prefix))throw new RuntimeException('MFA key invalid');
            $encryptionKey=base64_decode(substr($rawKey,strlen($prefix)),true);
            if($encryptionKey===false||strlen($encryptionKey)!==32)throw new RuntimeException('MFA key invalid');
        }elseif(is_file($path))throw new RuntimeException('MFA encryption key missing; restore from backup');
        $data=[];
        if(is_file($path)){
            $raw=file_get_contents($path);if($raw===false||!str_starts_with($raw,$prefix))throw new RuntimeException('MFA storage invalid');
            $envelope=json_decode(substr($raw,strlen($prefix)),true,8,JSON_THROW_ON_ERROR);
            $plain=openssl_decrypt(base64_decode($envelope['ciphertext'],true),'aes-256-gcm',$encryptionKey,OPENSSL_RAW_DATA,base64_decode($envelope['iv'],true),base64_decode($envelope['tag'],true),'LR-EMR-MFA-v1');
            if($plain===false)throw new RuntimeException('MFA decryption failed');
            $data=json_decode($plain,true,512,JSON_THROW_ON_ERROR);
            if(!is_array($data))throw new RuntimeException('MFA storage invalid');
        }
        $result=$callback($data);
        if($write){
            if($encryptionKey===null){
                $encryptionKey=random_bytes(32);$keyFile=fopen($keyPath,'x+b');
                if(!$keyFile)throw new RuntimeException('MFA key creation failed');
                try{chmod($keyPath,0600);$keyBytes=$prefix.base64_encode($encryptionKey);if(fwrite($keyFile,$keyBytes)!==strlen($keyBytes)||!fflush($keyFile))throw new RuntimeException('MFA key write failed');}finally{fclose($keyFile);}
            }
            $iv=random_bytes(12);$tag='';$ciphertext=openssl_encrypt(json_encode($data,JSON_THROW_ON_ERROR),'aes-256-gcm',$encryptionKey,OPENSSL_RAW_DATA,$iv,$tag,'LR-EMR-MFA-v1');
            if($ciphertext===false)throw new RuntimeException('MFA encryption failed');
            $encoded=$prefix.json_encode(['iv'=>base64_encode($iv),'tag'=>base64_encode($tag),'ciphertext'=>base64_encode($ciphertext)],JSON_THROW_ON_ERROR);$temp=tempnam($directory,'mfa-');
            if($temp===false)throw new RuntimeException('MFA storage unavailable');
            try{
                chmod($temp,0600);
                if(file_put_contents($temp,$encoded)!==strlen($encoded)||!rename($temp,$path))throw new RuntimeException('MFA storage write failed');
            }finally{if(is_file($temp))unlink($temp);}
        }
        return $result;
    }finally{flock($lock,LOCK_UN);fclose($lock);}
}
function mfaVerifyEntry(array &$entry,string $code): bool {
    $counter=mfaMatch($entry['secret'],$code,(int)($entry['last_counter']??-1));
    if($counter!==null){$entry['last_counter']=$counter;return true;}
    $hash=mfaRecoveryHash($code);
    foreach($entry['recovery']??[] as $i=>$stored)if(hash_equals($stored,$hash)){array_splice($entry['recovery'],$i,1);return true;}
    return false;
}
