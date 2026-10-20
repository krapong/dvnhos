<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('position_title');
            $table->string('auth_provider', 30)->default('local')->after('is_active');
            $table->index(['is_active', 'auth_provider']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_is_active_auth_provider_index');
            $table->dropColumn(['is_active', 'auth_provider']);
        });
    }
};
