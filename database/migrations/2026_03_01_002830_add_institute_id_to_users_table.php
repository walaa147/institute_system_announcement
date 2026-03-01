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
        Schema::table('users', function (Blueprint $table) {
            // إضافة الحقل بعد البريد الإلكتروني لضمان ترتيب منطقي في قاعدة البيانات
            $table->foreignId('institute_id')
                  ->nullable()
                  ->after('email')
                  ->constrained('institutes')
                  ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // حذف العلاقة أولاً ثم حذف الحقل عند التراجع عن الهجرة
            $table->dropForeign(['institute_id']);
            $table->dropColumn('institute_id');
        });
    }
};
