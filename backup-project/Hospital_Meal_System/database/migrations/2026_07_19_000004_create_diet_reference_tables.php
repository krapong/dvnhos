<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('diet_types', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('diet_templates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('diet_type_id')->constrained()->cascadeOnDelete();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('target_energy_kcal', 8, 2)->nullable();
            $table->decimal('target_protein_g', 8, 2)->nullable();
            $table->decimal('target_carbohydrate_g', 8, 2)->nullable();
            $table->decimal('target_fat_g', 8, 2)->nullable();
            $table->decimal('target_sodium_mg', 8, 2)->nullable();
            $table->decimal('target_potassium_mg', 8, 2)->nullable();
            $table->decimal('target_fluid_ml', 8, 2)->nullable();
            $table->string('texture', 100)->nullable();
            $table->json('allowed_for_notes')->nullable();
            $table->json('restricted_for_notes')->nullable();
            $table->json('meal_scope')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('diet_template_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('diet_template_id')->constrained()->cascadeOnDelete();
            $table->string('item_type', 50)->nullable();
            $table->string('item_name');
            $table->string('quantity', 50)->nullable();
            $table->text('notes')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diet_template_items');
        Schema::dropIfExists('diet_templates');
        Schema::dropIfExists('diet_types');
    }
};
