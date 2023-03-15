<?php

namespace App\Http\Controllers;

use App\Models\AnswerAttempt;
use App\Models\QuranQuestion;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AnswerAttemptController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', AnswerAttempt::class);

        $filters = $this->validate($request, [
            'user_id' => 'nullable|integer|min:0|exists:users,id',
            'question_id' => 'nullable|integer|min:0|exists:quran_questions,id',
            'answer' => 'nullable|integer|min:0|max:5',
            'is_correct' => 'nullable|boolean',
        ]);

        $attempts = AnswerAttempt::latest()
            ->when(isset($filters['user_id']), function ($query) use ($filters) {
                return $query->where('user_id', $filters['user_id']);
            })
            ->when(isset($filters['question_id']), function ($query) use ($filters) {
                return $query->where('question_id', $filters['question_id']);
            })
            ->when(isset($filters['answer']), function ($query) use ($filters) {
                return $query->where('answer', $filters['answer']);
            })
            ->when(isset($filters['is_correct']), function ($query) use ($filters) {
                return $query->where('is_correct', $filters['is_correct']);
            })
            ->paginate()
            ->appends($filters)
            ->toArray();

        return response()->json(compact('attempts'));
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Models\AnswerAttempt $answerAttempt
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function show(AnswerAttempt $answerAttempt)
    {
        $this->authorize('view', [AnswerAttempt::class, $answerAttempt]);

        return response()->json(compact('answerAttempt'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param AnswerAttempt $answerAttempt
     * @return JsonResponse
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function update(Request $request, AnswerAttempt $answerAttempt)
    {
        $this->authorize('update', [AnswerAttempt::class, $answerAttempt]);

        $data = $this->validate($request, [
            'answer' => 'integer|min:0|max:5',
        ]);

        $question = QuranQuestion::find($answerAttempt->question_id);

        $data['is_correct'] = $question->correct_option == $data['answer'] ? 1 : 0;

        $answerAttempt->update($data);

        return response()->json(compact('answerAttempt'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param AnswerAttempt $answerAttempt
     * @return Response
     * @throws AuthorizationException
     */
    public function destroy(AnswerAttempt $answerAttempt)
    {
        $this->authorize('delete', AnswerAttempt::class);

        $answerAttempt->delete();

        return response()->noContent();
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function myAnswerAttempts(Request $request)
    {
        $filters = $this->validate($request, [
            'question_id' => 'nullable|integer|min:0|exists:quran_questions,id',
            'is_correct' => 'nullable|boolean',
        ]);

        $attempts = Auth::user()->answerAttempts()
            ->when(isset($filters['question_id']), function ($query) use ($filters) {
                return $query->where('question_id', $filters['question_id']);
            })
            ->when(isset($filters['is_correct']), function ($query) use ($filters) {
                return $query->where('is_correct', $filters['is_correct']);
            })
            ->latest()
            ->paginate()
            ->toArray();

        return response()->json(compact('attempts'));
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function myActiveAnswerAttempt(Request $request)
    {
        $attempt = Auth::user()
            ->answerAttempts()
            ->whereNull('answer')
            ->whereNull('is_correct')
            ->latest()
            ->first();

        return response()->json(compact('attempt'));
    }
}
