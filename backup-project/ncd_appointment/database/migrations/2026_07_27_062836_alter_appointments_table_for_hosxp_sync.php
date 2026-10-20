<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->unsignedInteger('oapp_id')->nullable()->unique()->after('id');
            $table->string('hn')->nullable()->after('oapp_id');
            $table->string('vn')->nullable()->after('hn');
            $table->string('clinic_code')->nullable()->after('vn');
            $table->date('vstdate')->nullable()->after('clinic_code');
            
            // Drop foreign keys to avoid constraint errors during bulk sync
            $table->dropForeign(['patient_id']);
            $table->dropForeign(['clinic_id']);
            
            $table->unsignedBigInteger('patient_id')->nullable()->change();
            $table->unsignedBigInteger('clinic_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn(['oapp_id', 'hn', 'vn', 'clinic_code', 'vstdate']);
        });
    }
};
