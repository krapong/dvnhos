<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event_type', 80);
            $table->string('auditable_type')->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->json('before_payload')->nullable();
            $table->json('after_payload')->nullable();
            $table->string('ip_address', 64)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
            $table->index(['auditable_type', 'auditable_id']);
        });

        Schema::create('sync_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('source', 50);
            $table->string('sync_type', 50);
            $table->string('status', 30);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->json('payload')->nullable();
            $table->text('message')->nullable();
            $table->timestamps();
        });

        Schema::create('telegram_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('chat_id', 50)->nullable();
            $table->text('message');
            $table->json('payload')->nullable();
            $table->string('status', 30)->default('pending');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_logs');
        Schema::dropIfExists('sync_logs');
        Schema::dropIfExists('audit_logs');
    }
};
