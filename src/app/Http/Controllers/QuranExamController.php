<?php

namespace App\Http\Controllers;

use App\Models\QuranExam;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class QuranExamController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function index()
    {
        $this->authorize('viewAny', QuranExam::class);

        $validatedRequest = $this->validate(
            $request,
            [
                'name' => 'nullable|string',
                'page_num' => 'nullable|integer'
            ]
        );

        $quranExams = QuranExam::when(isset($validatedRequest['name']), function ($query) use ($validatedRequest) {
            return $query->where('name', $validatedRequest['name']);
        })->when(isset($validatedRequest['page_num']), function ($query) use ($validatedRequest) {
            return $query->where('page_num', $validatedRequest['page_num']);
        })
        ->latest('id')
        ->paginate()
        ->appends($validatedRequest)
        ->toArray();

        return response()->json(compact('quranExams'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function store(Request $request)
    {
        $this->authorize('create', QuranExam::class);

        $validatedReequest = $this->validate(
            $request,
            [
                'name' => 'nullable|string',
                'page_num' => 'nullable|integer',
                'question' => 'required|string',
                'answer_1' => 'required|string',
                'answer_2' => 'required|string',
                'answer_3' => 'required|string',
                'answer_4' => 'required|string',
                'answer_5' => 'required|string',
                'correct_answer' => 'required|integer',
            ]
        );

        $quranExam = QuranExam::create($validatedReequest);

        return response()->json($quranExam);
    }

    /**
     * Display the specified resource.
     *
     * @param QuranExam $quranExam
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function show(QuranExam $quranExam)
    {
        $this->authorize('view', $quranExam);

        return response()->json($quranExam);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param QuranExam $quranExam
     * @return JsonResponse
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function update(Request $request, QuranExam $quranExam)
    {
        $this->authorize('update', $quranExam);

        $validatedReequest = $this->validate(
            $request,
            [
                'name' => 'nullable|string',
                'page_num' => 'nullable|integer',
                'question' => 'required|string',
                'answer_1' => 'required|string',
                'answer_2' => 'required|string',
                'answer_3' => 'required|string',
                'answer_4' => 'required|string',
                'answer_5' => 'required|string',
                'correct_answer' => 'required|integer',
            ]
        );

        if ($quranExam->update($validatedReequest)) {
            return response()->json(['status' => 'success']);
        }

        return response()->json(['status' => 'failed'], Response::HTTP_BAD_REQUEST);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\QuranExam $quranExam
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function destroy(QuranExam $quranExam)
    {
        $this->authorize('delete', $quranExam);

        if($quranExam->delete())
        {
            return response()->json(['status' => 'success']);
        }

        return response()->json(['status' => 'failed'], Response::HTTP_BAD_REQUEST);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function assign(Request $request)
    {
        $validatedRequest = $this->validate(
            $request,
            [
                'user_id' => 'required|integer|exists:users,id',
                'quran_exam_id' => 'required|integer|exists:quran_exams,id',
            ]
        );

        $user = User::find($validatedRequest['user_id']);
        $quranExam = QuranExam::find($validatedRequest['quran_exam_id']);

        if(!$user || !$quranExam) {
            return response()->json(
                [
                    'status' => 'failed',
                    'message' => 'Kullanıcı veya sınav bulunamadı.'
                ],
                Response::HTTP_BAD_REQUEST
            );
        }

        $user->assignQuranExam($quranExam);

        return response()->json(['status' => 'success']);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function answer(Request $request)
    {
        $validatedRequest = $this->validate(
            $request,
            [
                'answer' => 'required|integer',
            ]
        );

        if(!Auth::user()->activeQuranExam()) {
            return response()->json(['status' => 'failed', 'message' => 'Aktif sınav yok.'], Response::HTTP_BAD_REQUEST);
        }

        $result = Auth::user()->answerExam($validatedRequest['answer']);

        return response()->json(['status' => 'success', 'result' => $result]);
    }
}
