<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('patients_cache', function (Blueprint $table): void {
            $table->id();
            $table->string('hn', 30)->unique();
            $table->string('an', 30)->nullable()->index();
            $table->string('prefix', 20)->nullable();
            $table->string('first_name');
            $table->string('last_name');
            $table->foreignId('ward_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('room_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('bed_id')->nullable()->constrained()->nullOnDelete();
            $table->string('sex', 10)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->decimal('weight_kg', 6, 2)->nullable();
            $table->decimal('height_cm', 6, 2)->nullable();
            $table->decimal('bmi', 6, 2)->nullable();
            $table->timestamp('bmi_taken_at')->nullable();
            $table->boolean('npo_status')->default(false);
            $table->string('diet_order')->nullable();
            $table->string('primary_diagnosis')->nullable();
            $table->string('secondary_diagnosis')->nullable();
            $table->text('clinical_note')->nullable();
            $table->timestamp('source_updated_at')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });

        Schema::create('admissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients_cache')->cascadeOnDelete();
            $table->foreignId('ward_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('room_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('bed_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('admitted_at')->nullable();
            $table->timestamp('discharged_at')->nullable();
            $table->string('status', 30)->default('admitted');
            $table->text('transfer_reason')->nullable();
            $table->foreignId('transfer_from_bed_id')->nullable()->constrained('beds')->nullOnDelete();
            $table->foreignId('transfer_to_bed_id')->nullable()->constrained('beds')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('patient_allergies', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients_cache')->cascadeOnDelete();
            $table->string('allergen');
            $table->string('severity', 50)->nullable();
            $table->string('reaction')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_allergies');
        Schema::dropIfExists('admissions');
        Schema::dropIfExists('patients_cache');
    }
};
