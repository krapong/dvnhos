<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('alert_rules', function (Blueprint $table) {
            $table->id();
            $table->string('scope', 40)->default('warehouse');
            $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('drug_id')->nullable()->constrained()->nullOnDelete();
            $table->string('alert_type', 40);
            $table->decimal('threshold_qty', 18, 3)->default(0);
            $table->unsignedInteger('expiry_days')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->boolean('notify_telegram')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['scope', 'alert_type', 'is_enabled']);
            $table->index(['warehouse_id', 'location_id', 'drug_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alert_rules');
    }
};
