<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require __DIR__.'/database.php';
$pdo=db();$output=__DIR__.'/.private/nursing-center-mysql.sql';
if(!is_dir(dirname($output)))mkdir(dirname($output),0700,true);
$stream=fopen($output.'.tmp','wb');if(!$stream)throw new RuntimeException('Cannot create backup.');
$pdo->exec('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');$pdo->beginTransaction();
try {
    fwrite($stream,"-- Nursing Command Center MySQL backup: import into an EMPTY database only.\nSET NAMES utf8mb4;\nSET time_zone = '+00:00';\n");
    foreach(['users','attempts','reports','report_history'] as $table) {
        $create=$pdo->query('SHOW CREATE TABLE `'.$table.'`')->fetch(PDO::FETCH_NUM)[1];
        fwrite($stream,$create.";\n");
        foreach($pdo->query('SELECT * FROM `'.$table.'`') as $row) {
            $columns=implode(',',array_map(fn($key)=>'`'.$key.'`',array_keys($row)));
            $values=implode(',',array_map(fn($value)=>$value===null?'NULL':$pdo->quote((string)$value),array_values($row)));
            fwrite($stream,'INSERT INTO `'.$table.'` ('.$columns.') VALUES ('.$values.");\n");
        }
    }
    $pdo->commit();fclose($stream);rename($output.'.tmp',$output);
    echo "Backup ready: .private/nursing-center-mysql.sql (contains account hashes and reports; keep private).\n";
} catch(Throwable $e) { if($pdo->inTransaction())$pdo->rollBack();fclose($stream);throw $e; }
