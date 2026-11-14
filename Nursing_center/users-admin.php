<?php
function manage_user(array $actor,array $input): string {
    if($actor['role']!=='admin')throw new RuntimeException('ไม่มีสิทธิ์จัดการผู้ใช้');
    $pdo=db();$pdo->beginTransaction();
    try {
        // Serialize administration changes so the last active administrator is protected.
        $all=$pdo->query('SELECT id,username,name,role,unit,active,must_change FROM users ORDER BY id FOR UPDATE')->fetchAll();
        $action=$input['action']??'';$id=(int)($input['id']??0);$target=null;
        foreach($all as $row)if((int)$row['id']===$id)$target=$row;
        if($action!=='create'&&!$target)throw new RuntimeException('ไม่พบบัญชีผู้ใช้');
        if(!in_array($action,['create','update','toggle','delete'],true))throw new RuntimeException('คำสั่งไม่ถูกต้อง');
        $self=$id===(int)$actor['id'];
        if(in_array($action,['create','update'],true)) {
            $username=trim($input['username']??'');$name=trim($input['name']??'');$role=$input['role']??'';$unit=$input['unit']??'';
            $active=isset($input['active'])?1:0;$mustChange=isset($input['must_change'])?1:0;
            if(!preg_match('/^[a-zA-Z0-9_.-]{3,50}$/D',$username)||$name===''||mb_strlen($name)>200||!isset(NCC_ROLES[$role])||!in_array($unit,array_merge(['all'],NCC_UNITS),true))throw new RuntimeException('กรุณากรอกชื่อผู้ใช้ ชื่อแสดง บทบาท และหน่วยงานให้ถูกต้อง');
            if(in_array($role,['head','staff'],true)&&$unit==='all')throw new RuntimeException('ผู้บันทึกและหัวหน้าหน่วยต้องเลือกหน่วยงานเฉพาะ');
            if($self&&($role!=='admin'||!$active))throw new RuntimeException('ไม่สามารถปิดใช้งานหรือลดสิทธิ์แอดมินของตนเอง');
            $password=(string)($input['password']??'');
            if($action==='create'&&$password==='')$password=$username.'11120';
            if($password!==''&&(mb_strlen($password)<8||strlen($password)>72))throw new RuntimeException('รหัสผ่านต้องอย่างน้อย 8 ตัวอักษร และไม่เกิน 72 ไบต์');
        } else {
            if($self)throw new RuntimeException('ไม่สามารถลบหรือปิดใช้งานบัญชีตนเอง');
            $active=$action==='toggle'?1-(int)$target['active']:0;$role=$target['role'];
        }
        if($target&&$target['role']==='admin'&&(int)$target['active']===1&&($role!=='admin'||!$active)) {
            $admins=array_filter($all,fn($row)=>$row['role']==='admin'&&(int)$row['active']===1);
            if(count($admins)<=1)throw new RuntimeException('ต้องเหลือผู้ดูแลระบบที่เปิดใช้งานอย่างน้อย 1 บัญชี');
        }
        if($action==='create') {
            $pdo->prepare('INSERT INTO users(username,name,password,role,unit,active,must_change) VALUES(?,?,?,?,?,?,?)')->execute([$username,$name,password_hash($password,PASSWORD_DEFAULT),$role,$unit,$active,$mustChange]);
            $message='เพิ่มผู้ใช้งานแล้ว';
        } elseif($action==='update') {
            $pdo->prepare('UPDATE users SET username=?,name=?,role=?,unit=?,active=?,must_change=? WHERE id=?')->execute([$username,$name,$role,$unit,$active,$mustChange,$id]);
            if($password!=='')$pdo->prepare('UPDATE users SET password=? WHERE id=?')->execute([password_hash($password,PASSWORD_DEFAULT),$id]);
            $message='แก้ไขผู้ใช้งานแล้ว';
        } elseif($action==='toggle') {
            $pdo->prepare('UPDATE users SET active=? WHERE id=?')->execute([$active,$id]);$message='เปลี่ยนสถานะบัญชีแล้ว';
        } else {
            if(($input['confirm_delete']??'')!=='yes')throw new RuntimeException('กรุณายืนยันการลบบัญชี');
            $q=$pdo->prepare('SELECT (SELECT COUNT(*) FROM reports WHERE updated_by=?) + (SELECT COUNT(*) FROM report_history WHERE changed_by=?)');$q->execute([$id,$id]);
            if((int)$q->fetchColumn()>0){$pdo->prepare('UPDATE users SET active=0 WHERE id=?')->execute([$id]);$message='บัญชีมีประวัติรายงาน จึงปิดใช้งานแทนการลบเพื่อเก็บประวัติ';}
            else {$pdo->prepare('DELETE FROM users WHERE id=?')->execute([$id]);$message='ลบผู้ใช้งานแล้ว';}
        }
        $pdo->commit();return $message;
    } catch(Throwable $e) {if($pdo->inTransaction())$pdo->rollBack();throw $e;}
}
function users_screen(array $user): never {
    $message=$_SESSION['users_message']??'';unset($_SESSION['users_message']);
    $editId=(int)($_GET['edit']??0);
    if($_SERVER['REQUEST_METHOD']==='POST') {
        try {$_SESSION['users_message']=manage_user($user,$_POST);go('/users');}
        catch(PDOException $e){$message=$e->getCode()==='23000'?'ชื่อผู้ใช้ซ้ำหรือบัญชีมีข้อมูลอ้างอิง กรุณาตรวจสอบ':'บันทึกไม่สำเร็จ กรุณาลองใหม่';}
        catch(RuntimeException $e){$message=$e->getMessage();}
        if(($_POST['action']??'')==='update')$editId=(int)($_POST['id']??0);
    }
    $edit=null;
    if($editId){$q=db()->prepare('SELECT id,username,name,role,unit,active,must_change FROM users WHERE id=?');$q->execute([$editId]);$edit=$q->fetch();if(!$edit)$message='ไม่พบบัญชีที่ต้องการแก้ไข';}
    $values=$edit?:['username'=>'','name'=>'','role'=>'staff','unit'=>'opd','active'=>1,'must_change'=>1];
    if($_SERVER['REQUEST_METHOD']==='POST'&&in_array($_POST['action']??'',['create','update'],true))foreach(['username','name','role','unit'] as $key)$values[$key]=$_POST[$key]??$values[$key];
    $roles='';foreach(NCC_ROLES as $key=>$label)$roles.='<option value="'.h($key).'" '.($values['role']===$key?'selected':'').'>'.h($label).'</option>';
    $units='';foreach(array_merge(['all'],NCC_UNITS) as $key)$units.='<option value="'.h($key).'" '.($values['unit']===$key?'selected':'').'>'.($key==='all'?'ทุกหน่วยงาน':(['ipd_male'=>'IPD · หอผู้ป่วยชาย','ipd_female'=>'IPD · หอผู้ป่วยหญิง'][$key]??strtoupper($key))).'</option>';
    $body='<style>.auth{max-width:1200px}.user-card{border:1px solid #dce7f2;border-radius:12px;padding:20px;margin:20px 0}.user-card h2{margin:0 0 10px}.user-grid{display:grid!important;grid-template-columns:repeat(3,minmax(0,1fr));align-items:start!important}.user-grid input,.user-grid select{width:100%}.user-check{display:flex!important;align-items:center;gap:8px}.user-check input{width:auto}.user-actions{display:flex;gap:7px;flex-wrap:wrap}.auth .user-actions form{margin:0}.user-delete{color:#b32c38}.user-delete form{display:grid;gap:8px}.user-note{color:#647d96;font-size:12px;line-height:1.8}.user-message{background:#edf6ff;padding:12px;border-radius:9px}.auth details{margin-top:8px}@media(max-width:700px){.user-grid{grid-template-columns:1fr!important}.auth{margin:12px;padding:16px}}</style><a href="/">← หน้าหลัก</a> · <a href="/settings.html">ตั้งค่าระบบ</a>';
    if($message)$body.='<p class="user-message" role="status">'.h($message).'</p>';
    $body.='<section class="user-card"><h2>'.($edit?'แก้ไขบัญชี '.h($edit['username']):'เพิ่มผู้ใช้งาน').'</h2><form class="user-grid" method="post" action="'.h(base_path()).'/users">'.csrf().'<input type="hidden" name="action" value="'.($edit?'update':'create').'"><input type="hidden" name="id" value="'.(int)($edit['id']??0).'"><label>ชื่อผู้ใช้<input name="username" value="'.h($values['username']).'" pattern="[a-zA-Z0-9_.-]{3,50}" maxlength="50" required autocomplete="off"></label><label>ชื่อแสดง<input name="name" value="'.h($values['name']).'" maxlength="200" required></label><label>บทบาท<select name="role">'.$roles.'</select></label><label>หน่วยงาน<select name="unit">'.$units.'</select></label><label>'.($edit?'รหัสผ่านใหม่ (เว้นว่างเพื่อใช้เดิม)':'รหัสผ่าน (เว้นว่าง = ชื่อผู้ใช้ + 11120)').'<input type="password" name="password" minlength="8" autocomplete="new-password"></label><div><label class="user-check"><input type="checkbox" name="active" value="1" '.($values['active']?'checked':'').'>เปิดใช้งาน</label><label class="user-check"><input type="checkbox" name="must_change" value="1" '.($values['must_change']?'checked':'').'>เปลี่ยนรหัสผ่านเมื่อเข้าสู่ระบบ</label></div><div class="user-actions"><button class="btn primary">'.($edit?'บันทึกการแก้ไข':'เพิ่มผู้ใช้').'</button>'.($edit?'<a class="btn" href="/users">ยกเลิก</a>':'').'</div></form><p class="user-note">รหัสผ่านอย่างน้อย 8 ตัวอักษร • ผู้บันทึกและหัวหน้าหน่วยต้องเลือกหน่วยงานเฉพาะ • รหัสผ่านปัจจุบันจะไม่ถูกแสดง</p></section>';
    $search=trim($_GET['q']??'');
    $q=db()->prepare('SELECT id,username,name,role,unit,active,must_change FROM users WHERE username LIKE ? OR name LIKE ? ORDER BY id');$q->execute(['%'.$search.'%','%'.$search.'%']);$rows=$q->fetchAll();
    $body.='<section class="user-card"><h2>บัญชีผู้ใช้งาน <small>('.count($rows).')</small></h2><form method="get"><input type="hidden" name="route" value="/users"><input name="q" value="'.h($search).'" placeholder="ค้นหาชื่อหรือชื่อผู้ใช้" aria-label="ค้นหาผู้ใช้"><button class="btn">ค้นหา</button><a href="/users">แสดงทั้งหมด</a></form><div class="table-scroll"><table><thead><tr><th>ชื่อผู้ใช้</th><th>ชื่อแสดง</th><th>บทบาท / หน่วยงาน</th><th>สถานะ</th><th>จัดการ</th></tr></thead><tbody>';
    foreach($rows as $row) {
        $id=(int)$row['id'];$self=$id===(int)$user['id'];
        $body.='<tr><td><strong>'.h($row['username']).'</strong>'.($self?' <span class="pill info">คุณ</span>':'').'</td><td>'.h($row['name']).'</td><td>'.h(NCC_ROLES[$row['role']]).'<br><small>'.($row['unit']==='all'?'ทุกหน่วยงาน':h(['ipd_male'=>'IPD · หอผู้ป่วยชาย','ipd_female'=>'IPD · หอผู้ป่วยหญิง'][$row['unit']]??strtoupper($row['unit']))).'</small></td><td><span class="pill '.($row['active']?'':'danger').'">'.($row['active']?'เปิดใช้งาน':'ปิดใช้งาน').'</span></td><td><div class="user-actions"><a class="btn" href="/users?edit='.$id.'">แก้ไข</a>';
        if(!$self)$body.='<form method="post">'.csrf().'<input type="hidden" name="id" value="'.$id.'"><button class="btn" name="action" value="toggle">'.($row['active']?'ปิดใช้งาน':'เปิดใช้งาน').'</button></form>';
        $body.='</div>';
        if(!$self)$body.='<details class="user-delete"><summary>ลบบัญชี</summary><form method="post">'.csrf().'<input type="hidden" name="id" value="'.$id.'"><input type="hidden" name="action" value="delete"><label class="user-check"><input type="checkbox" name="confirm_delete" value="yes" required>ยืนยันลบ '.h($row['username']).'</label><button class="btn">ยืนยันลบ</button></form></details>';
        $body.='</td></tr>';
    }
    if(!$rows)$body.='<tr><td colspan="5">ไม่พบผู้ใช้งาน</td></tr>';
    screen('จัดการผู้ใช้งาน',$body.'</tbody></table></div><p class="user-note">บัญชีที่มีประวัติรายงานจะถูกปิดใช้งานแทนการลบ เพื่อรักษาประวัติผู้บันทึก</p></section>');
}
