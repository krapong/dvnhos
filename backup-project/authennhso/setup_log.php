<?php
require_once __DIR__ . '/config.php';
try {
    $pdo = getDbConnection();
    $sql = "CREATE TABLE IF NOT EXISTS `login_history` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `provider_id` varchar(20) NOT NULL,
        `fullname` varchar(100) NOT NULL,
        `ip_address` varchar(50) NOT NULL,
        `user_agent` text,
        `status` varchar(50) NOT NULL,
        `login_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_provider_id` (`provider_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    $pdo->exec($sql);
    echo "Success";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
