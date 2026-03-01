<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::create('departments', function (Blueprint $table) {
        $table->id();
         $table->string('slug')->unique();
        $table->string('name_ar', 100);
        $table->string('name_en', 100)->nullable();
        $table->text('description_ar')->nullable();
        $table->text('description_en')->nullable();

        // ربط القسم بالمعهد
        $table->foreignId('institute_id')->constrained('institutes')->onDelete('cascade');

        $table->boolean('is_active')->default(true);



        $table->timestamps();
        $table->softDeletes();

        // لجعل اسم القسم فريداً داخل المعهد الواحد فقط
        $table->unique(['name_ar', 'institute_id']);
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};
