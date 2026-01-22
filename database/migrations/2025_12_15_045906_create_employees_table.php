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
    Schema::create('employees', function (Blueprint $table) {
        $table->id();
        $table->string('code', 20)->unique(); // الكود الوظيفي
        $table->foreignId('user_id')->unique()->constrained('users')->onDelete('cascade');
        $table->foreignId('institute_id')->constrained('institutes')->onDelete('cascade'); // ربط الموظف بمعهده

        $table->string('name_ar', 100);
        $table->string('name_en', 100)->nullable();
        $table->enum('gender', ['male', 'female'])->nullable();
        $table->string('phone', 20)->nullable();
        $table->string('address', 255)->nullable();
        $table->string('job_title', 50)->nullable();
        $table->date('hire_date')->nullable();
        $table->decimal('salary', 10, 2)->nullable();
        $table->boolean('is_active')->default(true); // تم تصحيح default

        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
