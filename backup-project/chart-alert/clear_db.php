<?php
$local_pdo = new PDO('sqlite:d:/backup project/chart-alert/local_security.sqlite');
$local_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$local_pdo->exec("DELETE FROM dashboard_data");
echo "Cleared dashboard_data\n";
