<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class HafizAttempt extends Model
{
    use HasFactory;

    /**
     * @var string
     */
    protected $table = 'hafiz_attempts';

    /**
     * @return mixed
     */
    public function morphTo()
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsToMany
     */
    public function pages(): BelongsToMany
    {
        return $this->belongsToMany(
            QuranPage::class,
            'quran_page_checks',
            'attempt_id'
        );
    }

    /**
     * @param User $user
     * @return mixed
     */
    public function getUserActiveAttempt(User $user)
    {
        return $this->where('user_id', $user->id)
            ->whereNull('is_completed')
            ->first();
    }
}
