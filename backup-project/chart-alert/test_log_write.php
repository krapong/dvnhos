<?php
$log_file = __DIR__ . "/cron_log_" . date('Y-m-d') . ".txt";
$res = file_put_contents($log_file, "TEST\n", FILE_APPEND);
echo "Result: " . var_export($res, true) . "\n";
