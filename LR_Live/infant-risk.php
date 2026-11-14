<?php
function infantRiskPredicate(string $alias='f',bool $newborn=false): string {
    $weight=$alias.'.birth_weight';$scores=$newborn?['apgar1','apgar2']:['apgar_score_min1','apgar_score_min5','apgar_score_min10'];
    $parts=["($weight>0 AND $weight<2500)"];foreach($scores as $col)$parts[]="($alias.$col IS NOT NULL AND $alias.$col>=0 AND $alias.$col<7)";
    return '('.implode(' OR ',$parts).')';
}
function infantRiskReasons(mixed $weight,array $scores): array {
    $reasons=[];$group=null;
    if(is_numeric($weight)&&(float)$weight>0&&(float)$weight<2500){$w=(float)$weight;$group=$w<1000?'ELBW <1000 g':($w<1500?'VLBW 1000–1499 g':'LBW 1500–2499 g');$reasons[]=$group;}
    foreach($scores as $minute=>$score)if($score!==null&&$score!==''&&is_numeric($score)&&(float)$score>=0&&(float)$score<7)$reasons[]='APGAR '.$minute.' นาที < 7';
    return ['reasons'=>$reasons,'weight_group'=>$group,'risk_screen'=>$reasons?'Y':'N'];
}
function newbornRiskSql(): string {
    return <<<'SQL'
SELECT n.an AS newborn_an,n.mother_an,m.hn AS mother_hn,n.born_date,n.born_time,n.birth_weight,n.apgar1,n.apgar2,
 n.birthcondition1,nc1.name AS condition1,n.birthcondition2,nc2.name AS condition2,
 n.dead,n.newborn_death_cause_id,ndc.name AS death_cause,n.note,n.doctor
FROM ipt_newborn n
LEFT JOIN ipt m ON m.an=n.mother_an
LEFT JOIN ipt_newborn_condition_type1 nc1 ON nc1.id=n.birthcondition1
LEFT JOIN ipt_newborn_condition_type2 nc2 ON nc2.id=n.birthcondition2
LEFT JOIN newborn_death_cuase ndc ON ndc.newborn_death_cause_id=n.newborn_death_cause_id
WHERE n.born_date>=:start AND n.born_date<:end AND 
SQL
    .infantRiskPredicate('n',true).' ORDER BY n.apgar2 IS NULL,n.apgar2,n.birth_weight,n.born_date DESC,n.an LIMIT 10001';
}
function infantRiskMonthlySql(): string {
    return <<<'SQL'
SELECT DATE_FORMAT(f.birth_date,'%Y-%m') AS ym,COUNT(*) AS total_births,
 SUM(f.apgar_score_min1 IS NOT NULL AND f.apgar_score_min1>=0 AND f.apgar_score_min1<7) AS apgar1_lt7,
 SUM(f.apgar_score_min5 IS NOT NULL AND f.apgar_score_min5>=0 AND f.apgar_score_min5<7) AS apgar5_lt7,
 SUM(f.apgar_score_min10 IS NOT NULL AND f.apgar_score_min10>=0 AND f.apgar_score_min10<7) AS apgar10_lt7,
 SUM(f.birth_weight>0 AND f.birth_weight<2500) AS lbw,
 SUM(f.birth_weight>0 AND f.birth_weight<1500) AS vlbw,
 SUM(f.birth_weight>0 AND f.birth_weight<1000) AS elbw,
 SUM(f.birth_weight IS NULL OR f.birth_weight<=0) AS missing_weight,
 ROUND(100*SUM(f.birth_weight>0 AND f.birth_weight<2500)/NULLIF(COUNT(*),0),2) AS lbw_percent
FROM ipt_labour_infant f WHERE f.birth_date>=:start AND f.birth_date<:end
GROUP BY ym ORDER BY ym
SQL;
}
