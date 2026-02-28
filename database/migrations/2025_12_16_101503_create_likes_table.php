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
    Schema::create('likes', function (Blueprint $table) {
        $table->id();

        // 1. ربط المفضلة بالمستخدم
        $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

        // 2. العلاقة المتعددة الأشكال (Likeable)
        // ستنشئ حقلي likeable_type و likeable_id
        $table->morphs('likeable');

        $table->unique(['user_id', 'likeable_id', 'likeable_type']);

        $table->timestamps();
    });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('likes');
    }
};
