<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CourseType extends BaseModel
{
    use HasFactory;
    use SoftDeletes;

    protected $casts = [
        'is_active' => 'boolean',
        'has_admission_exam' => 'boolean',
        'genders' => 'array',
        'education_levels' => 'array',
        'created_at' => 'datetime:d-m-Y H:i',
        'updated_at' => 'datetime:d-m-Y H:i',
        'deleted_at' => 'datetime:d-m-Y H:i',
    ];

    /**
     * @return HasMany
     */
    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
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
    public function userCourses(): HasMany
    {
        return $this->hasMany(UserCourse::class);
    }

    /**
     * @return HasMany
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }
}
