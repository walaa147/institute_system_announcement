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
        $table->foreignId('diploma_id')->nullable()->constrained('diplomas')->onDelete('cascade');
        // نجعل course_id قابل للإلغاء أيضاً لأن الحجز قد يكون لدبلوم فقط
        $table->foreignId('course_id')->nullable()->change();
    });
}
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            //
        });}
    };

