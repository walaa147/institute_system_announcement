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
    Schema::table('bookings', function (Blueprint $table) {
        // 1. تحديث حقل الحالة ليشمل 'attended'
        // ملاحظة: إذا واجهت مشكلة في change() مع enum، استخدم string
        $table->enum('status', ['draft', 'confirmed', 'cancelled', 'attended'])
              ->default('draft')
              ->change();

        // 2. حقل وقت الموافقة: سنستخدمه لحساب الفرق الزمني وتحديث الـ priority_level في خدمة المعاهد
        $table->timestamp('confirmed_at')->nullable()->after('status');

        // 3. معرف السكرتير: لربط الأداء بشخص محدد في تقارير المعهد
        $table->foreignId('processed_by')
              ->nullable()
              ->after('confirmed_at')
              ->constrained('users')
              ->nullOnDelete();
    });
}
};
