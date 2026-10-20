<?php
$local_pdo = new PDO('sqlite:' . __DIR__ . '/local_security.sqlite');
$local_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$stmt = $local_pdo->query("SELECT * FROM notify_schedule");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
