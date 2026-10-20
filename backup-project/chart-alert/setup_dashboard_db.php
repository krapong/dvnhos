<?php
$db = new PDO('sqlite:local_security.sqlite');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Create table for storing dashboard json
$sql = "CREATE TABLE IF NOT EXISTS dashboard_data (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    upload_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    json_data TEXT
)";
$db->exec($sql);

echo "Dashboard data table created successfully.\n";
