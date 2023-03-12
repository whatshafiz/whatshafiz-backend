<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class QuranExam extends BaseModel
{
    use HasFactory;

    protected $table = 'quran_exams';

    /**
     * @param User $user
     * @return void
     */
    public function assigneToUser(User $user)
    {
        $user->quranExams()->save($this);
    }

    /**
     * @return BelongsToMany
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'exam_user_assignment', 'exam_id', 'user_id')
            ->withPivot(['is_correct', 'answer', 'created_at', 'updated_at']);
    }

    /**
     * @param $answer
     * @return bool
     */
    public function checkAnswer($answer)
    {
        return $this->correct_answer === $answer;
    }
}
