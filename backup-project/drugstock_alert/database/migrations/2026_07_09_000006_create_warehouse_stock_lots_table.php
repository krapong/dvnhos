<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('warehouse_stock_lots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_stock_id')->constrained()->cascadeOnDelete();
            $table->string('lot_no', 120);
            $table->date('expiry_date')->nullable();
            $table->decimal('qty_on_hand', 18, 3)->default(0);
            $table->decimal('qty_reserved', 18, 3)->default(0);
            $table->decimal('unit_cost', 18, 2)->default(0);
            $table->date('received_date')->nullable();
            $table->string('status', 40)->default('active');
            $table->timestamps();

            $table->unique(['warehouse_stock_id', 'lot_no', 'expiry_date'], 'stock_lot_unique');
            $table->index(['expiry_date', 'qty_on_hand']);
            $table->index(['status', 'qty_on_hand']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_stock_lots');
    }
};
