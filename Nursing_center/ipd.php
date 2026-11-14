<?php
declare(strict_types=1);
function ipd_fields(): array {
    return ['opening'=>'ยอดยกมา','admit'=>'รับใหม่','transfer_in'=>'รับย้าย','refer'=>'ส่งต่อ Refer','opd'=>'ส่งต่อ OPD case','emergency'=>'ส่งต่อ Emergency case','transfer_out'=>'ย้ายออก','refuse'=>'ไม่สมัครใจอยู่','death'=>'เสียชีวิต','home'=>'กลับบ้าน'];
}
function ipd_total(array $report,string $key): int { return array_sum($report['counts'][$key]??[]); }
function ipd_balance(array $report): int {
    return ipd_total($report,'opening')+ipd_total($report,'admit')+ipd_total($report,'transfer_in')-array_sum(array_map(fn($k)=>ipd_total($report,$k),['refer','opd','emergency','transfer_out','refuse','death','home']));
}
function ipd_validate(array $input): array {
    $report=['counts'=>[],'census'=>[],'support'=>[]];
    foreach(['counts'=>array_keys(ipd_fields()),'census'=>['general','isolation','newborn'],'support'=>['foley','foley_child','heplock','iv','hf','nurses','hours','care_hours']] as $group=>$keys)foreach($keys as $key){
        $values=$group==='counts'?($input[$group][$key]??null):($group==='census'?($input[$group][$key]??null):[$input[$group][$key]??null]);
        $length=$group==='support'?1:4;
        if(!is_array($values)||count($values)!==$length)throw new RuntimeException('กรุณากรอกข้อมูลให้ครบทุกช่อง');
        $clean=[];
        foreach($values as $v){
            if(!is_scalar($v)||!preg_match($group==='support'&&in_array($key,['hours','care_hours'],true)?'/^\d+(?:\.\d{1,2})?$/D':'/^\d+$/D',(string)$v)||(float)$v>100000)throw new RuntimeException('จำนวนต้องเป็นศูนย์หรือจำนวนบวก ไม่เกิน 100,000');
            $clean[]=$group==='support'?(float)$v:(int)$v;
        }
        $report[$group][$key]=$group==='support'?$clean[0]:$clean;
    }
    $balance=ipd_balance($report);
    if($balance<0)throw new RuntimeException('ยอดจำหน่ายมากกว่ายอดผู้ป่วยที่มี กรุณาตรวจสอบ');
    if(array_sum($report['census']['general'])+array_sum($report['census']['isolation'])!==$balance)throw new RuntimeException('ยอดจำแนกประเภทในห้องสามัญ + ห้องแยก ต้องเท่ากับยอดคงพยาบาล');
    if(array_sum($report['census']['newborn'])>$balance)throw new RuntimeException('ทารกแรกเกิดเป็นส่วนหนึ่งของยอดคงพยาบาล ต้องไม่เกินยอดทั้งหมด');
    if($report['support']['foley_child']>$report['support']['foley'])throw new RuntimeException('ยอดคาสายสวนเฉพาะเด็กต้องไม่เกินยอดคาสายสวนทั้งหมด');
    foreach(['foley','heplock','iv','hf'] as $key)if($report['support'][$key]>$balance)throw new RuntimeException('จำนวนผู้ป่วยที่ใช้อุปกรณ์แต่ละรายการต้องไม่เกินยอดคงพยาบาล');
    $report['note']=trim((string)($input['note']??''));
    if(mb_strlen($report['note'])>500)throw new RuntimeException('หมายเหตุต้องไม่เกิน 500 ตัวอักษร');
    return $report;
}
function ipd_save(array $user,string $unit,string $date,string $shift,array $input): void {
    if(!allowed($user,'entry')||!allowed($user,$unit))throw new RuntimeException('ไม่มีสิทธิ์บันทึกหอผู้ป่วยนี้');
    $report=ipd_validate($input);
    $version=filter_var($input['version']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>0]]);
    if($version===false)throw new RuntimeException('กรุณาโหลดรายงานใหม่');
    $pdo=db();$pdo->beginTransaction();
    try{
        $q=$pdo->prepare('SELECT id,version FROM reports WHERE department=? AND report_date=? AND shift=? FOR UPDATE');$q->execute([$unit,$date,$shift]);$row=$q->fetch();
        if(($row?(int)$row['version']:0)!==$version)throw new RuntimeException('มีผู้แก้ไขรายงานแล้ว กรุณาโหลดใหม่ก่อนบันทึก');
        $payload=json_encode($report,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);$version++;
        if($row){$id=$row['id'];$pdo->prepare('UPDATE reports SET payload=?,version=?,updated_by=? WHERE id=?')->execute([$payload,$version,$user['id'],$id]);}
        else{$pdo->prepare('INSERT INTO reports(department,report_date,shift,payload,updated_by) VALUES(?,?,?,?,?)')->execute([$unit,$date,$shift,$payload,$user['id']]);$id=$pdo->lastInsertId();}
        $pdo->prepare('INSERT INTO report_history(report_id,version,payload,changed_by) VALUES(?,?,?,?)')->execute([$id,$version,$payload,$user['id']]);$pdo->commit();
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();if($e instanceof PDOException&&in_array((string)$e->getCode(),['23000','40001'],true))throw new RuntimeException('รายงานถูกแก้ไขพร้อมกัน กรุณาโหลดใหม่');throw $e;}
}
function ipd_screen(array $user): never {
    $wards=['ipd_male'=>'หอผู้ป่วยชาย','ipd_female'=>'หอผู้ป่วยหญิง'];
    if($user['unit']!=='all'&&!isset($wards[$user['unit']])){http_response_code(403);screen('ไม่มีสิทธิ์เข้าถึง IPD','<a href="/">กลับหน้าหลัก</a>');}
    if($user['unit']!=='all')$wards=array_intersect_key($wards,[$user['unit']=>true]);
    $unit=(string)($_POST['unit']??$_GET['unit']??array_key_first($wards));
    if(!isset($wards[$unit])){http_response_code(403);screen('ไม่มีสิทธิ์ในหอผู้ป่วยนี้','<a href="/ipd">กลับ IPD</a>');}
    $date=(string)($_POST['date']??$_GET['date']??(new DateTimeImmutable('now',new DateTimeZone('Asia/Bangkok')))->format('Y-m-d'));
    $shift=(string)($_POST['shift']??$_GET['shift']??'morning');$shifts=['morning'=>'เวรเช้า 08:00–16:00','evening'=>'เวรบ่าย 16:00–00:00','night'=>'เวรดึก 00:00–08:00'];
    $parsed=DateTimeImmutable::createFromFormat('!Y-m-d',$date);
    if(!$parsed||$parsed->format('Y-m-d')!==$date||!isset($shifts[$shift])){http_response_code(422);screen('ช่วงเวลารายงานไม่ถูกต้อง','<a href="/ipd">กลับ IPD</a>');}
    $message='';$failed=false;
    if($_SERVER['REQUEST_METHOD']==='POST'){
        try{ipd_save($user,$unit,$date,$shift,$_POST);$_SESSION['ipd_message']='บันทึกข้อมูลลงฐานข้อมูลกลางเรียบร้อยแล้ว';go('/ipd?unit='.$unit.'&date='.$date.'&shift='.$shift);}
        catch(RuntimeException $e){$message=$e->getMessage();$failed=true;http_response_code(422);}
    }
    $message=$message?:($_SESSION['ipd_message']??'');unset($_SESSION['ipd_message']);
    $q=db()->prepare("SELECT r.*,u.name AS author FROM reports r JOIN users u ON u.id=r.updated_by WHERE department IN ('ipd_male','ipd_female') AND report_date=? AND shift=?");$q->execute([$date,$shift]);$rows=[];
    foreach($q->fetchAll() as $row)if(isset($wards[$row['department']])){$row['data']=json_decode($row['payload'],true);$rows[$row['department']]=$row;}
    $current=$rows[$unit]??null;$data=$failed?$_POST:($current['data']??[]);$write=allowed($user,'entry');
    $fiscalYear=(int)$parsed->format('Y')+((int)$parsed->format('n')>=10?1:0);
    $fiscalStart=($fiscalYear-1).'-10-01';$cumulative=array_fill_keys(array_keys($wards),0);$daily=[];
    $q=db()->prepare("SELECT department,report_date,shift,payload FROM reports WHERE department IN ('ipd_male','ipd_female') AND report_date BETWEEN ? AND ? ORDER BY report_date");$q->execute([$fiscalStart,$date]);
    foreach($q as $r)if(isset($wards[$r['department']])){$payload=json_decode($r['payload'],true);$cumulative[$r['department']]+=ipd_total($payload,'admit');if($r['department']===$unit&&$r['shift']===$shift)$daily[$r['report_date']]=ipd_balance($payload);}
    ob_start(); ?>
    <link rel="stylesheet" href="<?=h(base_path())?>/ipd.css">
        <?php require_once __DIR__.'/ipd-layout.php'; ipd_layout_start($user,$fiscalYear,$wards,$unit,$date,$shift); ?>
<div class="ipd-heading heading"><div><span class="ipd-eyebrow">INPATIENT NURSING · DAILY CENSUS</span><h1>IPD : <?=$unit==='ipd_male'?'ผู้ป่วยในชาย':'ผู้ป่วยในหญิง'?></h1><p>บันทึกข้อมูลรายเวร · <?=h($wards[$unit])?> · ประเภทผู้ป่วย 1–4</p></div><button class="btn" type="button" onclick="setTimeout(() => window.print(), 0)">พิมพ์รายงาน</button></div>
    <form method="get" class="ipd-filter"><input type="hidden" name="route" value="/ipd"><div class="ipd-date-control"><span>วันที่รายงาน (พ.ศ.)</span><?=ncc_thai_date_field('ipdDate',$date,'date')?></div><label>เวร<select name="shift"><?php foreach($shifts as $key=>$label):?><option value="<?=$key?>" <?=$shift===$key?'selected':''?>><?=$label?></option><?php endforeach;?></select></label><input type="hidden" name="unit" value="<?=h($unit)?>"><button class="btn primary">ดูข้อมูล</button></form>
    <?php if($message):?><p class="ipd-message <?=$failed?'error':''?>" role="status"><?=h($message)?></p><?php endif;?>
    <div class="ipd-kpis"><?php foreach($wards as $key=>$label):$r=$rows[$key]['data']??null;?><article class="ipd-kpi <?=$key?>"><span><?=$label?></span><strong><?=$r?ipd_balance($r):'—'?><small> ราย</small></strong><p><?=$r?'รับใหม่ '.ipd_total($r,'admit').' · กลับบ้าน '.ipd_total($r,'home'):'ยังไม่มีรายงานเวรนี้'?></p><span class="ipd-tag"><?=$r?'บันทึกแล้ว':'รอบันทึก'?></span></article><?php endforeach;?><article class="ipd-kpi"><span>คงพยาบาลรวม · เวรที่เลือก</span><strong><?=count($rows)?array_sum(array_map(fn($r)=>ipd_balance($r['data']),$rows)):'—'?><small> ราย</small></strong><p>มีรายงาน <?=count($rows)?> / <?=count($wards)?> หอ · ไม่รวมเวรอื่นซ้ำ</p></article></div>
    <section class="ipd-card"><div class="ipd-section-title"><h3>ยอดรับใหม่สะสม · ปีงบประมาณ <?=$fiscalYear+543?></h3><span class="ipd-muted">1 ต.ค. – วันที่เลือก · รวมทุกเวร</span></div><div class="ipd-cumulative"><?php foreach($cumulative as $key=>$count):?><p><?=h($wards[$key])?> <strong><?=number_format($count)?> ราย</strong></p><?php endforeach;?></div><p class="ipd-muted">รวมเฉพาะรับใหม่ที่บันทึกในระบบ ไม่รวมยกมาและรับย้าย เพื่อไม่ให้นับซ้ำ</p></section>
    <div class="ipd-workspace"><section class="ipd-card"><div class="ipd-section-title"><div><span class="ipd-eyebrow">SHIFT REPORT</span><h2><?=h($wards[$unit])?></h2></div><span class="ipd-tag"><?=$current?'ฉบับที่ '.(int)$current['version']:'รายงานใหม่'?></span></div><p class="ipd-muted"><?=h(ncc_thai_date($date))?> · <?=$shifts[$shift]?> · จำนวนหน่วยเป็นราย</p>
    <form method="post" id="ipd-form" data-original="<?=h(json_encode($current['data']??[],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR))?>"><?=csrf()?><input type="hidden" name="unit" value="<?=h($unit)?>"><input type="hidden" name="date" value="<?=h($date)?>"><input type="hidden" name="shift" value="<?=h($shift)?>"><input type="hidden" name="version" value="<?=h($failed?($_POST['version']??''):($current['version']??0))?>">
    <fieldset <?=$write?'':'disabled'?>><div class="ipd-table-wrap"><table class="ipd-table"><caption>01 · การรับเข้าและจำหน่าย แยกประเภทผู้ป่วย</caption><thead><tr><th scope="col">รายการ</th><?php for($i=1;$i<=4;$i++):?><th scope="col">ประเภท <?=$i?></th><?php endfor;?><th scope="col">รวม</th></tr></thead><tbody>
    <?php foreach(ipd_fields() as $key=>$label):?><tr><th scope="row"><?=$label?></th><?php for($i=0;$i<4;$i++):?><td><input aria-label="<?=$label?> ประเภท <?=$i+1?>" type="number" min="0" max="100000" step="1" name="counts[<?=$key?>][]" value="<?=h($data['counts'][$key][$i]??0)?>" data-count="<?=$key?>" required></td><?php endfor;?><td class="ipd-sum" data-sum="<?=$key?>">0</td></tr><?php endforeach;?></tbody></table></div>
    <div class="ipd-balance"><span>คงพยาบาล = ยกมา + รับใหม่ + รับย้าย − จำหน่ายทุกประเภท</span><strong id="ipd-balance">0</strong></div>
    <div class="ipd-table-wrap"><table class="ipd-table"><caption>02 · จำแนกผู้ป่วยที่คงพยาบาล ณ สิ้นเวร</caption><thead><tr><th>รายการ</th><?php for($i=1;$i<=4;$i++):?><th>ประเภท <?=$i?></th><?php endfor;?><th>รวม</th></tr></thead><tbody><?php foreach(['general'=>'ห้องสามัญ','isolation'=>'ห้องแยก','newborn'=>'ทารกแรกเกิด (นับรวมในห้องข้างต้น)'] as $key=>$label):?><tr><th><?=$label?></th><?php for($i=0;$i<4;$i++):?><td><input aria-label="<?=$label?> ประเภท <?=$i+1?>" type="number" min="0" max="100000" step="1" name="census[<?=$key?>][]" value="<?=h($data['census'][$key][$i]??0)?>" data-census="<?=$key?>" required></td><?php endfor;?><td class="ipd-sum" data-sum="<?=$key?>">0</td></tr><?php endforeach;?></tbody></table></div><p id="ipd-check" aria-live="polite"></p>
    <h3>03 · การดูแลพิเศษและภาระงาน</h3><div class="ipd-support"><?php foreach(['foley'=>'คาสายสวนปัสสาวะ (ราย)','foley_child'=>'คาสายสวนฯ เฉพาะเด็ก (ราย)','heplock'=>'Heparin lock (ราย)','iv'=>'ให้สารน้ำ IV (ราย)','hf'=>'High flow (ราย)','nurses'=>'พยาบาลปฏิบัติงาน (คน)','hours'=>'ชั่วโมงทำงานต่อคน','care_hours'=>'ชั่วโมงการพยาบาลที่ต้องการรวม'] as $key=>$label):?><label><?=$label?><input type="number" min="0" max="100000" step="<?=in_array($key,['hours','care_hours'])?'0.01':'1'?>" name="support[<?=$key?>]" value="<?=h($data['support'][$key]??($key==='hours'?8:0))?>" data-support="<?=$key?>" required></label><?php endforeach;?></div>
    <label class="ipd-note">หมายเหตุประจำเวร<textarea name="note" maxlength="500" rows="3" placeholder="บันทึกประเด็นส่งต่อเวร โดยไม่ระบุข้อมูลส่วนบุคคลผู้ป่วย"><?=h($data['note']??'')?></textarea></label></fieldset>
    <div class="ipd-save"><span><?=$current?'บันทึกล่าสุดโดย '.h($current['author']).' · '.h(ncc_thai_date($current['updated_at'],true)):'กรอก 0 เมื่อไม่มีรายการ'?></span><?php if($write):?><button class="btn" type="reset">คืนค่าที่โหลดมา</button><button class="btn primary" id="ipd-save">บันทึกรายงานเวร</button><?php else:?><strong>สิทธิ์ดูข้อมูลเท่านั้น</strong><?php endif;?></div></form></section>
    <aside class="ipd-aside"><section class="ipd-card"><span class="ipd-eyebrow">7 DAY CENSUS</span><h3>คงพยาบาลย้อนหลัง 7 วัน</h3><p class="ipd-muted">หอและเวรที่เลือก · — คือไม่มีรายงาน</p><?php $maximum=max(array_merge([1],array_values($daily)));for($i=6;$i>=0;$i--):$day=$parsed->modify('-'.$i.' days')->format('Y-m-d');$value=$daily[$day]??null;?><div class="ipd-trend"><span><?=h(ncc_thai_date($day))?></span><div><i style="width:<?=$value===null?0:round($value/$maximum*100)?>%"></i></div><strong><?=$value??'—'?></strong></div><?php endfor;?></section><section class="ipd-card"><span class="ipd-eyebrow">WORKLOAD</span><h3>Productivity / เวร</h3><strong class="ipd-productivity" id="ipd-productivity">—</strong><p class="ipd-muted">ชั่วโมงการพยาบาลที่ต้องการ ÷ (จำนวนพยาบาล × ชั่วโมงทำงานต่อคน) × 100</p><p>กรอกชั่วโมงที่ต้องการตามเกณฑ์ของหน่วยงาน ไม่มีการสมมติน้ำหนักประเภทผู้ป่วย</p></section>
    <section class="ipd-card"><h3>แนวทางบันทึก</h3><ol><li>เลือกวันที่ เวร และหอผู้ป่วย</li><li>กรอกยอดรับเข้าและจำหน่ายตามประเภท 1–4</li><li>ยอดห้องสามัญ + ห้องแยก ต้องตรงกับคงพยาบาล</li><li>ตรวจยอดก่อนบันทึก ระบบเก็บประวัติทุกฉบับ</li></ol><p class="ipd-muted">Refer, OPD case และ Emergency case เป็นรายการจำหน่ายแยกกัน ให้นับแต่ละรายเพียงรายการเดียว วันที่เวรบ่ายคือวันที่เริ่มเวร</p></section>
    <section class="ipd-card"><h3>สิทธิ์การใช้งาน</h3><p>ผู้บันทึก / หัวหน้าหอ: อ่านและบันทึกเฉพาะหอของตน</p><p>ผู้บริหาร / หัวหน้ากลุ่มงาน: ดูรายงานตามหน่วยงานที่ได้รับสิทธิ์</p><p>ผู้ดูแลระบบ: จัดการบัญชีและรายงานตามขอบเขตหน่วยงาน</p><small>โมดูลนี้บันทึกข้อมูลรวมรายเวร ไม่มีขั้นตอนรับรองรายงาน</small></section>
    <?php if($current): $history=db()->prepare('SELECT h.version,h.changed_at,u.name FROM report_history h JOIN users u ON u.id=h.changed_by WHERE h.report_id=? ORDER BY h.version DESC LIMIT 5');$history->execute([$current['id']]);?><section class="ipd-card"><h3>ประวัติการบันทึก</h3><?php foreach($history as $item):?><p><strong>ฉบับ <?=$item['version']?></strong> · <?=h($item['name'])?><br><small><?=h(ncc_thai_date($item['changed_at'],true))?></small></p><?php endforeach;?></section><?php endif;?></aside></div>
    <div class="footer-note">ข้อมูลรายงานที่เจ้าหน้าที่บันทึก • บันทึกแบบ Manual • ฐานข้อมูลกลาง MySQL</div></main></div><button class="mobile-menu" id="mobileBtn" type="button" aria-label="เปิดเมนู" aria-controls="sidebar" aria-expanded="false">☰</button>
    <script src="<?=h(base_path())?>/ipd.js"></script>
    <?php screen('IPD · หอผู้ป่วยใน',ob_get_clean(),true);
}
