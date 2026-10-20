<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('stock_movement_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_movement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('drug_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_stock_lot_id')->nullable()->constrained('warehouse_stock_lots')->nullOnDelete();
            $table->string('lot_no', 120)->nullable();
            $table->date('expiry_date')->nullable();
            $table->decimal('quantity', 18, 3);
            $table->decimal('unit_cost', 18, 2)->default(0);
            $table->decimal('line_total', 18, 2)->default(0);
            $table->string('reason', 120)->nullable();
            $table->timestamps();

            $table->index(['stock_movement_id', 'drug_id']);
            $table->index(['drug_id', 'expiry_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movement_items');
    }
};
