<?php
/**
 * DVHospital Database Backup Utility
 * Generates a clean, production-ready SQL dump file of the MySQL database.
 * Supports UTF-8 (Thai characters) and handles foreign key constraints.
 */

// Define paths
$rootPath = dirname(__DIR__);
$envPath = $rootPath . '/.env';
$outputPath = $rootPath . '/database/hospital_db_backup.sql';

echo "--- Starting Database Backup Script ---\n";

// 1. Parse .env file
if (!file_exists($envPath)) {
    die("Error: .env file not found at: $envPath\n");
}

echo "Reading .env configuration...\n";
$envVars = [];
$lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($lines as $line) {
    if (strpos(trim($line), '#') === 0) continue;
    $parts = explode('=', $line, 2);
    if (count($parts) === 2) {
        $key = trim($parts[0]);
        $value = trim($parts[1]);
        // Strip quotes
        if (preg_match('/^"临*(.*?)"临*$/', $value, $matches) || preg_match('/^\'临*(.*?)\'临*$/', $value, $matches)) {
            $value = $matches[1];
        } else {
            $value = trim($value, '"\'');
        }
        $envVars[$key] = $value;
    }
}

// Extract database configurations
$dbConnection = $envVars['DB_CONNECTION'] ?? 'mysql';
$dbHost = $envVars['DB_HOST'] ?? '127.0.0.1';
$dbPort = $envVars['DB_PORT'] ?? '3306';
$dbName = $envVars['DB_DATABASE'] ?? 'hospital_db';
$dbUser = $envVars['DB_USERNAME'] ?? 'root';
$dbPass = $envVars['DB_PASSWORD'] ?? '';

if ($dbConnection !== 'mysql') {
    die("Error: This script is only optimized for MySQL connections. Current driver: $dbConnection\n");
}

echo "Connecting to database: $dbName at $dbHost:$dbPort as $dbUser...\n";

try {
    // 2. Establish PDO connection
    $dsn = "mysql:host=$dbHost;port=$dbPort;dbname=$dbName;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ];
    $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
    echo "Connection successful!\n";

    // 3. Initialize output stream
    $fileHandle = fopen($outputPath, 'w');
    if (!$fileHandle) {
        die("Error: Cannot open $outputPath for writing.\n");
    }

    // Write file headers
    fwrite($fileHandle, "-- =====================================================\n");
    fwrite($fileHandle, "--   โรงพยาบาลเทพรัตนเวชชานุกูล เฉลิมพระเกียรติ ๖๐ พรรษา\n");
    fwrite($fileHandle, "--   Database Export SQL Dump (Production Ready)\n");
    fwrite($fileHandle, "--   Generated At: " . date('Y-m-d H:i:s') . "\n");
    fwrite($fileHandle, "-- =====================================================\n\n");

    fwrite($fileHandle, "SET FOREIGN_KEY_CHECKS=0;\n");
    fwrite($fileHandle, "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n");
    fwrite($fileHandle, "SET time_zone = \"+07:00\";\n\n");
    fwrite($fileHandle, "/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;\n");
    fwrite($fileHandle, "/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;\n");
    fwrite($fileHandle, "/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;\n");
    fwrite($fileHandle, "/*!40101 SET NAMES utf8mb4 */;\n\n");

    // 4. Retrieve all tables
    $tables = [];
    $stmt = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        $tables[] = $row[0];
    }

    echo "Found " . count($tables) . " tables to dump.\n";

    // 5. Dump table structures and data
    foreach ($tables as $table) {
        echo "Dumping table: $table...\n";
        
        fwrite($fileHandle, "-- -----------------------------------------------------\n");
        fwrite($fileHandle, "-- Table structure for `$table`\n");
        fwrite($fileHandle, "-- -----------------------------------------------------\n");
        
        // Drop table statement
        fwrite($fileHandle, "DROP TABLE IF EXISTS `$table`;\n");
        
        // Create table statement
        $createStmt = $pdo->query("SHOW CREATE TABLE `$table`")->fetch();
        $createSql = $createStmt['Create Table'];
        fwrite($fileHandle, $createSql . ";\n\n");

        // Dump table data
        fwrite($fileHandle, "-- -----------------------------------------------------\n");
        fwrite($fileHandle, "-- Dumping data for table `$table`\n");
        fwrite($fileHandle, "-- -----------------------------------------------------\n");

        $dataStmt = $pdo->query("SELECT * FROM `$table`");
        $rowCount = 0;
        
        // Write INSERTS in chunks to prevent huge queries
        $batchSize = 100;
        $batchRows = [];
        $columns = null;

        while ($row = $dataStmt->fetch(PDO::FETCH_ASSOC)) {
            if ($columns === null) {
                $columns = array_keys($row);
            }
            
            $values = [];
            foreach ($row as $col => $val) {
                if ($val === null) {
                    $values[] = "NULL";
                } else {
                    // Escape special characters and wraps in quotes
                    $escaped = str_replace(
                        ["\\", "\0", "\n", "\r", "'", '"', "\x1a"],
                        ["\\\\", "\\0", "\\n", "\\r", "\\'", '\\"', "\\Z"],
                        $val
                    );
                    $values[] = "'" . $escaped . "'";
                }
            }
            $batchRows[] = "(" . implode(", ", $values) . ")";
            $rowCount++;

            if (count($batchRows) >= $batchSize) {
                // Write batch insert
                $escapedCols = array_map(function($col) { return "`$col`"; }, $columns);
                $insertSql = "INSERT INTO `$table` (" . implode(", ", $escapedCols) . ") VALUES \n" . implode(",\n", $batchRows) . ";\n";
                fwrite($fileHandle, $insertSql);
                $batchRows = [];
            }
        }

        // Write remaining rows in batch
        if (count($batchRows) > 0 && $columns !== null) {
            $escapedCols = array_map(function($col) { return "`$col`"; }, $columns);
            $insertSql = "INSERT INTO `$table` (" . implode(", ", $escapedCols) . ") VALUES \n" . implode(",\n", $batchRows) . ";\n";
            fwrite($fileHandle, $insertSql);
        }

        fwrite($fileHandle, "\n-- Table `$table` row count: $rowCount\n\n");
        echo "Finished table: $table ($rowCount rows)\n";
    }

    // Write footer settings
    fwrite($fileHandle, "SET FOREIGN_KEY_CHECKS=1;\n\n");
    fwrite($fileHandle, "/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;\n");
    fwrite($fileHandle, "/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;\n");
    fwrite($fileHandle, "/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;\n");

    fclose($fileHandle);
    echo "\nSUCCESS! Database dump written successfully to:\n$outputPath\n";
    echo "Dump size: " . number_format(filesize($outputPath) / 1024, 2) . " KB\n";

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage() . "\n");
} catch (Exception $e) {
    die("Error: " . $e->getMessage() . "\n");
}
