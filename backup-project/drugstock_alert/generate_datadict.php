<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;

$tables = Schema::getTables();
$output = "# พจนานุกรมข้อมูล (Data Dictionary)\n\n";
$output .= "สำหรับผู้ดูแลระบบ (System Administrator)\n\n";

foreach ($tables as $tableInfo) {
    $table = $tableInfo['name'];
    if (in_array($table, ['migrations', 'failed_jobs', 'personal_access_tokens', 'password_reset_tokens', 'cache', 'cache_locks', 'jobs', 'job_batches'])) continue;
    
    $output .= "## ตาราง: `{$table}`\n\n";
    $output .= "| ชื่อคอลัมน์ (Column) | ประเภท (Type) | อนุญาตค่าว่าง (Nullable) | ค่าเริ่มต้น (Default) |\n";
    $output .= "|--------|------|----------|---------|\n";
    
    $columns = Schema::getColumns($table);
    foreach ($columns as $column) {
        $name = $column['name'];
        $type = $column['type'];
        $nullable = $column['nullable'] ? 'Yes' : 'No';
        $default = $column['default'] ?? 'NULL';
        
        $output .= "| `{$name}` | `{$type}` | {$nullable} | {$default} |\n";
    }
    $output .= "\n";
}

file_put_contents(__DIR__ . '/datadict.md', $output);
echo "Data dictionary generated to datadict.md";
