<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('telegram_notification_schedules', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 30)->unique();
            $table->string('label');
            $table->time('notify_at');
            $table->boolean('enabled')->default(true);
            $table->date('last_sent_date')->nullable();
            $table->timestamp('last_sent_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_notification_schedules');
    }
};
