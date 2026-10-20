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
        Schema::create('alerts', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('box_id')->nullable();
            $table->string('box_name')->nullable();
            $table->string('location_name')->nullable();
            $table->string('drug_name')->nullable();
            $table->integer('quantity')->nullable();
            $table->string('unit')->nullable();
            $table->string('severity')->nullable();
            $table->string('status')->nullable();
            $table->string('category')->nullable();
            $table->text('description')->nullable();
            $table->date('expiry_date')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};
