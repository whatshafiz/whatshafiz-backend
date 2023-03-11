<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
        $this->authorize('viewAny', Complaint::class);

        $filters = $this->validate(
            $request,
            [
                'created_by' => 'nullable|integer|min:0|exists:users,id',
                'reviewed_by' => 'nullable|integer|min:0|exists:users,id',
                'reviewed_at' => 'nullable|date_format:Y-m-d H:i:s',
                'is_fixed' => 'nullable|boolean',
                'result' => 'nullable|string|max:255',
                'subject' => 'nullable|string|max:255',
                'description' => 'nullable|string|max:255',
                'related_user_id' => 'nullable|integer|min:0|exists:users,id',
            ]
        );

        $complaints = Complaint::latest()
            ->when(isset($filters['created_by']), function ($query) use ($filters) {
                return $query->where('created_by', $filters['created_by']);
            })
            ->when(isset($filters['reviewed_by']), function ($query) use ($filters) {
                return $query->where('reviewed_by', $filters['reviewed_by']);
            })
            ->when(isset($filters['reviewed_at']), function ($query) use ($filters) {
                return $query->where('reviewed_at', $filters['reviewed_at']);
            })
            ->when(isset($filters['is_fixed']), function ($query) use ($filters) {
                return $query->where('is_fixed', $filters['is_fixed']);
            })
            ->when(isset($filters['result']), function ($query) use ($filters) {
                return $query->where('result', $filters['result']);
            })
            ->when(isset($filters['subject']), function ($query) use ($filters) {
                return $query->where('subject', $filters['subject']);
            })
            ->when(isset($filters['description']), function ($query) use ($filters) {
                return $query->where('description', $filters['description']);
            })
            ->when(isset($filters['related_user_id']), function ($query) use ($filters) {
                return $query->where('related_user_id', $filters['related_user_id']);
            })
            ->latest()
            ->paginate()
            ->appends($filters)
            ->toArray();

        return response()->json(compact('complaints'));
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
        $requestData = $this->validate(
            $request,
            [
                'is_fixed' => 'nullable|boolean',
                'subject' => 'nullable|string|max:255',
            ]
        );

        $complaints = Complaint::myComplaints()
            ->when(isset($requestData['is_fixed']), function ($query) use ($requestData) {
                return $query->where('is_fixed', $requestData['is_fixed']);
            })
            ->when(isset($requestData['subject']), function ($query) use ($requestData) {
                return $query->where('subject', $requestData['subject']);
            })
            ->latest()
            ->paginate();

        return response()->json(compact('complaints'));
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
                'is_fixed' => 'nullable|boolean'
            ]
        );

        if (auth()->id() !== $complaint->created_by) {
            $validatedComplaintData['reviewed_by'] = auth()->id();
            $validatedComplaintData['reviewed_at'] = now();
        }

        if ($complaint->update($validatedComplaintData)) {
            return response()->json(null, Response::HTTP_NO_CONTENT);
        }

        return response()->json(['status' => 'failed'], Response::HTTP_BAD_REQUEST);
    }
}
