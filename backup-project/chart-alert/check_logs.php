<?php
$db = new PDO('sqlite:local_security.sqlite');
$stmt = $db->query("PRAGMA table_info(alert_logs)");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
