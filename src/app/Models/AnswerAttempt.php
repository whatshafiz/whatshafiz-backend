<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnswerAttempt extends BaseModel
{
    use HasFactory;

    /**
     * @var string[]
     */
    protected $with = ['user', 'question'];

    /**
     * @return string[]
     */
    protected $cast = [
        'is_correct' => 'boolean',
    ];

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(QuranQuestion::class);
    }
}
