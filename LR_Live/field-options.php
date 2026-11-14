<?php
$source=json_decode(file_get_contents(__DIR__.'/database/sheet-options-source.json'),true,512,JSON_THROW_ON_ERROR);
$byColumn=array_column($source['columns'],'values','column');
$mapping=[
 'สิทธิการรักษา'=>8,'สัญชาติ'=>9,'วิธีประเมินอายุครรภ์ (ตามชีต)'=>16,
 'รายการความเสี่ยง ANC'=>17,'ผล HIV บิดา (ตามชีต)'=>18,'ผล VDRL บิดา (ตามชีต)'=>19,
 'HIV มารดา ครั้งที่ 1'=>20,'HIV มารดา ครั้งที่ 2'=>21,'HBsAg มารดา'=>22,
 'VDRL มารดา ครั้งที่ 1'=>23,'VDRL มารดา ครั้งที่ 2'=>23,
 'วัคซีน dT (ตามชีต)'=>35,'วิธีคลอดทารก'=>38,'แผลฝีเย็บ (ตามชีต)'=>39,
 'ระดับแผลฝีเย็บ (ตามชีต)'=>40,'เพศทารก'=>46,'ภาวะแทรกซ้อน (ตามชีต)'=>63,
];
$result=[];foreach($mapping as $field=>$column)$result[$field]=$byColumn[$column];
$result['เพศทารก']=['ชาย','หญิง','ระบุไม่ได้'];
return $result;
