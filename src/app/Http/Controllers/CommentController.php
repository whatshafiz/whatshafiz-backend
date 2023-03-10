<?php

namespace App\Http\Controllers;

use App\Models\Comment;
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
     * @return \Illuminate\Http\JsonResponse
     * @throws AuthorizationException|ValidationException
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Comment::class);

        $validatedRequest = $this->validate(
            $request,
            [
                'user_id' => 'nullable|integer|exists:users,id',
                'approved_by' => 'nullable|integer|exists:users,id',
                'is_approved' => 'nullable|boolean',
            ]
        );

        $comments = Comment::latest('id')
            ->when(isset($validatedRequest['user_id']), function ($query) use ($validatedRequest) {
                return $query->where('user_id', $validatedRequest['user_id']);
            })
            ->when(isset($validatedRequest['approved_by']), function ($query) use ($validatedRequest) {
                return $query->where('approved_by', $validatedRequest['approved_by']);
            })
            ->when(isset($validatedRequest['is_approved']), function ($query) use ($validatedRequest) {
                return $query->where('is_approved', $validatedRequest['is_approved']);
            })
            ->paginate()
            ->appends($validatedRequest);

        return response()->json(compact('comments'));
    }

    /**
     * @return JsonResponse
     */
    public function myComments()
    {
        $comments = Comment::where('user_id', Auth::id())->paginate();

        return response()->json(compact('comments'));
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
        $this->authorize('create', Comment::class);

        $validatedRequest = $this->validate(
            $request,
            [
                'name' => 'required|string|min:3|max:100',
                'comment' => 'required|string|min:3|max:1000',
            ]
        );

        $validatedRequest['user_id'] = Auth::id();
        $comment = Comment::create($validatedRequest);

        return response()->json(compact('comment'));
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Models\Comment $comment
     * @return \Illuminate\Http\JsonResponse
     * @throws AuthorizationException
     */
    public function show(Comment $comment)
    {
        $this->authorize('view', $comment);

        return response()->json(compact('comment'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Request $request
     * @param Comment $comment
     * @return JsonResponse
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function update(Request $request, Comment $comment)
    {
        $this->authorize('update', $comment);

        $validationRules = [
            'name' => 'nullable|string|min:3|max:100',
            'comment' => 'nullable|string|min:3|max:1000',
        ];

        if (Auth::user()->hasPermissionTo('comments.update')) {
            $validationRules['is_approved'] = 'nullable|boolean';
        }

        $validatedResponse = $this->validate(
            $request,
            $validationRules
        );

        $comment->update($validatedResponse);

        return response()->json(compact('comment'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Comment $comment
     * @return \Illuminate\Http\JsonResponse
     * @throws AuthorizationException
     */
    public function destroy(Comment $comment)
    {
        $this->authorize('delete', $comment);

        $comment->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
