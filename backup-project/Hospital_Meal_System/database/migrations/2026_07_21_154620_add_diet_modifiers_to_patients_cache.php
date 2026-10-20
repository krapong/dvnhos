<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('patients_cache', function (Blueprint $table) {
            $table->string('specific_group_diet')->nullable()->after('therapeutic_diet');
            $table->string('food_allergy')->nullable()->after('specific_group_diet');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patients_cache', function (Blueprint $table) {
            $table->dropColumn(['specific_group_diet', 'food_allergy']);
        });
    }
};
