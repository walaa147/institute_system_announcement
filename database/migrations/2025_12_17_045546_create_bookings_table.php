<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * تشغيل الهجرة (إنشاء الجدول).
     */
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();

            // روابط الجداول
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->comment('الطالب الذي قام بالحجز');
           // $table->foreignId('course_id')->constrained('courses')->onDelete('cascade')->comment('الكورس المحجوز');

            // ضمان عدم حجز المستخدم لنفس الكورس مرتين   (حجز مبدئي واحد لكل كورس)
            //$table->unique(['user_id', 'course_id']);
            $table->unique(['user_id', 'bookable_id', 'bookable_type'], 'user_bookable_unique');


            // التفاصيل المالية
            $table->decimal('original_price', 10, 2)->comment('سعر الكورس الأصلي عند الحجز');
            $table->decimal('discount_amount', 10, 2)->nullable()->comment('قيمة الخصم المطبقة');
            $table->decimal('final_price', 10, 2)->comment('السعر النهائي المطلوب دفعه');

            $table->string('payment_method')->nullable();
            $table->string('transaction_id')->unique()->nullable();
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded'])->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->json('payment_payload')->nullable();
            $table->text('admin_notes')->nullable();

            // حالة الحجز والدفع
            $table->boolean('is_paid')->default(false)->comment('هل تم الدفع وإتمام التسجيل؟');
            $table->json('payment_details')->nullable()->comment('تفاصيل الدفع عند الإتمام (بواسطة السكرتير)');
            $table->string('booking_type')->default('regular');
            $table->enum('status', ['draft', 'confirmed', 'cancelled'])->default('draft');

            // تاريخ ووقت الحجز
            $table->dateTime('booking_date')->useCurrent()->comment('تاريخ ووقت تسجيل الحجز المبدئي');


            // العلاقة متعددة الأشكال لربط الحجز بالكورس أو الدبلوم او الاعلان مباشرة
$table->morphs('bookable');




            $table->timestamps();
        });
    }

    /**
     * إلغاء الهجرة (حذف الجدول).
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
