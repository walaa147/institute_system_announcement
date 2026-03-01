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
        $table->string('slug', 50)->unique(); // سلاگ المعهد لاستخدامه في الروابط
         $table->string('code', 50)->unique(); // كود المعهد لاستخدامه في
          $table->string('name_ar', 255); // اسم المعهد بالعربي
        $table->string('name_en', 255)->nullable(); // اسم المعهد بالانجليزي
        $table->text('description_ar')->nullable();
        $table->text('description_en')->nullable();
        $table->string('address', 255)->nullable(); // تم تغييره لـ string لأنه أنسب للعنوان

            $table->string('phone', 20)->nullable(); // رقم التواصل
        $table->string('email', 100)->unique()->nullable(); // ايميل المعهد الرسمي


        $table->string('website')->nullable();
        $table->decimal('lat', 10, 8)->nullable();// خط العرض

        $table->decimal('lng', 11, 8)->nullable();
        $table->string('logo',2048)->nullable();
        $table->string('cover_photo' , 2048)->nullable();

        $table->decimal('commission_rate', 5, 2)->default(0);
        $table->integer('priority_level')->default(0);
        $table->integer('points_balance')->default(0);
        $table->boolean('status')->default(true);




        $table->timestamps();
        $table->softDeletes();

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
