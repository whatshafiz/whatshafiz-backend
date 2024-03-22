<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Comment extends BaseModel
{
    use HasFactory;
    use SoftDeletes;

    protected $casts = [
        'is_approved' => 'boolean',
        'created_at' => 'datetime:d-m-Y H:i',
        'updated_at' => 'datetime:d-m-Y H:i',
        'approved_at' => 'datetime:d-m-Y H:i',
    ];

    /**
     * @return BelongsTo
     */
    public function courseType(): BelongsTo
    {
        return $this->belongsTo(CourseType::class);
    }

    /**
     * @return BelongsTo
     */
    public function commentedBy(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @param  User  $user
     * @return bool
     */
    public function isCommentedBy(User $user): bool
    {
        return $this->commented_by_id === $user->id;
    }

    /**
     * @param  User  $user
     * @return Builder
     */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('is_approved', true);
    }
}
