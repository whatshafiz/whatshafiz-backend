<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class Course extends BaseModel
{
    use HasFactory;
    use SoftDeletes;

    const AVAILABLE_COURSES_CACHE_KEY = 'available-courses';

    protected $casts = [
        'can_be_applied' => 'boolean',
        'is_active' => 'boolean',
        'start_at' => 'datetime:d-m-Y H:i',
        'can_be_applied_until' => 'datetime:d-m-Y H:i',
        'proficiency_exam_start_time' => 'datetime:d-m-Y H:i',
        'students_matchings_started_at' => 'datetime:d-m-Y H:i',
        'created_at' => 'datetime:d-m-Y H:i',
        'updated_at' => 'datetime:d-m-Y H:i',
        'deleted_at' => 'datetime:d-m-Y H:i',
    ];

    /**
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            Cache::forget(self::AVAILABLE_COURSES_CACHE_KEY);
        });
    }

    /**
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where(function ($subquery) {
            return $subquery->where('is_active', true)
                ->where('can_be_applied', true)
                ->where('can_be_applied_until', '>=', Carbon::now());
        });
    }

    /**
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeUnavailable(Builder $query): Builder
    {
        return $query->where(function ($subquery) {
            return $subquery->where('can_be_applied', false)
                ->orWhere(function ($subquery) {
                    return $subquery->where('can_be_applied', true)
                        ->where('can_be_applied_until', '<', Carbon::now());
                });
        });
    }

    /**
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @return BelongsToMany
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_course');
    }

    /**
     * @return HasMany
     */
    public function teacherStudentsMatchings(): HasMany
    {
        return $this->hasMany(TeacherStudent::class);
    }

    /**
     * @return HasMany
     */
    public function whatsappGroups(): HasMany
    {
        return $this->hasMany(WhatsappGroup::class);
    }

    /**
     * @return HasMany
     */
    public function whatsappGroupUsers(): HasMany
    {
        return $this->hasMany(WhatsappGroupUser::class);
    }

    /**
     * @param  int  $userId
     * @return bool
     */
    public function hasUser(int $userId): bool
    {
        return $this->users()->where('user_id', $userId)->exists();
    }
}
