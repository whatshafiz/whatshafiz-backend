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
     * @param  Request  $request
     * @return JsonResponse
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', [AnswerAttempt::class, $request]);

        $filters = $this->validate($request, [
            'user_id' => 'nullable|integer|min:0|exists:users,id',
            'quran_question_id' => 'nullable|integer|min:0|exists:quran_questions,id',
            'selected_option_number' => 'nullable|integer|min:1|max:5',
            'is_correct_option' => 'nullable|boolean',
        ]);

        $attempts = AnswerAttempt::latest('id')
            ->when(isset($filters['user_id']), function ($query) use ($filters) {
                return $query->where('user_id', $filters['user_id']);
            })
            ->when(isset($filters['quran_question_id']), function ($query) use ($filters) {
                return $query->where('quran_question_id', $filters['quran_question_id']);
            })
            ->when(isset($filters['selected_option_number']), function ($query) use ($filters) {
                return $query->where('selected_option_number', $filters['selected_option_number']);
            })
            ->when(isset($filters['is_correct_option']), function ($query) use ($filters) {
                return $query->where('is_correct_option', $filters['is_correct_option']);
            })
            ->paginate()
            ->appends($filters)
            ->toArray();

        return response()->json(compact('attempts'));
    }

    /**
     * @param  Request  $request
     * @return JsonResponse
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function myAnswerAttempts(Request $request): JsonResponse
    {
        return $this->index($request->merge(['user_id' => Auth::id()]));
    }

    /**
     * Display the specified resource.
     *
     * @param  AnswerAttempt  $answerAttempt
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function show(AnswerAttempt $answerAttempt): JsonResponse
    {
        $this->authorize('view', [AnswerAttempt::class, $answerAttempt]);

        return response()->json(compact('answerAttempt'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function store(Request $request): JsonResponse
    {
        $answerAttempt = $this->validate($request, [
            'quran_question_id' => 'required|integer|min:0|exists:quran_questions,id',
            'selected_option_number' => 'required|integer|min:1|max:5',
        ]);

        $correctOption =  QuranQuestion::where('id', $answerAttempt['quran_question_id'])->value('correct_option');

        $answerAttempt['user_id'] = Auth::id();
        $answerAttempt['is_correct_option'] = $correctOption === $answerAttempt['selected_option_number'];

        AnswerAttempt::create($answerAttempt);

        return response()->json(compact('answerAttempt'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  AnswerAttempt  $answerAttempt
     * @return Response
     * @throws AuthorizationException
     */
    public function destroy(AnswerAttempt $answerAttempt): JsonResponse
    {
        $this->authorize('delete', AnswerAttempt::class);

        $answerAttempt->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
