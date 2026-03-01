<?php

use Illuminate\Database\Eloquent\SoftDeletes;
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
    Schema::create('courses', function (Blueprint $table) {
        $table->id();
        $table->string('code', 10)->unique(); // كود الدورة
        $table->string('slug', 50)->unique(); // سلاگ الدورة لاستخدامه في الروابط

        // العنوان (للتسويق في واجهة الفلاتر)
        $table->string('title_ar');
        $table->string('title_en')->nullable();

        // الاسم (الرسمي للأكاديمية)
        $table->string('name_ar');
        $table->string('name_en')->nullable();

        $table->text('description_ar')->nullable();
        $table->text('description_en')->nullable();
        $table->decimal('price', 10, 2)->default(0);
        $table->string('photo_path')->nullable(); // صورة الدورة
        $table->string('duration')->nullable(); // مدة الدورة (مثلاً: "3 ساعات" أو "5 أيام")
        $table->date('start_date')->nullable(); // تاريخ بدء الدورة
        $table->date('end_date')->nullable(); // تاريخ انتهاء الدورة

        // ربط بالقسم
        $table->foreignId('department_id')->constrained()->onDelete('cascade');
        $table->foreignId('institute_id')->constrained()->onDelete('cascade');

        $table->boolean('is_active')->default(true); // هل تظهر في التطبيق أم لا
        $table->boolean('is_available')->default(true);// هل يمكن للطلاب التسجيل في الدورة أم لا
        $table->integer('total_likes')->default(0); // عدد الإعجابات بالدورة
$table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');// الموظف الذي قام بإنشاء الدورة

        // الموظف الذي قام بآخر تعديل
        $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');



        $table->timestamps();
        $table->softDeletes(); // إضافة حذف ناعم (Soft Deletes)
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
