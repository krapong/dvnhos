<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require __DIR__.'/database.php';
try {
    db()->exec(file_get_contents(__DIR__.'/schema.sql'));
    echo "MySQL schema installed.\n";
    if(in_array('--migrate-sqlite',$argv,true)) {
        $source=__DIR__.'/.private/users.sqlite';
        if(!is_file($source))throw new RuntimeException('SQLite source not found.');
        if((int)db()->query('SELECT COUNT(*) FROM users')->fetchColumn()>0)throw new RuntimeException('Migration refused: destination users table must be empty. Existing accounts were not changed.');
        $old=new PDO('sqlite:'.$source,null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
        $rows=$old->query('SELECT id,username,name,password,role,unit,active,must_change FROM users')->fetchAll(PDO::FETCH_ASSOC);
        db()->beginTransaction();
        $insert=db()->prepare('INSERT INTO users(id,username,name,password,role,unit,active,must_change) VALUES(?,?,?,?,?,?,?,?)');
        foreach($rows as $row)$insert->execute(array_values($row));
        if((int)db()->query('SELECT COUNT(*) FROM users')->fetchColumn()!==count($rows))throw new RuntimeException('Migration count mismatch.');
        db()->commit();
        echo 'Migrated '.count($rows)." users with original IDs, password hashes and roles. SQLite source retained.\n";
    }
} catch(Throwable $e) {
    try { if(db()->inTransaction())db()->rollBack(); } catch(Throwable $ignored) {}
    fwrite(STDERR,'Installation failed: '.$e->getMessage().PHP_EOL);exit(1);
}
