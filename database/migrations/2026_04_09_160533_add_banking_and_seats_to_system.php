<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // 1. إضافة معرف البنك (ضروري جداً للـ Capture لاحقاً)
            $table->string('bank_payment_id')->nullable()->after('transaction_id');

            // 2. تحديث الـ Enum الخاص بحالة الدفع لإضافة 'authorized'
            // ملاحظة: في Laravel لا يمكن تعديل Enum بـ change() مباشرة بسهولة،
            // الأفضل استخدام DB Statement لضمان الدقة في MySQL
            DB::statement("ALTER TABLE bookings MODIFY COLUMN payment_status ENUM('pending', 'authorized', 'paid', 'failed', 'refunded') DEFAULT 'pending'");
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('bank_payment_id');
            DB::statement("ALTER TABLE bookings MODIFY COLUMN payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending'");
        });
    }
};
