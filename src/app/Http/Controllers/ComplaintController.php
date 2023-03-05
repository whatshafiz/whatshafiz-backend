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
     * @return JsonResponse
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Complaint::class);

        $requestData = $this->validate(
            $request,
            [
                'created_by' => 'nullable|integer',
                'reviewed_by' => 'nullable|integer',
                'reviewed_at' => 'nullable|date',
                'is_fixed' => 'nullable|boolean',
                'result' => 'nullable|string',
                'subject' => 'nullable|string',
                'description' => 'nullable|string',
                'related_user_id' => 'nullable|integer',
            ]
        );

        $complaints = Complaint::latest()
            ->when(isset($requestData['created_by']), function ($query) use ($requestData) {
                return $query->where('created_by', $requestData['created_by']);
            })
            ->when(isset($requestData['reviewed_by']), function ($query) use ($requestData) {
                return $query->where('reviewed_by', $requestData['reviewed_by']);
            })
            ->when(isset($requestData['reviewed_at']), function ($query) use ($requestData) {
                return $query->where('reviewed_at', $requestData['reviewed_at']);
            })
            ->when(isset($requestData['is_fixed']), function ($query) use ($requestData) {
                return $query->where('is_fixed', $requestData['is_fixed']);
            })
            ->when(isset($requestData['result']), function ($query) use ($requestData) {
                return $query->where('result', $requestData['result']);
            })
            ->when(isset($requestData['subject']), function ($query) use ($requestData) {
                return $query->where('subject', $requestData['subject']);
            })
            ->when(isset($requestData['description']), function ($query) use ($requestData) {
                return $query->where('description', $requestData['description']);
            })
            ->when(isset($requestData['related_user_id']), function ($query) use ($requestData) {
                return $query->where('related_user_id', $requestData['related_user_id']);
            })
            ->latest()
            ->get();

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
                'created_by' => 'nullable|integer',
                'reviewed_by' => 'nullable|integer',
                'reviewed_at' => 'nullable|date',
                'is_fixed' => 'nullable|boolean',
                'result' => 'nullable|string',
                'subject' => 'nullable|string',
                'description' => 'nullable|string',
                'related_user_id' => 'nullable|integer',
            ]
        );

        $complaints = Complaint::myComplaints()
            ->when(isset($requestData['is_fixed']), function ($query) use ($requestData) {
                return $query->where('is_fixed', $requestData['is_fixed']);
            })->latest()->get();

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
        $requestData = $this->validate(
            $request,
            [
                'subject' => 'required|string',
                'description' => 'required|string',
                'related_user_id' => 'nullable|integer',
            ]
        );

        $complaint = Complaint::create([
            'subject' => $requestData['subject'],
            'description' => $requestData['description'],
            'related_user_id' => $requestData['related_user_id'] ?? null,
            'created_by' => auth()->id()
        ]);

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

        $requestData = $this->validate(
            $request,
            [
                'subject' => 'nullable|string',
                'description' => 'nullable|string',
                'related_user_id' => 'nullable|integer',
                'result' => 'nullable|string',
                'is_fixed' => 'nullable|boolean'
            ]
        );

        if (auth()->id() !== $complaint->created_by) {
            $requestData['reviewed_by'] = auth()->id();
            $requestData['reviewed_at'] = now();
        }

        if ($complaint->update($requestData)) {
            return response()->json(null, Response::HTTP_NO_CONTENT);
        }

        return response()->json(['status' => 'failed'], Response::HTTP_BAD_REQUEST);
    }
}
