<?php
declare(strict_types=1);
function database_config(): array {
    $file=__DIR__.'/.private/database.php';
    $config=is_file($file)?require $file:[];
    foreach(['host','port','database','username','password'] as $key) {
        $value=getenv('NCC_DB_'.strtoupper($key));
        if($value!==false)$config[$key]=$value;
    }
    if(empty($config['database'])||empty($config['username']))throw new RuntimeException('Configure .private/database.php or NCC_DB_* environment variables.');
    return $config+['host'=>'127.0.0.1','port'=>3306,'password'=>''];
}
function db(): PDO {
    static $connection;
    if($connection)return $connection;
    $c=database_config();
    $connection=new PDO('mysql:host='.$c['host'].';port='.(int)$c['port'].';dbname='.$c['database'].';charset=utf8mb4',$c['username'],$c['password'],[
        PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false,
    ]);
    // The internal server's init_connect overrides the PDO handshake with TIS-620.
    // Explicitly set the session charset after connecting so Thai JSON round-trips.
    $connection->exec('SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci');
    $connection->exec("SET time_zone = '+00:00'");
    return $connection;
}
