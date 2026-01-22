<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_diploma', function (Blueprint $table) {
            $table->id();

            // الربط بجدول الدبلومات
            $table->foreignId('diploma_id')
                  ->constrained('diplomas')
                  ->onDelete('cascade'); // إذا حذف الدبلوم يحذف الارتباط تلقائياً

            // الربط بجدول الكورسات
            $table->foreignId('course_id')
                  ->constrained('courses')
                  ->onDelete('cascade'); // إذا حذف الكورس يحذف من الدبلومات المرتبط بها

            // حقل إضافي للترتيب (مهم جداً للباكند)
            // لكي يظهر ترتيب الكورسات داخل الدبلوم (مثلاً الكورس 1 ثم الكورس 2)
            $table->integer('sort_order')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_diploma');
    }
};
