<?php
$db = new PDO('sqlite:local_security.sqlite');
$db->exec('CREATE TABLE IF NOT EXISTS cron_history (id INTEGER PRIMARY KEY AUTOINCREMENT, run_key TEXT UNIQUE, run_time DATETIME DEFAULT CURRENT_TIMESTAMP)');
echo "Table created.\n";
