<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // หน้าเพจคงที่ เช่น เกี่ยวกับเรา, ITA, ชมรมจริยธรรม, OIT
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('section')->nullable(); // about, ita, ethics, oit, services
            $table->longText('content');
            $table->string('featured_image')->nullable();
            $table->longText('attachments')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->integer('user_id');
            $table->timestamps();
        });

        // สำหรับผู้บริหาร / โครงสร้างองค์กร
        Schema::create('executives', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('title'); // ตำแหน่ง
            $table->string('position_level')->nullable(); // ระดับ: director, deputy, head
            $table->string('photo')->nullable();
            $table->text('bio')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // ปฏิทินกิจกรรม
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('location')->nullable();
            $table->datetime('start_date');
            $table->datetime('end_date')->nullable();
            $table->string('color')->default('#00695C'); // สีแสดงบนปฏิทิน
            $table->boolean('is_all_day')->default(false);
            $table->integer('user_id');
            $table->timestamps();
        });

        // Activity Log
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->nullable();
            $table->string('action'); // create, update, delete, login, logout
            $table->string('model_type')->nullable();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->text('description')->nullable();
            $table->longText('properties')->nullable(); // old/new values
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });

        // Site Settings
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('group')->default('general'); // general, contact, social, seo
            $table->string('type')->default('text'); // text, textarea, image, boolean, json
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('events');
        Schema::dropIfExists('executives');
        Schema::dropIfExists('pages');
    }
};
