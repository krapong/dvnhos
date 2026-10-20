<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('alert_notifications', function (Blueprint $table) {
            $table->string('dedup_key', 191)->nullable()->after('id');
            $table->unique('dedup_key');
        });
    }

    public function down(): void
    {
        Schema::table('alert_notifications', function (Blueprint $table) {
            $table->dropUnique(['dedup_key']);
            $table->dropColumn('dedup_key');
        });
    }
};
