<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class WhatsappGroupUser extends BaseModel
{
    public $table = 'user_course';

    use HasFactory;
    use SoftDeletes;

    protected $casts = [
        'is_teacher' => 'boolean',
        'is_moderator' => 'boolean',
        'joined_at' => 'datetime:d-m-Y H:i',
        'moderation_started_at' => 'datetime:d-m-Y H:i',
        'created_at' => 'datetime:d-m-Y H:i',
        'updated_at' => 'datetime:d-m-Y H:i',
        'deleted_at' => 'datetime:d-m-Y H:i',
    ];

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
