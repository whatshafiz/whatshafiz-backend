<?php

namespace App\Http\Controllers;

use App\Models\ChatgptQuestion;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use OpenAI\Laravel\Facades\OpenAI;
use Symfony\Component\HttpFoundation\Response;

class ChatgptController extends Controller
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
        //$this->authorize('viewAny', ChatgptQuestion::class);

        $filters = $this->validate(
            $request,
            [
                'question' => 'nullable|string',
                'page_number' => 'nullable|integer',
                'approved_by' => 'nullable|integer|exists:users,id',
                'is_approved' => 'nullable|boolean',
            ]
        );

        $questions = ChatgptQuestion::with(['approvedBy'])
            ->when(isset($filters['question']), function ($query) use ($filters) {
                return $query->where('question', 'like', '%' . $filters['question'] . '%');
            })
            ->when(isset($filters['approved_by']), function ($query) use ($filters) {
                return $query->where('approved_by', $filters['approved_by']);
            })
            ->when(isset($filters['is_approved']), function ($query) use ($filters) {
                return $query->where('is_approved', $filters['is_approved']);
            })
            ->when(isset($filters['page_number']), function ($query) use ($filters) {
                return $query->where('page_number', $filters['page_number']);
            })
            ->latest('id')
            ->paginate()
            ->appends($filters);

        return response()->json(compact('questions'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return JsonResponse
     * @throws AuthorizationException|ValidationException
     */
    public function store(Request $request)
    {
        //$this->authorize('create', ChatgptQuestion::class);

        $this->validate($request, [
            'page' => 'required|int'
        ]);

        $result = OpenAI::completions()->create([
            'model' => 'text-davinci-003',
            'prompt' => ChatgptQuestion::questionText($request->page),
            'max_tokens' => 3850,
            'temperature' => 0
        ]);

        try{
            $response = json_decode($result['choices'][0]['text']);
            $question = ChatgptQuestion::create((array) $response);
        } catch (\Exception $e) {
            return response()->json($e->getMessage());
        }

        return response()->json(compact('question'));
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Models\ChatgptQuestion $chatgptQuestion
     * @return JsonResponse
     */
    public function show(ChatgptQuestion $chatgptQuestion)
    {
       // $this->authorize('view', ChatgptQuestion::class);

        return response()->json(compact('chatgptQuestion'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param ChatgptQuestion $chatgptQuestion
     * @return JsonResponse
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function update(Request $request, ChatgptQuestion $chatgptQuestion)
    {
        //$this->authorize('update', ChatgptQuestion::class);

        $data = $this->validate($request, [
            'name' => 'nullable|string',
            'page_number' => 'nullable|integer',
            'question' => 'nullable|string',
            'option_1' => 'nullable|string',
            'option_2' => 'nullable|string',
            'option_3' => 'nullable|string',
            'option_4' => 'nullable|string',
            'option_5' => 'nullable|string',
            'correct_option' => 'nullable|integer',
            'is_approved' => 'nullable|boolean',
        ]);

        if(isset($data['is_approved']) && $data['is_approved'] === true)
        {
            $data['approved_by'] = auth()->id();
        }

        if ($chatgptQuestion->update($data)) {
            return response()->json(null, Response::HTTP_NO_CONTENT);
        }

        return response()->json(['status' => 'failed'], Response::HTTP_BAD_REQUEST);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param ChatgptQuestion $chatgptQuestion
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function destroy(ChatgptQuestion $chatgptQuestion)
    {
        //$this->authorize('delete', ChatgptQuestion::class);

        if ($chatgptQuestion->delete()) {
            return response()->json(null, Response::HTTP_NO_CONTENT);
        }

        return response()->json(['status' => 'failed'], Response::HTTP_BAD_REQUEST);
    }
}
