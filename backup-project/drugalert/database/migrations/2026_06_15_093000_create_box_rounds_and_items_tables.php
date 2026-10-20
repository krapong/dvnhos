<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('box_rounds')) {
            Schema::create('box_rounds', function (Blueprint $table) {
                $table->id();
                $table->string('box_id');
                $table->integer('round_number')->default(1);
                $table->string('status')->default('active');
                $table->string('created_by')->nullable();
                $table->integer('items_count')->default(0);
                $table->integer('used_count')->default(0);
                $table->decimal('used_value', 10, 2)->default(0.00);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('box_round_items')) {
            Schema::create('box_round_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('box_round_id');
                $table->string('drug_id');
                $table->string('drug_name');
                $table->string('lot_number');
                $table->integer('quantity');
                $table->integer('used_quantity')->default(0);
                $table->string('expiry_date');
                $table->integer('alert_days')->default(30);
                $table->timestamps();

                $table->foreign('box_round_id')->references('id')->on('box_rounds')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('box_round_items');
        Schema::dropIfExists('box_rounds');
    }
};
