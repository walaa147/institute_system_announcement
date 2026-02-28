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
    Schema::create('advertisements', function (Blueprint $table) {
        $table->id();
        $table->foreignId('institute_id')->constrained('institutes')->cascadeOnDelete();
        $table->foreignId('department_id')->constrained('departments')->cascadeOnDelete();
        // من قام بإنشاء الإعلان
        $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();


        // هذه الدالة تنشئ حقلين: adable_type و adable_id لربط الإعلان بالكورس أو الدبلوم
        $table->nullableMorphs('advertisable');

         $table->string('trainer_name')->nullable();
           $table->string('slug')->unique();


        // حقول العنوان والوصف (في حال كان الإعلان مخصصاً ولا يرتبط بدورة معينة)
        $table->string('title_ar', 150)->nullable();
        $table->string('title_en', 150)->nullable();
        $table->text('description_ar')->nullable();
        $table->text('description_en')->nullable();
         $table->string('image_path')->nullable();
        $table->string('location')->nullable();


// حقول الأسعار والخصومات
        $table->decimal('price_before_discount', 10, 2)->nullable()->default(0);
        $table->decimal('price_after_discount', 10, 2)->nullable()->default(0);
        $table->decimal('discount_percentage', 10, 2)->nullable()->default(0);
        $table->timestamp('discount_expiry')->nullable();
        $table->decimal('early_paid_price', 10, 2)->nullable()->default(0);
        $table->decimal('certificate_price', 10, 2)->nullable()->default(0);
        $table->boolean('is_free')->default(false);
        $table->boolean('has_certificate')->default(false);

// حقول المقاعد والحجز
         $table->integer('early_paid_seats_limit')->nullable();
          $table->integer('max_seats')->nullable();
           $table->integer('current_seats_taken')->nullable()->default(0);


        // تواريخ صلاحية الإعلان (مهمة جداً للحجز والإشعارات)
        $table->boolean('is_active')->default(true);
        $table->dateTime('event_date')->nullable();
        $table->string('duration')->nullable();
         $table->dateTime('start_date')->nullable();
        $table->dateTime('end_date')->nullable();
          $table->string('link')->nullable();
        $table->boolean('is_open_for_booking')->default(true);
         $table->timestamp('published_at')->nullable();
          $table->timestamp('expired_at')->nullable();



        $table->timestamp('created_at')->useCurrent();
         $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
         $table->softDeletes('deleted_at')->nullable();




        $table->timestamps();
        $table->softDeletes();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('advertisements');
    }
};
