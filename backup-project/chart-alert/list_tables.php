<?php
$db = new PDO('sqlite:local_security.sqlite');
foreach($db->query("SELECT name FROM sqlite_master WHERE type='table'") as $row) {
    echo $row['name'] . "\n";
}
