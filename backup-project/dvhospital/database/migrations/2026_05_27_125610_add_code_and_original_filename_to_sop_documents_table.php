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
        Schema::table('sop_documents', function (Blueprint $table) {
            $table->string('code')->nullable()->after('id')->comment('รหัสเอกสาร');
            $table->string('original_filename')->nullable()->after('file_path')->comment('ชื่อไฟล์ต้นฉบับ');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sop_documents', function (Blueprint $table) {
            $table->dropColumn(['code', 'original_filename']);
        });
    }
};
