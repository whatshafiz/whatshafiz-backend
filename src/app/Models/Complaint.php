<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Complaint extends BaseModel
{
    use HasFactory;

    protected $casts = [
        'is_resolved' => 'boolean',
        'created_at' => 'datetime:d-m-Y H:i',
        'updated_at' => 'datetime:d-m-Y H:i',
        'reviewed_at' => 'datetime:d-m-Y H:i',
    ];

    /**
     * @return BelongsTo
     */
    public function createdUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo
     */
    public function reviewedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * @return BelongsTo
     */
    public function relatedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'related_user_id');
    }

    /**
     * @param User|null $user
     * @return bool
     */
    public function isOwnedByUser(?User $user): bool
    {
        $userId = $user ? $user->id : auth()->id();

        return $this->created_by === $userId;
    }
}
