<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMedicalRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('medical_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_id')->unique();
            $table->foreignId('user_id')->constrained();
            $table->string('department');
            $table->date('start_date');
            $table->date('end_date');
            $table->date('target_date');
            $table->string('type');
            $table->text('columns');
            $table->string('status')->default('Pending');
            $table->string('file_url')->nullable();
            $table->text('details')->nullable();
            $table->text('purpose')->nullable();
            $table->string('requester_name');
            $table->string('contact_number');
            $table->string('fiscal_year');
            $table->string('delivery_channel');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('medical_requests');
    }
}
