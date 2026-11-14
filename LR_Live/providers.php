<?php
function deliveryProviders(PDO $pdo,string $charset): array {
    $sql="SELECT d.code,d.name,d.active,d.provider_type_code,dp.name AS position_name FROM doctor d LEFT JOIN doctor_position dp ON dp.id=d.position_id
    WHERE d.active='Y' AND NULLIF(TRIM(d.code),'') IS NOT NULL AND NULLIF(TRIM(d.name),'') IS NOT NULL
    AND (d.position_id IN (1,4,5) OR dp.name LIKE '%พยาบาล%' OR (dp.id IS NULL AND (d.provider_type_code IN ('01','03')
      OR EXISTS (SELECT 1 FROM opduser u WHERE u.doctorcode=d.code AND (u.groupname LIKE 'พยาบาล%' OR u.groupname='แพทย์')))))
    ORDER BY d.name,d.code";
    $rows=[];foreach($pdo->query($sql) as $row){$row=reportNormalizeLinked($row,$charset);$rows[]=['code'=>(string)$row['code'],'name'=>$row['name'],'role'=>$row['position_name']??($row['provider_type_code']==='01'?'แพทย์':($row['provider_type_code']==='03'?'พยาบาล':'บุคลากร')),'active'=>$row['active']];}
    return $rows;
}
function selectedDeliveryProvider(PDO $pdo,string $charset,string $code): array {
    foreach(deliveryProviders($pdo,$charset) as $provider)if($provider['code']===$code)return $provider;
    throw new InvalidArgumentException('กรุณาเลือกผู้ทำคลอดที่เป็นแพทย์หรือพยาบาลและมีสถานะ Active ใน HOSxP');
}
