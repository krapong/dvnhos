<?php
declare(strict_types=1);

function hosxpAccountPermitted(array $account,string $charset,array $allowedUsers=[]): bool
{
    if(strtoupper(trim((string)($account['account_disable']??'')))==='Y')return false;
    $group=(string)($account['groupname']??'');
    if(preg_match('//u',$group)!==1){$group=iconv($charset,'UTF-8',$group);if($group===false)return false;}
    return in_array((string)($account['loginname']??''),$allowedUsers,true)||in_array(trim($group),['พยาบาล','พยาบาลจิตเวช','พยาบาลผู้ป่วยใน','พยาบาลห้องฉุกเฉิน'],true);
}
function hosxpNurseAllowed(array $account,string $password,string $charset,array $allowedUsers=[]): bool
{
    if($password===''||!hosxpAccountPermitted($account,$charset,$allowedUsers))return false;
    $hash=(string)($account['passweb']??'');
    return preg_match('/\A[0-9a-f]{32}\z/i',$hash)===1&&hash_equals(strtolower($hash),md5($password));
}
function hosxpSessionAllowed(array $cfg,string $username): bool
{
    $pdo=new PDO(sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s',$cfg['db_host'],$cfg['db_port'],$cfg['db_name'],$cfg['db_charset']),$cfg['db_user'],$cfg['db_password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>false,PDO::ATTR_TIMEOUT=>4]);
    $stmt=$pdo->prepare('SELECT loginname,groupname,account_disable FROM opduser WHERE loginname=:username LIMIT 2');$stmt->execute([':username'=>$username]);$rows=$stmt->fetchAll(PDO::FETCH_ASSOC);
    return count($rows)===1&&hosxpAccountPermitted($rows[0],$cfg['db_charset'],$cfg['app_allowed_users']??[]);
}

function authenticateHosxpNurse(array $cfg, string $username, string $password): bool
{
    if ($username === '' || $password === '' || strlen($username) > 250 || strlen($password) > 1024) return false;
    $pdo = new PDO(
        sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $cfg['db_host'], $cfg['db_port'], $cfg['db_name'], $cfg['db_charset']),
        $cfg['db_user'], $cfg['db_password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false, PDO::ATTR_TIMEOUT => 4]
    );
    $stmt = $pdo->prepare('SELECT loginname, passweb, groupname, account_disable FROM opduser WHERE loginname = :username LIMIT 2');
    $stmt->execute([':username' => $username]);
    $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return count($accounts) === 1 && hosxpNurseAllowed($accounts[0], $password, $cfg['db_charset'], $cfg['app_allowed_users'] ?? []);
}
