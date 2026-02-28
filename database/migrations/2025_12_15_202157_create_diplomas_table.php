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
    Schema::create('diplomas', function (Blueprint $table) {
        $table->id();
        $table->string('code')->unique(); // رمز فريد للدبلوم
        $table->string('title_ar', 150); // الاسم بالعربي
        $table->string('title_en', 150)->nullable(); // الاسم بالإنجليزي
        $table->text('description_ar')->nullable();
        $table->text('description_en')->nullable();
        $table->decimal('total_cost', 10, 2)->default(0); // التكلفة (استخدام decimal أفضل للفلوس)
        $table->string('photo_path')->nullable(); // صورة الدبلوم
        $table->boolean('is_active')->default(true);
$table->boolean('is_open')->default(true);
        // الربط بالمعهد (الذي طلبت إضافته)
        $table->foreignId('institute_id')->constrained('institutes')->onDelete('cascade');

        // حقول التتبع لربطها بالموظف (مثل الكورسات)
        $table->foreignId('created_by')->constrained('employees');
        $table->foreignId('updated_by')->nullable()->constrained('employees');

        $table->foreignId('department_id')->nullable()->constrained('departments')->onDelete('cascade');

        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('diplomas');
    }
};
