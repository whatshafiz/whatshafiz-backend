<?php

namespace App\Models\Traits;

use App\Models\QuranExam;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

trait QuranStudent
{
    /**
     * @return BelongsToMany
     */
    public function quranExams(): BelongsToMany
    {
        return $this->belongsToMany(QuranExam::class, 'exam_user_assignment', 'user_id', 'exam_id')
            ->withPivot(['is_success', 'answer']);
    }

    /**
     * @param QuranExam $quranExam
     * @return void
     */
    public function assignQuranExam(QuranExam $quranExam): void
    {
        $this->quranExams()->save($quranExam);
    }

    /**
     * @return QuranExam|null
     */
    public function activeQuranExam(): ?QuranExam
    {
        return $this->quranExams()->wherePivot('answer', null)->first();
    }

    /**
     * @param $answer
     * @return bool
     */
    public function answerExam($answer): bool
    {
        $activeExam = $this->activeQuranExam();
        $result = $activeExam->checkAnswer($answer);
        $this->quranExams()->updateExistingPivot($activeExam->id, ['answer' => $answer, 'is_success' => $result]);
        return $result;
    }

    /**
     * @param $answer
     * @return bool
     */
    public function checkActiveExamAnswer($answer = null): bool
    {
        $exam = $this->activeQuranExam();
        if ($exam) {
            $result = $answer ? $exam->checkAnswer($answer) : $exam->pivot->answer === $exam->coorect_answer;
            $this->quranExams()->updateExistingPivot($exam->id, ['is_success' => $result]);
            return $result;
        }
        return false;
    }

    /**
     * @return HasMany
     */
    public function failedQuranExams(): HasMany
    {
        return $this->hasMany(QuranExam::class, 'user_id', 'id')->where('is_success', false);
    }

    /**
     * @return HasMany
     */
    public function successQuranExams(): HasMany
    {
        return $this->hasMany(QuranExam::class, 'user_id', 'id')->where('is_success', true);
    }
}
