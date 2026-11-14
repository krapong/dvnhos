<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require_once dirname(__DIR__).'/manual-store.php';
try {
    $cfg=supplementConfig();$server=supplementConnection(false);
    $server->exec('CREATE DATABASE IF NOT EXISTS `'.$cfg['database'].'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $db=supplementConnection();
    foreach(explode(';',file_get_contents(dirname(__DIR__).'/database/supplement.sql')) as $sql)if(trim($sql)!=='')$db->exec($sql);
    $db->beginTransaction();
    $db->query("SELECT meta_value FROM supplement_meta WHERE meta_key='write_lock' FOR UPDATE")->fetchColumn();
    $done=$db->query("SELECT meta_value FROM supplement_meta WHERE meta_key='legacy_imported'")->fetchColumn();
    $imported=0;
    if($done===false){
        $file=dirname(__DIR__).'/.private/supplement.php';$legacy=[];
        if(is_file($file)){
            $raw=file_get_contents($file);
            if($raw!==''){
                if(!str_starts_with($raw,MANUAL_PREFIX))throw new RuntimeException('Invalid legacy store; migration stopped');
                $legacy=json_decode(substr($raw,strlen(MANUAL_PREFIX)),true,512,JSON_THROW_ON_ERROR);
                if(!is_array($legacy))throw new RuntimeException('Invalid legacy data');
            }
        }
        $existing=supplementLoad($db);
        foreach($legacy as $id=>$entry){
            if(isset($existing[$id])&&$existing[$id]!=$entry)throw new RuntimeException('Legacy record conflicts with database; migration stopped');
        }
        supplementPersist($db,$existing,$existing+$legacy);$imported=count($legacy);
        $db->exec("INSERT INTO supplement_meta (meta_key,meta_value) VALUES ('legacy_imported','1')");
    }
    $db->commit();echo 'Supplementary schema ready. Imported records: '.$imported.PHP_EOL;
} catch(Throwable $e){if(isset($db)&&$db->inTransaction())$db->rollBack();fwrite(STDERR,'Supplementary database setup failed; code: '.$e->getCode().PHP_EOL);exit(1);}
