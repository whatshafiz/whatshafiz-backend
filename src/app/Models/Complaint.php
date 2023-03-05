<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Complaint extends Model
{
    use HasFactory;

    /**
     * @var array
     */
    protected $fillable = [
        'reviewed_by',
        'reviewed_at',
        'is_fixed',
        'result',
        'subject',
        'description',
        'related_user_id',
        'created_by'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function createdUser()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function reviewedUser()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function relatedUser()
    {
        return $this->belongsTo(User::class, 'related_user_id');
    }

    /**
     * @param User|null $user
     * @return bool
     */
    public function isRelatedToUser(?User $user): bool
    {
        $userId = $user ? $user->id : auth()->id();

        return $this->created_by === $userId ||
            $this->reviewed_by === $userId;
    }

    /**
     * @param $query
     * @return mixed
     */
    public function scopeMyComplaints($query)
    {
        return $query->where('created_by', auth()->id())
            ->orWhere('reviewed_by', auth()->id());
    }
}
