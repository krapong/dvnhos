<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('setting_group', 50)->default('general');
            $table->string('setting_key', 100)->unique();
            $table->longText('setting_value')->nullable();
            $table->boolean('is_public')->default(false);
            $table->timestamps();

            $table->index(['setting_group', 'setting_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
