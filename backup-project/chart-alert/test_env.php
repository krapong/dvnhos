<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>System Check</h1>";

echo "<h2>1. PHP Version</h2>";
echo "PHP Version: " . phpversion() . "<br>";

echo "<h2>2. Database Extensions</h2>";
$extensions = ['pdo', 'pdo_mysql', 'pdo_sqlite', 'curl'];
foreach ($extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "<span style='color:green;'>[OK] $ext is loaded.</span><br>";
    } else {
        echo "<span style='color:red;'>[ERROR] $ext is NOT loaded.</span><br>";
    }
}

echo "<h2>3. Folder Permissions</h2>";
$db_file = __DIR__ . '/local_security.sqlite';
if (is_writable(__DIR__)) {
    echo "<span style='color:green;'>[OK] Folder is writable.</span><br>";
} else {
    echo "<span style='color:red;'>[ERROR] Folder is NOT writable.</span><br>";
}

if (file_exists($db_file)) {
    if (is_writable($db_file)) {
        echo "<span style='color:green;'>[OK] local_security.sqlite is writable.</span><br>";
    } else {
        echo "<span style='color:red;'>[ERROR] local_security.sqlite is NOT writable.</span><br>";
    }
} else {
    echo "local_security.sqlite does not exist yet.<br>";
}

echo "<h2>4. File Check</h2>";
echo "Checking if files exist:<br>";
$files = ['login.php', 'auth.php', 'db_config.php', 'local_db.php'];
foreach ($files as $file) {
    if (file_exists(__DIR__ . '/' . $file)) {
        echo "<span style='color:green;'>[OK] $file exists.</span><br>";
    } else {
        echo "<span style='color:red;'>[ERROR] $file is missing.</span><br>";
    }
}
?>
