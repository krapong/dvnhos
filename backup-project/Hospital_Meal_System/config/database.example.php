<?php

return [
    'default' => env('DB_CONNECTION', 'mysql'),
    'connections' => [
        'sqlite' => [
            'driver' => 'sqlite',
            'url' => env('DATABASE_URL'),
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix' => '',
            'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
        ],
        'mysql' => [
            'driver' => 'mysql',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'CHANGE_ME_BEFORE_USE'),
            'username' => env('DB_USERNAME', 'CHANGE_ME_BEFORE_USE'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],
        'hosxp' => [
            'driver' => 'mysql',
            'url' => env('HOSXP_DATABASE_URL'),
            'host' => env('HOSXP_HOST', '127.0.0.1'),
            'port' => env('HOSXP_PORT', '3306'),
            'database' => env('HOSXP_DATABASE', 'hosxp'),
            'username' => env('HOSXP_USERNAME', 'readonly_user'),
            'password' => env('HOSXP_PASSWORD', ''),
            'unix_socket' => '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'read' => [
                'host' => env('HOSXP_HOST', '127.0.0.1'),
            ],
            'write' => [
                'host' => env('HOSXP_HOST', '127.0.0.1'),
            ],
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
                PDO::ATTR_TIMEOUT => env('HOSXP_CONNECT_TIMEOUT', 5),
            ]) : [],
        ],
    ],
    'migrations' => 'migrations',
    'redis' => [
        'client' => env('REDIS_CLIENT', 'phpredis'),
    ],
];
