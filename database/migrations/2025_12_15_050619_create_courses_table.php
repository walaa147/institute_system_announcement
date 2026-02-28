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
    Schema::create('courses', function (Blueprint $table) {
        $table->id();
        $table->string('code', 10)->unique(); // كود الدورة

        // العنوان (للتسويق في واجهة الفلاتر)
        $table->string('title_ar');
        $table->string('title_en')->nullable();

        // الاسم (الرسمي للأكاديمية)
        $table->string('name_ar');
        $table->string('name_en')->nullable();

        $table->text('description')->nullable();
        $table->decimal('price', 10, 2)->default(0);
        $table->string('photo_path')->nullable(); // صورة الدورة

        // ربط بالقسم
        $table->foreignId('department_id')->nullable()->constrained()->onDelete('cascade');

        // منطق الفلاتر (حجز أو لايك)
        $table->boolean('is_open')->default(true); // true = حجز، false = لايك
        $table->boolean('is_active')->default(true); // هل تظهر في التطبيق أم لا
$table->foreignId('created_by')->nullable()->constrained('employees')->onDelete('set null');

        // الموظف الذي قام بآخر تعديل
        $table->foreignId('updated_by')->nullable()->constrained('employees')->onDelete('set null');

        $table->foreignId('institute_id')->constrained('institutes')->cascadeOnDelete();

        $table->timestamps();
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
