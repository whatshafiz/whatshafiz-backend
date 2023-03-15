<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class HafizOl extends Model
{
    use HasFactory;

    /**
     * @var string
     */
    protected $table = 'hafiz_ol';

    /**
     * @return MorphMany
     */
    public function attempts(): MorphMany
    {
        return $this->morphMany(HafizAttempt::class, 'attemptable');
    }

    /**
     * @return BelongsToMany
     */
    public function users()
    {
        return $this->belongsToMany(
            User::class,
            'hafiz_attempts',
            'attemptable_id',
            'user_id'
        )->where('attemptable_type', self::class);
    }

    /**
     * @param User $user
     * @return MorphMany
     */
    public function attemptsByUser(User $user)
    {
        return $this->attempts()->where('user_id', $user->id);
    }

    /**
     * @param User $user
     * @return void
     */
    public function attempt(User $user)
    {
        $attempt = new HafizAttempt();
        $attempt->user_id = $user->id;
        $attempt->attemptable_id = $this->id;
        $attempt->attemptable_type = self::class;
        $attempt->save();
    }
}
