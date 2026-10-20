<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('meal_order_batches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ward_id')->constrained()->cascadeOnDelete();
            $table->date('service_date');
            $table->string('cutoff_time', 10)->nullable();
            $table->string('status', 30)->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('kitchen_accepted_at')->nullable();
            $table->json('notes')->nullable();
            $table->timestamps();
            $table->unique(['ward_id', 'service_date']);
        });

        Schema::create('meal_orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('meal_order_batch_id')->nullable()->constrained('meal_order_batches')->nullOnDelete();
            $table->foreignId('patient_id')->constrained('patients_cache')->cascadeOnDelete();
            $table->foreignId('admission_id')->nullable()->constrained('admissions')->nullOnDelete();
            $table->foreignId('ward_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('bed_id')->nullable()->constrained()->nullOnDelete();
            $table->date('meal_date');
            $table->string('status', 30)->default('draft');
            $table->foreignId('ordered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('change_reason')->nullable();
            $table->string('source', 50)->default('manual');
            $table->boolean('is_stat')->default(false);
            $table->boolean('npo_locked')->default(false);
            $table->timestamps();
            $table->index(['meal_date', 'ward_id']);
        });

        Schema::create('meal_order_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('meal_order_id')->constrained()->cascadeOnDelete();
            $table->string('meal_type', 20);
            $table->foreignId('diet_template_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('diet_type_id')->nullable()->constrained()->nullOnDelete();
            $table->string('menu_item_name');
            $table->string('portion', 50)->nullable();
            $table->string('status', 30)->default('draft');
            $table->json('notes')->nullable();
            $table->timestamps();
            $table->unique(['meal_order_id', 'meal_type']);
        });

        Schema::create('meal_order_changes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('meal_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('change_type', 50);
            $table->json('before_payload')->nullable();
            $table->json('after_payload')->nullable();
            $table->text('reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meal_order_changes');
        Schema::dropIfExists('meal_order_items');
        Schema::dropIfExists('meal_orders');
        Schema::dropIfExists('meal_order_batches');
    }
};
