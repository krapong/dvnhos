<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // posts = ใช้สำหรับ ข่าวประชาสัมพันธ์, ข่าวรับสมัครงาน, ประกวดราคา/จัดซื้อจัดจ้าง, กิจกรรม
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->enum('type', ['news', 'job', 'procurement', 'activity', 'ita'])->index();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->integer('user_id');
            $table->text('excerpt')->nullable(); // ย่อหน้าสรุป
            $table->longText('content'); // เนื้อหาหลัก
            $table->string('featured_image')->nullable(); // รูปภาพหลัก
            $table->longText('attachments')->nullable(); // ไฟล์แนบ (PDF, doc)
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamp('expired_at')->nullable(); // สำหรับประกาศที่มีวันหมดอายุ
            $table->boolean('is_pinned')->default(false); // ปักหมุด
            $table->integer('view_count')->default(0);
            $table->longText('meta')->nullable(); // ข้อมูลเพิ่มเติม เช่น เงื่อนไขการจ้าง, วงเงินจัดซื้อ
            $table->timestamps();

            $table->index(['type', 'status', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
