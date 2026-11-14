<?php
declare(strict_types=1);
function ncc_thai_date(?string $value,bool $withTime=false):string {
    if(!$value)return '—';
    $date=DateTimeImmutable::createFromFormat($withTime?'!Y-m-d H:i:s':'!Y-m-d',$value,new DateTimeZone('UTC'));
    $errors=DateTimeImmutable::getLastErrors();
    if(!$date||($errors&&($errors['warning_count']||$errors['error_count'])))return '—';
    if($withTime)$date=$date->setTimezone(new DateTimeZone('Asia/Bangkok'));
    $months=['ม.ค.','ก.พ.','มี.ค.','เม.ย.','พ.ค.','มิ.ย.','ก.ค.','ส.ค.','ก.ย.','ต.ค.','พ.ย.','ธ.ค.'];
    return (int)$date->format('j').' '.$months[(int)$date->format('n')-1].' '.((int)$date->format('Y')+543).($withTime?' เวลา '.$date->format('H:i').' น.':'');
}
function ncc_thai_date_field(string $id,string $value,string $name):string {
    [$year,$month,$day]=array_map('intval',explode('-',$value));
    $months=['มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน','กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม'];
    $html='<span class="thai-date-field" data-date-field="'.h($id).'"><input type="hidden" id="'.h($id).'" name="'.h($name).'" value="'.h($value).'">';
    foreach(['day'=>'วัน','month'=>'เดือน','year'=>'ปี พ.ศ.'] as $part=>$label){
        $html.='<select data-part="'.$part.'" aria-label="'.$label.'">';
        $values=$part==='day'?range(1,(int)(new DateTimeImmutable($value))->format('t')):($part==='month'?range(1,12):range(min(1900,$year),max(2100,$year)));
        $selected=['day'=>$day,'month'=>$month,'year'=>$year][$part];
        foreach($values as $n)$html.='<option value="'.$n.'"'.($selected===$n?' selected':'').'>'.($part==='month'?$months[$n-1]:($part==='year'?$n+543:$n)).'</option>';
        $html.='</select>';
    }
    return $html.'<small>พ.ศ.</small></span>';
}
