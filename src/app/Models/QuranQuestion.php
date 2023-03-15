<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class QuranQuestion extends BaseModel
{
    use HasFactory;

    /**
     * @var string
     */
    protected $table = 'quran_questions';

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function attempts()
    {
        return $this->hasMany(AnswerAttempt::class, 'question_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'answer_attempts', 'question_id', 'user_id');
    }
}
