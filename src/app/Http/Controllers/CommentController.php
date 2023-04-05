<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CommentController extends Controller
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
        $this->authorize('viewAny', Comment::class);

        $filters = $this->validate(
            $request,
            [
                'type' => 'nullable|string|in:whatshafiz,whatsenglish,whatsarapp',
                'commented_by_id' => 'nullable|integer|exists:users,id',
                'approved_by_id' => 'nullable|integer|exists:users,id',
                'is_approved' => 'nullable|boolean',
            ]
        );

        $comments = Comment::with(['commentedBy', 'approvedBy'])
            ->when(isset($filters['type']), function ($query) use ($filters) {
                return $query->where('type', $filters['type']);
            })
            ->when(isset($filters['commented_by_id']), function ($query) use ($filters) {
                return $query->where('commented_by_id', $filters['commented_by_id']);
            })
            ->when(isset($filters['approved_by_id']), function ($query) use ($filters) {
                return $query->where('approved_by_id', $filters['approved_by_id']);
            })
            ->when(isset($filters['is_approved']), function ($query) use ($filters) {
                return $query->where('is_approved', $filters['is_approved']);
            })
            ->orderByTabulator($request)
            ->paginate($request->size)
            ->appends($filters);

        return response()->json($comments->toArray());
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function myComments(Request $request): JsonResponse
    {
        $comments = Comment::where('commented_by_id', Auth::id())
            ->orderByTabulator($request)
            ->paginate($request->size);

        return response()->json($comments->toArray());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return JsonResponse
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Comment::class);

        $validatedCommentData = $this->validate(
            $request,
            [
                'type' => 'required|string|in:whatshafiz,whatsenglish,whatsarapp',
                'title' => 'required|string|min:3|max:100',
                'comment' => 'required|string|min:3|max:1000',
            ]
        );

        $validatedCommentData['commented_by_id'] = Auth::id();
        $comment = Comment::create($validatedCommentData);

        return response()->json(compact('comment'));
    }

    /**
     * Display the specified resource.
     *
     * @param  Comment  $comment
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function show(Comment $comment): JsonResponse
    {
        $this->authorize('view', $comment);

        return response()->json(compact('comment'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  Request  $request
     * @param  Comment  $comment
     * @return JsonResponse
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function update(Request $request, Comment $comment): JsonResponse
    {
        $this->authorize('update', $comment);

        $validationRules = [
            'type' => 'required|string|in:whatshafiz,whatsenglish,whatsarapp',
            'title' => 'required|string|min:3|max:100',
            'comment' => 'required|string|min:3|max:1000',
        ];

        if (Auth::user()->hasPermissionTo('comments.update')) {
            $validationRules['is_approved'] = 'nullable|boolean';
        }

        $validatedCommentData = $this->validate($request, $validationRules);

        if (!$comment->is_approved && !empty($validatedCommentData['is_approved'])) {
            $validatedCommentData['approved_by_id'] = Auth::id();
            $validatedCommentData['approved_at'] = Carbon::now();
        }

        $comment->update($validatedCommentData);

        return response()->json(compact('comment'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  Comment  $comment
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function destroy(Comment $comment): JsonResponse
    {
        $this->authorize('delete', $comment);

        $comment->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
