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
        Schema::create('ncd_weekly_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained('clinics')->cascadeOnDelete();
            $table->integer('fiscal_year');
            $table->integer('month');
            $table->integer('week_no');
            $table->integer('patient_count')->default(0);
            $table->timestamps();
            
            $table->unique(['clinic_id', 'fiscal_year', 'month', 'week_no'], 'ncd_weekly_stats_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ncd_weekly_stats');
    }
};
