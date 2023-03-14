<?php

namespace App\Http\Controllers;

use App\Models\AnswerAttempt;
use App\Models\QuranQuestion;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class QuranQuestionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', QuranQuestion::class);

        $filtesr = $this->validate($request, [
            'page_number' => 'nullable|integer|min:0',
            'question' => 'nullable|string|max:255',
            'name' => 'nullable|string|max:255',
        ]);

        $questions = QuranQuestion::latest()
            ->when(isset($filters['page_number']), function ($query) use ($filters) {
                return $query->where('page_number', $filters['page_number']);
            })
            ->when(isset($filters['question']), function ($query) use ($filters) {
                return $query->where('question', $filters['question']);
            })
            ->when(isset($filters['name']), function ($query) use ($filters) {
                return $query->where('name', $filters['name']);
            })
            ->paginate()
            ->appends($filters)
            ->toArray();

        return response()->json(compact('questions'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function store(Request $request)
    {
        $this->authorize('create', QuranQuestion::class);

        $data = $this->validate($request, [
            'page_number' => 'required|integer|min:0',
            'question' => 'required|string|max:255',
            'name' => 'nullable|string|max:255',
            'option_1' => 'required|string|max:255',
            'option_2' => 'required|string|max:255',
            'option_3' => 'required|string|max:255',
            'option_4' => 'required|string|max:255',
            'option_5' => 'required|string|max:255',
            'correct_option' => 'required|integer|min:1|max:5',
        ]);

        $question = QuranQuestion::create($data);

        return response()->json(compact('question'));
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Models\QuranQuestion $quranQuestion
     * @return \Illuminate\Http\JsonResponse
     * @throws AuthorizationException
     */
    public function show(QuranQuestion $quranQuestion)
    {
        $this->authorize('view', QuranQuestion::class);

        return response()->json(compact('quranQuestion'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param QuranQuestion $quranQuestion
     * @return \Illuminate\Http\JsonResponse
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function update(QuranQuestion $quranQuestion)
    {
        $this->authorize('update', QuranQuestion::class);

        $data = $this->validate($request, [
            'page_number' => 'required|integer|min:0',
            'question' => 'required|string|max:255',
            'name' => 'nullable|string|max:255',
            'option_1' => 'required|string|max:255',
            'option_2' => 'required|string|max:255',
            'option_3' => 'required|string|max:255',
            'option_4' => 'required|string|max:255',
            'option_5' => 'required|string|max:255',
            'correct_option' => 'required|integer|min:1|max:5',
        ]);

        $quranQuestion->update($data);

        return response()->json(compact('quranQuestion'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param QuranQuestion $quranQuestion
     * @return \Illuminate\Http\JsonResponse
     * @throws AuthorizationException
     */
    public function destroy(QuranQuestion $quranQuestion)
    {
        $this->authorize('delete', QuranQuestion::class);

        $quranQuestion->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function assign(Request $request)
    {
        $this->authorize('assign', QuranQuestion::class);

        $data = $this->validate($request, [
            'user_id' => 'nullable|integer|min:0|exists:users,id',
            'question_id' => 'nullable|integer|min:0|exists:quranquestions,id',
        ]);

        $question = QuranQuestion::find($data['question_id']);

        $question->users()->assign($data['user_id']);

        return response()->json(compact('question'));
    }
}
