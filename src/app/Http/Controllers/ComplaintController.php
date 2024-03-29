<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class ComplaintController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', [Complaint::class, $request->created_by]);

        $filters = $this->validate(
            $request,
            [
                'is_resolved' => 'nullable|boolean',
                'created_by' => 'nullable|integer|min:0|exists:users,id',
                'reviewed_by' => 'nullable|integer|min:0|exists:users,id',
                'related_user_id' => 'nullable|integer|min:0|exists:users,id',
            ]
        );

        $searchKey = $this->getTabulatorSearchKey($request);

        $complaints = Complaint::with('createdUser', 'reviewedUser', 'relatedUser')
            ->when(isset($filters['is_resolved']), function ($query) use ($filters) {
                return $query->where('is_resolved', $filters['is_resolved']);
            })
            ->when(isset($filters['created_by']), function ($query) use ($filters) {
                return $query->where('created_by', $filters['created_by']);
            })
            ->when(isset($filters['reviewed_by']), function ($query) use ($filters) {
                return $query->where('reviewed_by', $filters['reviewed_by']);
            })
            ->when(isset($filters['related_user_id']), function ($query) use ($filters) {
                return $query->where('related_user_id', $filters['related_user_id']);
            })
            ->when(!empty($searchKey), function ($query) use ($searchKey) {
                return $query->where(function ($subQuery) use ($searchKey) {
                    return $subQuery->where('id', $searchKey)
                        ->orWhere('result', 'LIKE', '%' . $searchKey . '%')
                        ->orWhere('subject', 'LIKE', '%' . $searchKey . '%')
                        ->orWhere('description', 'LIKE', '%' . $searchKey . '%')
                        ->orWhereHas('createdUser', function ($subQuery) use ($searchKey) {
                            return $subQuery->where('name', 'LIKE', '%' . $searchKey . '%')
                                ->orWhere('surname', 'LIKE', '%' . $searchKey . '%');
                        })
                        ->orWhereHas('reviewedUser', function ($subQuery) use ($searchKey) {
                            return $subQuery->where('name', 'LIKE', '%' . $searchKey . '%')
                                ->orWhere('surname', 'LIKE', '%' . $searchKey . '%');
                        })
                        ->orWhereHas('relatedUser', function ($subQuery) use ($searchKey) {
                            return $subQuery->where('name', 'LIKE', '%' . $searchKey . '%')
                                ->orWhere('surname', 'LIKE', '%' . $searchKey . '%');
                        });
                });
            })
            ->orderByTabulator($request)
            ->paginate($request->size)
            ->appends(array_merge($this->filters, $filters));

        return response()->json($complaints->toArray());
    }

    /**
     * @param Complaint $complaint
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function show(Complaint $complaint): JsonResponse
    {
        $this->authorize('view', [Complaint::class, $complaint]);

        $complaint->load('createdUser', 'reviewedUser', 'relatedUser');

        return response()->json(compact('complaint'));
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function myComplaints(Request $request): JsonResponse
    {
        $request->merge(['created_by' => Auth::id()]);

        return $this->index($request);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function store(Request $request): JsonResponse
    {
        $validatedComplaintData = $this->validate(
            $request,
            [
                'subject' => 'required|string|max:255',
                'description' => 'required|string|max:255',
                'related_user_id' => 'nullable|integer|min:0|exists:users,id',
            ]
        );

        $validatedComplaintData['created_by'] = Auth::id();
        $complaint = Complaint::create($validatedComplaintData);

        return response()->json(compact('complaint'));
    }

    /**
     * @param Request $request
     * @param Complaint $complaint
     * @return JsonResponse
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function update(Request $request, Complaint $complaint): JsonResponse
    {
        $this->authorize('update', [Complaint::class, $complaint]);

        $validatedComplaintData = $this->validate(
            $request,
            [
                'subject' => 'nullable|string|max:255',
                'description' => 'nullable|string|max:255',
                'related_user_id' => 'nullable|integer|min:0|exists:users,id',
                'result' => 'nullable|string|max:255',
                'is_resolved' => 'nullable|boolean'
            ]
        );

        if (Auth::id() !== $complaint->created_by) {
            $validatedComplaintData['reviewed_by'] = Auth::id();
            $validatedComplaintData['reviewed_at'] = now();
        }

        $complaint->update($validatedComplaintData);

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
