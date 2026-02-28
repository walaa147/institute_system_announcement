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
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->string('code', 20)->unique()->nullable(); // كود الطالب
            $table->string('full_name_ar', 255);
            $table->string('full_name_en', 255)->nullable();
            $table->string('phone_number', 100)->unique();

            $table->enum('gender', ['male', 'female'])->nullable();
            $table->string('logo', 2048)->nullable(); // الصورة الشخصية
            $table->string('city', 100)->nullable();
            $table->text('address')->nullable();

            $table->text('fcm_token')->nullable(); // لإشعارات الجوال
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();


        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_profiles');
    }
};
