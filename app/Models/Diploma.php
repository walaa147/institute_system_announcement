<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;
class Diploma extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code', 'slug', 'name_ar', 'name_en', 'title_ar', 'title_en',
        'description_ar', 'description_en', 'total_cost', 'photo_path',
        'duration', 'start_date', 'end_date', 'is_active', 'is_available',
        'institute_id', 'department_id', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_available' => 'boolean',
        'total_cost' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
    ];
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($diploma) {
            if (empty($diploma->slug)) {
                $diploma->slug = Str::slug($diploma->title_ar) . '-' . time();
            }
        });
    }

    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'course_diploma')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderBy('course_diploma.sort_order');
    }

    public function institute(): BelongsTo
    {
        return $this->belongsTo(Institute::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function likes(): MorphMany
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    public function bookings(): MorphMany
    {
        return $this->morphMany(Booking::class, 'bookable');
    }

    public function waitingLists(): MorphMany
    {
        return $this->morphMany(WaitingList::class, 'bookable');
    }

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo_path ? asset('storage/' . $this->photo_path) : null;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }
}