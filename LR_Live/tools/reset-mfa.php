<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require dirname(__DIR__).'/mfa-store.php';
require dirname(__DIR__).'/usage-log.php';
$options=getopt('',['user:','confirm:']);$user=$options['user']??'';
if(!is_string($user)||$user===''||strlen($user)>250||($options['confirm']??null)!==$user){fwrite(STDERR,"Authorized server administrators only. Verify the account owner's identity first.\nUsage: php tools/reset-mfa.php --user=USERNAME --confirm=USERNAME\nThis revokes MFA and all existing sessions for that account.\n");exit(1);}
try{
    $key=mfaUserKey($user);
    $removed=mfaStore(static function(&$data)use($key){$found=isset($data[$key]);unset($data[$key],$data['attempts:'.$key]);return $found;},true);
    usageLog('mfa_admin_reset',['account'=>$user,'existed'=>$removed],'server-admin:'.(getenv('USERNAME')?:getenv('USER')?:'cli'));
    echo $removed?"MFA revoked. Next password login requires new enrollment.\n":"No MFA enrollment found for that account.\n";
}catch(Throwable $e){fwrite(STDERR,"MFA reset failed. Check OpenSSL and private directory permissions.\n");exit(1);}
