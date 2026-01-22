<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Like extends Model
{
    use HasFactory;

    // تحديد اسم الجدول الجديد
    protected $table = 'likes';

    protected $fillable = [
        'user_id',
        'likeable_type',
        'likeable_id',
    ];

    /**
     * العلاقة العكسية لـ Polymorphic (للوصول إلى الشيء الذي تم الإعجاب به)
     */
    public function likeable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * العلاقة مع المستخدم الذي قام بالإعجاب
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
