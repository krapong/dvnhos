<?php
/** Explicit HOSxP links only. A child with conflicting mothers is left unresolved. */
function familyLinks(PDO $pdo,array $mothers,array $children): array {
    $ids=array_values(array_unique(array_filter(array_merge($mothers,$children),static fn($v)=>$v!==null&&$v!=='')));
    if(!$ids)return [];
    $marks=implode(',',array_fill(0,count($ids),'?'));
    $sql="SELECT DISTINCT mother_an,child_an FROM (
      SELECT mother_an,an AS child_an FROM ipt_newborn
      UNION SELECT l.an,f.infant_an FROM ipt_labour l JOIN ipt_labour_infant f ON f.ipt_labour_id=l.ipt_labour_id
    ) links WHERE mother_an IS NOT NULL AND mother_an<>'' AND child_an IS NOT NULL AND child_an<>''
    AND (mother_an IN ($marks) OR child_an IN ($marks))";
    $q=$pdo->prepare($sql);$q->execute(array_merge($ids,$ids));$candidates=[];
    foreach($q->fetchAll() as $r)$candidates[(string)$r['child_an']][(string)$r['mother_an']]=true;
    // Check all sources for each candidate child, including mothers outside the selected period.
    if(!$candidates)return [];
    $children=array_keys($candidates);$marks=implode(',',array_fill(0,count($children),'?'));
    $q=$pdo->prepare("SELECT mother_an,an AS child_an FROM ipt_newborn WHERE an IN ($marks)
      UNION SELECT l.an,f.infant_an FROM ipt_labour l JOIN ipt_labour_infant f ON f.ipt_labour_id=l.ipt_labour_id WHERE f.infant_an IN ($marks)");
    $q->execute(array_merge($children,$children));
    foreach($q->fetchAll() as $r)if($r['mother_an']!==null&&$r['mother_an']!=='')$candidates[(string)$r['child_an']][(string)$r['mother_an']]=true;
    return array_map(static fn($m)=>count($m)===1?(string)array_key_first($m):null,$candidates);
}
function familyEnrich(array $rows,array $links): array {
    foreach($rows as &$r){
        $mother=(string)$r['AN มารดา'];$children=[];
        foreach($links as $child=>$parent)if($parent===$mother)$children[]=(string)$child;
        sort($children);$r['_child_ans']=$children;
        $r['_family_status']=$children?'ยืนยันจาก AN ใน HOSxP':'ยังไม่พบ AN เชื่อมโยง';
    }unset($r);return $rows;
}
function familyNewbornAdmissions(PDO $pdo,string $from,string $to): array {
    $names=static fn($s)=>'0x'.bin2hex($s).',0x'.bin2hex(iconv('UTF-8','TIS-620',$s));
    $q=$pdo->prepare('SELECT DISTINCT i.an,i.hn,i.regdate FROM ipt i WHERE i.regdate>=:start AND i.regdate<:end'
      .' AND EXISTS (SELECT 1 FROM spclty s WHERE s.spclty=i.spclty AND CAST(s.name AS BINARY) IN ('.$names('กุมารเวชกรรม').'))'
      .' AND EXISTS (SELECT 1 FROM ipt_admit_type a WHERE a.ipt_admit_type_id=i.ipt_admit_type_id AND CAST(a.ipt_admit_type_name AS BINARY) IN ('.$names('แรกเกิด').')) ORDER BY i.regdate DESC,i.an LIMIT 10001');
    $q->execute([':start'=>$from,':end'=>(new DateTimeImmutable($to))->modify('+1 day')->format('Y-m-d')]);
    $rows=$q->fetchAll();if(count($rows)>10000)throw new RuntimeException('Too many newborn admissions');return $rows;
}
