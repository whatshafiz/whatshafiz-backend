<?php

namespace App\Http\Controllers;

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
     * @return JsonResponse
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', QuranQuestion::class);

        $filters = $this->validate($request, [
            'page_number' => 'nullable|integer|min:1|max:610',
            'question' => 'nullable|string|max:3000',
        ]);

        $questions = QuranQuestion::selectRaw('*, CONCAT(TRIM(SUBSTRING(question, 1, 33)), \'...\') as question')
            ->when(isset($filters['page_number']), function ($query) use ($filters) {
                return $query->where('page_number', $filters['page_number']);
            })
            ->when(isset($filters['question']), function ($query) use ($filters) {
                return $query->where('question', 'LIKE', '%' . $filters['question'] . '%');
            })
            ->orderByTabulator($request)
            ->paginate($request->size)
            ->appends($filters);

        return response()->json($questions->toArray());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', QuranQuestion::class);

        $validatedQuranQuestionData = $this->validate($request, [
            'page_number' => 'required|integer|min:1|max:610',
            'question' => 'required|string|max:3000',
            'option_1' => 'required|string|max:255',
            'option_2' => 'required|string|max:255',
            'option_3' => 'required|string|max:255',
            'option_4' => 'required|string|max:255',
            'option_5' => 'required|string|max:255',
            'correct_option' => 'required|integer|min:1|max:5',
        ]);

        $quranQuestion = QuranQuestion::create($validatedQuranQuestionData);

        return response()->json(compact('quranQuestion'), Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     *
     * @param QuranQuestion $quranQuestion
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function show(QuranQuestion $quranQuestion): JsonResponse
    {
        $this->authorize('view', QuranQuestion::class);

        return response()->json(compact('quranQuestion'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Request $request
     * @param QuranQuestion $quranQuestion
     * @return JsonResponse
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function update(Request $request, QuranQuestion $quranQuestion): JsonResponse
    {
        $this->authorize('update', QuranQuestion::class);

        $validatedQuranQuestionData = $this->validate($request, [
            'page_number' => 'required|integer|min:1|max:610',
            'question' => 'required|string|max:3000',
            'option_1' => 'required|string|max:255',
            'option_2' => 'required|string|max:255',
            'option_3' => 'required|string|max:255',
            'option_4' => 'required|string|max:255',
            'option_5' => 'required|string|max:255',
            'correct_option' => 'required|integer|min:1|max:5',
        ]);

        $quranQuestion->update($validatedQuranQuestionData);

        return response()->json(compact('quranQuestion'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param QuranQuestion $quranQuestion
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function destroy(QuranQuestion $quranQuestion): JsonResponse
    {
        $this->authorize('delete', QuranQuestion::class);

        $quranQuestion->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
