<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('boxes', 'deleted_at')) {
            Schema::table('boxes', function (Blueprint $table) {
                $table->timestamp('deleted_at')->nullable()->after('updated_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('boxes', 'deleted_at')) {
            Schema::table('boxes', function (Blueprint $table) {
                $table->dropColumn('deleted_at');
            });
        }
    }
};
