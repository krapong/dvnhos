<?php
// CLI only. Credentials are generated once and kept outside the served allowlist.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__.'/security.php';
if(!is_dir(__DIR__.'/.private'))mkdir(__DIR__.'/.private',0700,true);
if ((int)db()->query('SELECT COUNT(*) FROM users')->fetchColumn()>0) { echo "Users already initialized\n";exit; }
$accounts=[['admin','ผู้ดูแลระบบ','admin','all'],['executive','ผู้บริหาร','executive','all'],['director','หัวหน้ากลุ่มงาน','director','all']];
foreach(NCC_UNITS as $unit) { $accounts[]=[$unit.'_head','หัวหน้าหน่วย '.strtoupper($unit),'head',$unit];$accounts[]=[$unit.'_staff','ผู้บันทึก '.strtoupper($unit),'staff',$unit]; }
$lines=["บัญชีเริ่มต้น Nursing Command Center", "ต้องเปลี่ยนรหัสผ่านเมื่อเข้าใช้ครั้งแรก เก็บไฟล์นี้เป็นความลับ", "username\tpassword\trole\tunit"];
db()->beginTransaction();
foreach($accounts as [$username,$name,$role,$unit]) { $password=bin2hex(random_bytes(12));db()->prepare('INSERT INTO users(username,name,password,role,unit) VALUES(?,?,?,?,?)')->execute([$username,$name,password_hash($password,PASSWORD_DEFAULT),$role,$unit]);$lines[]=implode("\t",[$username,$password,$role,$unit]); }
file_put_contents(__DIR__.'/.private/initial-accounts.txt',implode(PHP_EOL,$lines));
db()->commit();echo "Created ".count($accounts)." accounts. Credentials: .private/initial-accounts.txt\n";
