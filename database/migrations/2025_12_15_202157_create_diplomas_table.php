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
         $table->string('slug',50)->unique(); // سلاگ الدبلوم لاستخدامه في الروابط
         $table->string('name_ar', 150); // الاسم الرسمي للدبلوم بالعربي
        $table->string('name_en', 150)->nullable();// الاسم الرسمي للدبلوم بالإنجليزي

        $table->string('title_ar', 150);// العنوان التسويقي للدبلوم بالعربي
        $table->string('title_en', 150)->nullable();// العنوان التسويقي للدبلوم بالإنجليزي
        $table->text('description_ar')->nullable();// الوصف التسويقي للدبلوم بالعربي
        $table->text('description_en')->nullable();// الوصف التسويقي للدبلوم بالإنجليزي
        $table->decimal('total_cost', 10, 2)->default(0); // التكلفة (استخدام decimal أفضل للفلوس)
        $table->string('photo_path',2048)->nullable(); // صورة الدبلوم
        $table->string('duration')->nullable(); // مدة الدبلوم (مثلاً: "3 أشهر" أو "6 أشهر")
        $table->date('start_date')->nullable(); // تاريخ بدء الدبلوم
        $table->date('end_date')->nullable(); // تاريخ انتهاء الدبلوم
        $table->boolean('is_active')->default(true);// هل يظهر في التطبيق أم لا
        $table->boolean('is_available')->default(true);// هل يمكن للطلاب التسجيل في الدبلوم أم لا
$table->integer('total_likes')->default(0); // عدد الإعجابات بالدبلوم
        // حقول التتبع لربطها بالموظف (مثل الكورسات)
        $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');// الموظف الذي قام بإنشاء الدبلوم
        $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');// الموظف الذي قام بآخر تعديل

        $table->foreignId('department_id')->nullable()->constrained('departments')->onDelete('cascade');
 $table->foreignId('institute_id')->constrained('institutes')->onDelete('cascade');

        $table->timestamps();
        $table->softDeletes(); // إضافة حذف ناعم (Soft Deletes)
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
