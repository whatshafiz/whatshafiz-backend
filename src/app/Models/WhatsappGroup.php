<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WhatsappGroup extends BaseModel
{
    use HasFactory;
    use SoftDeletes;

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime:d-m-Y H:i',
        'updated_at' => 'datetime:d-m-Y H:i',
        'deleted_at' => 'datetime:d-m-Y H:i',
    ];

    /**
     * @return HasMany
     */
    public function users(): HasMany
    {
        return $this->hasMany(WhatsappGroupUser::class);
    }

    /**
     * @return HasMany
     */
    public function moderator(): HasMany
    {
        return $this->users()->where('is_moderator', true);
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
