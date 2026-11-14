<?php
declare(strict_types=1);
require_once __DIR__.'/ipd.php';
require_once __DIR__.'/ipd-layout.php';
function ipd_overview_data(array $rows,array $wards,string $shift,int $year):array {
    $months=array_fill(0,12,['admit'=>0,'discharge'=>0,'reports'=>0,'days'=>0,'census'=>null]);
    $days=[];$admit=0;$discharge=0;$deaths=0;
    foreach($rows as $r){
        if(!isset($wards[$r['department']]))continue;
        $data=is_array($r['payload'])?$r['payload']:json_decode($r['payload'],true,512,JSON_THROW_ON_ERROR);
        $m=((int)substr($r['report_date'],5,2)+2)%12;
        $out=array_sum(array_map(fn($key)=>ipd_total($data,$key),['refer','opd','emergency','transfer_out','refuse','death','home']));
        $admit+=ipd_total($data,'admit');$discharge+=$out;$deaths+=ipd_total($data,'death');
        $months[$m]['admit']+=ipd_total($data,'admit');$months[$m]['discharge']+=$out;$months[$m]['reports']++;
        if($r['shift']===$shift)$days[$r['report_date']][$r['department']]=$data;
    }
    ksort($days);$monthlyDays=array_fill(0,12,[]);
    foreach($days as $date=>$reports)if(count($reports)===count($wards))$monthlyDays[((int)substr($date,5,2)+2)%12][]=array_sum(array_map('ipd_balance',$reports));
    foreach($monthlyDays as $m=>$values)if($values){$months[$m]['census']=array_sum($values)/count($values);$months[$m]['days']=count($values);}
    $latest=$days?array_key_last($days):null;$snapshot=$latest?$days[$latest]:[];$types=[0,0,0,0];$care=0;$hours=0;
    foreach($snapshot as $data){for($i=0;$i<4;$i++)$types[$i]+=($data['census']['general'][$i]??0)+($data['census']['isolation'][$i]??0);$care+=$data['support']['care_hours']??0;$hours+=($data['support']['nurses']??0)*($data['support']['hours']??0);}
    return compact('months','admit','discharge','deaths','latest','snapshot','types','care','hours');
}
function ipd_overview_chart(array $months,array $series):string {
    $labels=['ต.ค.','พ.ย.','ธ.ค.','ม.ค.','ก.พ.','มี.ค.','เม.ย.','พ.ค.','มิ.ย.','ก.ค.','ส.ค.','ก.ย.'];
    $maximum=1;foreach($months as $month)foreach($series as $key=>$color)$maximum=max($maximum,$month[$key]??0);
    $maximum=ceil($maximum/4)*4;$svg='<svg class="ipdo-chart" viewBox="0 0 720 225" role="img" aria-label="กราฟรายเดือน ตุลาคมถึงกันยายน">';
    for($i=0;$i<=4;$i++){$y=180-$i*38;$svg.='<line x1="42" x2="710" y1="'.$y.'" y2="'.$y.'" stroke="#dce8f5"/><text x="35" y="'.($y+4).'" text-anchor="end">'.number_format($maximum*$i/4,0).'</text>';}
    foreach($months as $i=>$month){$x=53+$i*55;$svg.='<text x="'.($x+14).'" y="204" text-anchor="middle">'.$labels[$i].'</text>';$n=0;
        foreach($series as $key=>$color){$value=$key==='census'?$month[$key]:($month['reports']?$month[$key]:null);if($value===null){if(!$n)$svg.='<text x="'.($x+14).'" y="172" text-anchor="middle">—</text>';}
            else{$height=$value/$maximum*152;$width=count($series)===1?29:17;$bx=$x+$n*20;$svg.='<rect x="'.$bx.'" y="'.(180-$height).'" width="'.$width.'" height="'.max(1,$height).'" rx="4" fill="'.$color.'"><title>'.$labels[$i].' '.h($key).' '.number_format($value,1).'</title></rect>';if(count($series)===1)$svg.='<text x="'.($x+14).'" y="'.max(20,173-$height).'" text-anchor="middle">'.number_format($value,1).'</text>';}$n++;}}
    return $svg.'</svg>';
}
function ipd_dashboard(array $user):never {
    $wards=['ipd_male'=>'หอผู้ป่วยชาย','ipd_female'=>'หอผู้ป่วยหญิง'];
    if($user['unit']!=='all'&&!isset($wards[$user['unit']])){http_response_code(403);screen('ไม่มีสิทธิ์เข้าถึง IPD','<a href="/">กลับหน้าหลัก</a>');}
    if($user['unit']!=='all')$wards=array_intersect_key($wards,[$user['unit']=>true]);
    $today=new DateTimeImmutable('now',new DateTimeZone('Asia/Bangkok'));$defaultYear=(int)$today->format('Y')+((int)$today->format('n')>=10?1:0)+543;
    $be=filter_var($_GET['year']??$defaultYear,FILTER_VALIDATE_INT,['options'=>['min_range'=>2444,'max_range'=>2743]]);
    $unit=(string)($_GET['unit']??'all');$shift=(string)($_GET['shift']??'morning');$shifts=['morning'=>'เวรเช้า','evening'=>'เวรบ่าย','night'=>'เวรดึก'];
    if(!$be||!isset($shifts[$shift])||($unit!=='all'&&!isset($wards[$unit]))){http_response_code(422);screen('ตัวกรองไม่ถูกต้อง','<a href="/ipd-dashboard">กลับภาพรวม IPD</a>');}
    $selected=$unit==='all'?$wards:[$unit=>$wards[$unit]];$year=$be-543;$from=($year-1).'-10-01';$to=$year.'-09-30';$cutoff=min($to,$today->format('Y-m-d'));
    $params=array_keys($selected);$sql='SELECT department,report_date,shift,payload FROM reports WHERE department IN ('.implode(',',array_fill(0,count($params),'?')).') AND report_date BETWEEN ? AND ? ORDER BY report_date,shift';
    $q=db()->prepare($sql);$q->execute([...$params,$from,$cutoff]);$rows=$q->fetchAll();$d=ipd_overview_data($rows,$selected,$shift,$year);
    $census=$d['snapshot']?array_sum(array_map('ipd_balance',$d['snapshot'])):null;$complete=count($d['snapshot'])===count($selected);$totalTypes=array_sum($d['types']);
    $fmt=fn($v,$digits=0)=>$v===null?'—':number_format($v,$digits);$latest=$d['latest']?ncc_thai_date($d['latest']):'ยังไม่มีรายงาน';
    ob_start(); ?><link rel="stylesheet" href="<?=h(base_path())?>/ipd.css"><?php ipd_layout_start($user,$year,$wards,'',$today->format('Y-m-d'),$shift); ?>
    <section class="ipdo-heading"><div><span class="ipd-eyebrow">INPATIENT COMMAND CENTER</span><h1>ภาพรวมการดำเนินงาน IPD</h1><p>ข้อมูลจริงจากรายงานหอผู้ป่วยชาย / หญิง · ปีงบประมาณ <?=$be?></p></div><button class="btn" type="button" onclick="setTimeout(()=>window.print(),0)">พิมพ์ / PDF</button></section>
    <form method="get" class="ipd-filter ipdo-filter"><input type="hidden" name="route" value="/ipd-dashboard"><label>ปีงบประมาณ พ.ศ.<select name="year"><?php $years=range($defaultYear-3,$defaultYear+1);$years[]=$be;$years=array_unique($years);rsort($years);foreach($years as $y):?><option value="<?=$y?>" <?=$be===$y?'selected':''?>><?=$y?></option><?php endforeach;?></select></label><label>หน่วยงาน<select name="unit"><option value="all">ทุกหอตามสิทธิ์</option><?php foreach($wards as $key=>$label):?><option value="<?=$key?>" <?=$unit===$key?'selected':''?>><?=$label?></option><?php endforeach;?></select></label><label>เวรสำหรับยอดคงพยาบาล<select name="shift"><?php foreach($shifts as $key=>$label):?><option value="<?=$key?>" <?=$shift===$key?'selected':''?>><?=$label?></option><?php endforeach;?></select></label><button class="btn primary">แสดงข้อมูล</button></form>
    <p class="ipdo-context">ข้อมูลสะสม 1 ต.ค. <?=$be-1?> – <?=h(ncc_thai_date($cutoff))?> · คงพยาบาล: วันที่ล่าสุด <?=$latest?> / <?=$shifts[$shift]?> · มีรายงาน <?=count($d['snapshot'])?> / <?=count($selected)?> หอ<?=$complete?'':' (ข้อมูลยังไม่ครบ)'?></p>
    <div class="ipdo-stats"><?php foreach([
        ['♟','ผู้ป่วยคงพยาบาลล่าสุด',$fmt($census),'ราย',$complete?'รวมครบทุกหอที่เลือก':'รวมเฉพาะหอที่มีรายงาน','#1786e7'],
        ['▥','อัตราครองเตียง','—','%','ยังไม่ได้กำหนดจำนวนเตียง','#29b978'],
        ['⇥','รับใหม่ (Admit)',$rows?$fmt($d['admit']):'—','ราย','สะสมทุกเวรในปีงบประมาณ','#9065ed'],
        ['⌂','จำหน่าย / ย้ายออก',$rows?$fmt($d['discharge']):'—','ราย','รวมทุกเหตุจำหน่าย · ทุกเวร','#1684df'],
        ['◷','วันนอนเฉลี่ย (LOS)','—','วัน','ยังไม่มีข้อมูลวันนอนรายบุคคล','#ff963a'],
        ['◎','Productivity',$d['hours']>0?$fmt($d['care']/$d['hours']*100,1):'—','%','ภาระงาน / ชั่วโมงกำลังคน · เวรล่าสุด','#e9579a']
    ] as [$icon,$label,$value,$measure,$note,$color]):?><article class="ipdo-stat" style="--tone:<?=$color?>"><span class="ipdo-icon"><?=$icon?></span><div><h2><?=$label?></h2><strong><?=$value?><small> <?=$measure?></small></strong><p><?=$note?></p></div></article><?php endforeach;?></div>
    <div class="ipdo-charts"><section class="ipd-card"><div class="ipdo-title"><h2>▥ แนวโน้มผู้ป่วยคงพยาบาลรายเดือน</h2><span>ปีงบประมาณ <?=$be?></span></div><p class="ipd-muted">เฉลี่ยต่อวันที่มีรายงานครบทุกหอที่เลือก ใน<?=$shifts[$shift]?> · ไม่รวมเวรซ้ำ</p><?=ipd_overview_chart($d['months'],['census'=>'#238be4'])?><p class="ipdo-footnote">— = ไม่มีข้อมูลครบ · เดือนที่มีข้อมูล <?=count(array_filter($d['months'],fn($m)=>$m['days']>0))?> / 12 เดือน · อัตราครองเตียงรอข้อมูลจำนวนเตียง</p></section>
    <section class="ipd-card"><div class="ipdo-title"><h2>◉ สัดส่วนผู้ป่วยตามประเภท</h2></div><p class="ipd-muted">ยอดคงพยาบาลวันล่าสุด · <?=$latest?> / <?=$shifts[$shift]?></p><div class="ipdo-mix"><?php $colors=['#1684e8','#29b978','#ffca32','#ed669f'];$stops=[];$pos=0;foreach($d['types'] as $i=>$n){$end=$pos+($totalTypes?$n/$totalTypes*100:0);$stops[]=$colors[$i].' '.$pos.'% '.$end.'%';$pos=$end;}?><div class="ipdo-donut" role="img" aria-label="จำนวนผู้ป่วยตามประเภท <?=$totalTypes?> ราย" style="background:<?=$totalTypes?'conic-gradient('.implode(',',$stops).')':'#e5edf6'?>"><div><span>ผู้ป่วยทั้งหมด</span><strong><?=$fmt($census)?></strong><small>ราย · เวรล่าสุด</small></div></div><div class="ipdo-legend"><?php foreach($d['types'] as $i=>$n):?><p><i style="background:<?=$colors[$i]?>"></i><span>ประเภท <?=$i+1?></span><strong><?=$census!==null?$n:'—'?> ราย</strong><small><?=$totalTypes?$fmt($n/$totalTypes*100,1).' %':'—'?></small></p><?php endforeach;?></div></div></section></div>
    <div class="ipdo-bottom"><section class="ipd-card"><div class="ipdo-title"><h2>⇄ การรับ–จำหน่ายรายเดือน</h2></div><div class="ipdo-chart-legend"><span>● รับใหม่</span><span>● จำหน่าย / ย้ายออก</span></div><?=ipd_overview_chart($d['months'],['admit'=>'#29b978','discharge'=>'#1684e8'])?><p class="ipdo-footnote">รวมทุกเวร · ไม่นับยอดยกมาเป็นรับใหม่ · จำหน่ายรวมส่งต่อ ย้ายออก ไม่สมัครใจ เสียชีวิต และกลับบ้าน</p></section>
    <section class="ipd-card"><div class="ipdo-title"><h2>♡ คุณภาพและการดูแล</h2></div><div class="ipdo-quality"><?php foreach(['เสียชีวิตสะสม'=>[$rows?$fmt($d['deaths']):'—','ราย'],'พลัดตกหกล้ม'=>['—','ยังไม่มีช่องบันทึก IPD'],'แผลกดทับ ≥ 2'=>['—','ยังไม่มีช่องบันทึก IPD'],'Medication Error'=>['—','ยังไม่มีช่องบันทึก IPD']] as $label=>[$value,$note]):?><div><span><?=$label?></span><strong><?=$value?></strong><small><?=$note?></small></div><?php endforeach;?></div><p class="ipdo-footnote">ไม่มีการสร้างตัวเลขตัวอย่างหรือสรุปผ่านเกณฑ์เมื่อข้อมูลไม่ครบ</p></section>
    <section class="ipd-card"><div class="ipdo-title"><h2>▦ สรุปหอผู้ป่วย</h2></div><div class="ipd-table-wrap"><table class="ipdo-wards"><thead><tr><th>หน่วยงาน</th><th>คงพยาบาล</th><th>เตียง / ครองเตียง</th></tr></thead><tbody><?php foreach($selected as $key=>$label):$r=$d['snapshot'][$key]??null;?><tr><th><a href="/ipd?unit=<?=$key?>&amp;date=<?=h($d['latest']??$today->format('Y-m-d'))?>&amp;shift=<?=$shift?>"><?=h($label)?> ↗</a></th><td><?=$r?ipd_balance($r):'—'?></td><td>— / —</td></tr><?php endforeach;?></tbody></table></div><p class="ipdo-footnote">คลิกชื่อหอเพื่อเปิดรายงาน · จำนวนเตียงและอัตราครองเตียงยังไม่กำหนด</p><div class="ipdo-record-links"><?php foreach($selected as $key=>$label):?><a class="btn" href="/ipd?unit=<?=$key?>">บันทึก<?=h($label)?> →</a><?php endforeach;?></div></section></div>
    <div class="footer-note">ข้อมูลรวมตามสิทธิ์ผู้ใช้ · ฐานข้อมูลกลาง MySQL · <?=count($rows)?> รายงานในช่วงที่เลือก</div></main></div><button type="button" class="mobile-menu" id="mobileBtn" aria-label="เปิดเมนู" aria-controls="sidebar" aria-expanded="false">☰</button><script src="<?=h(base_path())?>/ipd.js"></script>
    <?php screen('ภาพรวม IPD',ob_get_clean(),true);
}
