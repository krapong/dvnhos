<?php
// Additive, repeatable upgrade. Never resets existing users or reports.
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require __DIR__.'/security.php';
$pdo=db();
$pdo->exec("ALTER TABLE users MODIFY unit ENUM('all','opd','lr','ht','dm','ckd','warfarin','asthma','ipd_male','ipd_female') NOT NULL");
$pdo->exec("ALTER TABLE reports MODIFY department ENUM('opd','lr','ht','dm','ckd','warfarin','asthma','ipd_male','ipd_female') NOT NULL");
$lines=[];
foreach(['male'=>'ชาย','female'=>'หญิง'] as $sex=>$label)foreach(['head'=>'หัวหน้าหอ','staff'=>'ผู้บันทึก'] as $role=>$name){
    $username='ipd_'.$sex.'_'.$role;
    $q=$pdo->prepare('SELECT id FROM users WHERE username=?');$q->execute([$username]);if($q->fetchColumn())continue;
    $password=bin2hex(random_bytes(8));
    $pdo->prepare('INSERT INTO users(username,name,password,role,unit,must_change) VALUES(?,?,?,?,?,1)')->execute([$username,$name.'ผู้ป่วย'.$label,password_hash($password,PASSWORD_DEFAULT),$role,'ipd_'.$sex]);
    $lines[]=implode("\t",[$username,$password,$name.'ผู้ป่วย'.$label]);
}
if($lines)file_put_contents(__DIR__.'/.private/ipd-accounts.txt',"บัญชี IPD — เปลี่ยนรหัสผ่านเมื่อเข้าสู่ระบบครั้งแรก\n".implode("\n",$lines)."\n",FILE_APPEND|LOCK_EX);
echo "IPD schema and accounts ready. Credentials are in .private/ipd-accounts.txt\n";
