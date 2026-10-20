<?php
require 'db_config.php';
$stmt = $pdo->query("SELECT TABLE_NAME, COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE COLUMN_NAME LIKE '%diag%' AND TABLE_SCHEMA = DATABASE()");
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($results);
