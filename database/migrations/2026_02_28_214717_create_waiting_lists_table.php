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
    Schema::create('waiting_lists', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();


        // أولوية الطالب في قائمة الانتظار
        $table->integer('priority_order')->default(1);

        // حالة الطالب في قائمة الانتظار
        // waiting: ينتظر | notified: تم إشعاره بوجود مقعد | converted: تحول لحجز فعلي | cancelled: ألغى طلبه
        $table->enum('status', ['waiting', 'notified', 'converted', 'cancelled'])->default('waiting');

        $table->text('notes')->nullable();
        $table->morphs('bookable');

        $table->timestamps();
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('waiting_lists');
    }
};
