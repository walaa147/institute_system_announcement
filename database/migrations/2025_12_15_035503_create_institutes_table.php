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
    Schema::create('institutes', function (Blueprint $table) {
        $table->id();
        $table->string('name_ar', 255); // اسم المعهد بالعربي
        $table->string('name_en', 255)->nullable(); // اسم المعهد بالانجليزي
        $table->text('description')->nullable();
        $table->string('address', 255)->nullable(); // تم تغييره لـ string لأنه أنسب للعنوان
        $table->string('photo_path', 2048)->nullable(); // مسار الصورة يفضل أن يكون أطول قليلاً

        // إضافات اختيارية مهمة للمعهد
        $table->string('phone', 20)->nullable(); // رقم التواصل
        $table->string('email', 100)->nullable(); // ايميل المعهد الرسمي

        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('institutes');
    }
};
