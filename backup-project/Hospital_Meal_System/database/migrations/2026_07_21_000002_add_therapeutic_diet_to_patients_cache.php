<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('patients_cache', function (Blueprint $table): void {
            $table->string('therapeutic_diet')->nullable()->after('diet_order');
        });
    }

    public function down(): void
    {
        Schema::table('patients_cache', function (Blueprint $table): void {
            $table->dropColumn('therapeutic_diet');
        });
    }
};
