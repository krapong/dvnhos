<?php
const MANUAL_PREFIX = "<?php http_response_code(404); exit; ?>\n";
function supplementConfig(): array {
    $path=__DIR__.'/supplement.config.php';
    if(!is_file($path))throw new RuntimeException('Configure supplement.config.php before using supplementary data');
    $cfg=require $path;
    if(!is_array($cfg)||empty($cfg['host'])||empty($cfg['username'])||!preg_match('/\A[a-zA-Z0-9_]+\z/',$cfg['database']??''))throw new RuntimeException('Supplementary database connection is not configured');
    $hos=require __DIR__.'/config.php';
    $localHosts=['localhost','127.0.0.1','::1'];
    $sameHost=$cfg['host']===$hos['db_host']||(in_array($cfg['host'],$localHosts,true)&&in_array($hos['db_host'],$localHosts,true));
    if($sameHost&&(int)($cfg['port']??3306)===(int)$hos['db_port']&&strcasecmp($cfg['database'],$hos['db_name'])===0)throw new RuntimeException('Supplementary database must be separate from HOSxP');
    return $cfg;
}
function supplementConnection(bool $selectDatabase=true): PDO {
    $cfg=supplementConfig();
    $pdo=new PDO(sprintf('mysql:host=%s;port=%d;%scharset=utf8mb4',$cfg['host'],$cfg['port']??3306,$selectDatabase?'dbname='.$cfg['database'].';':''),$cfg['username'],$cfg['password'],[
        PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false,PDO::ATTR_TIMEOUT=>5]);
    $pdo->exec('SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci');
    return $pdo;
}
function supplementLoad(PDO $db): array {
    $data=[];
    foreach($db->query('SELECT record_id,version FROM supplement_records') as $r)$data[$r['record_id']]=['version'=>(int)$r['version'],'fields'=>[],'audit'=>[]];
    foreach($db->query('SELECT * FROM supplement_fields') as $r)$data[$r['record_id']]['fields'][$r['field_name']]=['value'=>$r['field_value'],'by'=>$r['updated_by'],'at'=>$r['updated_at']];
    foreach($db->query('SELECT * FROM supplement_audit ORDER BY id') as $r)$data[$r['record_id']]['audit'][]=['field'=>$r['field_name'],'before'=>$r['before_value'],'after'=>$r['after_value'],'by'=>$r['changed_by'],'at'=>$r['changed_at']];
    return $data;
}
function supplementPersist(PDO $db,array $before,array $after): void {
    $record=$db->prepare('INSERT INTO supplement_records (record_id,version) VALUES (?,?) ON DUPLICATE KEY UPDATE version=VALUES(version)');
    $remove=$db->prepare('DELETE FROM supplement_fields WHERE record_id=?');
    $field=$db->prepare('INSERT INTO supplement_fields (record_id,field_name,field_value,updated_by,updated_at) VALUES (?,?,?,?,?)');
    $audit=$db->prepare('INSERT INTO supplement_audit (record_id,field_name,before_value,after_value,changed_by,changed_at) VALUES (?,?,?,?,?,?)');
    if(array_diff_key($before,$after))throw new RuntimeException('Deleting supplementary records is not supported');
    foreach($after as $id=>$entry){
        $old=$before[$id]??['audit'=>[]];
        if($entry===$old)continue;
        if(array_slice($entry['audit'],0,count($old['audit']))!==$old['audit'])throw new RuntimeException('Audit history cannot be overwritten');
        $record->execute([$id,$entry['version']]);$remove->execute([$id]);
        foreach($entry['fields'] as $name=>$f)$field->execute([$id,$name,$f['value'],$f['by'],$f['at']]);
        foreach(array_slice($entry['audit'],count($old['audit'])) as $a)$audit->execute([$id,$a['field'],$a['before'],$a['after'],$a['by'],$a['at']]);
    }
}
function manualStore(callable $callback,bool $write=false): mixed {
    $db=supplementConnection();
    $db->exec('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
    $db->beginTransaction();
    try {
        if($write){
            $lock=$db->query("SELECT meta_value FROM supplement_meta WHERE meta_key='write_lock' FOR UPDATE")->fetchColumn();
            if($lock===false)throw new RuntimeException('Run tools/setup-supplement.php first');
        }
        $data=supplementLoad($db);$before=$data;
        $result=$callback($data);
        if($write)supplementPersist($db,$before,$data);
        $db->commit();return $result;
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
}
function manualMerge(array $row,array $stored): array {
    $entry=$stored[$row['_record_id']]??[];$row['_manual']=[];$row['_version']=$entry['version']??0;
    $row['_source']=[];
    foreach(['G','TP','Pre','A','L','ชื่อผู้ทำคลอด','รหัสผู้ทำคลอด (ทารก)','เพศทารก'] as $key)if(array_key_exists($key,$row))$row['_source'][$key]=$row[$key];
    foreach($entry['fields']??[] as $key=>$field)if(array_key_exists($key,$row)&&(reportAllowsOverride($key)||reportMissing($row[$key]))){
        $row[$key]=$field['value'];$row['_manual'][$key]=['by'=>$field['by'],'at'=>$field['at']];
    }
    return $row;
}
