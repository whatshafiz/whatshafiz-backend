<?php

namespace App\Http\Controllers;

use App\Models\WhatsappGroup;
use App\Models\WhatsappGroupUser;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class WhatsappGroupController extends Controller
{
    /**
     * @param  Request  $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', [WhatsappGroup::class, $request->user_id]);

        $filters = $this->validate(
            $request,
            [
                'user_id' => 'nullable|integer|exists:users,id',
                'course_id' => 'nullable|integer|min:1|exists:courses,id',
            ]
        );
        $searchKey = $this->getTabulatorSearchKey($request);

        $whatsappGroups = WhatsappGroup::with('course')
            ->withCount('users')
            ->when(isset($filters['user_id']), function ($query) use ($filters) {
                return $query->whereHas('users', function ($subQuery) use ($filters) {
                    return $subQuery->where('user_id', $filters['user_id']);
                });
            })
            ->when(isset($filters['course_id']), function ($query) use ($filters) {
                return $query->whereHas('course', function ($subQuery) use ($filters) {
                    return $subQuery->where('course_id', $filters['course_id']);
                });
            })
            ->when(!empty($searchKey), function ($query) use ($searchKey) {
                return $query->where(function ($subQuery) use ($searchKey) {
                    return $subQuery->where('id', $searchKey)
                        ->orWhere('course_id', $searchKey)
                        ->orWhere('type', $searchKey)
                        ->orWhere('gender', $searchKey)
                        ->orWhere('name', 'LIKE', '%' . $searchKey . '%')
                        ->orWhere('join_url', 'LIKE', '%' . $searchKey . '%')
                        ->orWhereHas('course', function ($subQuery) use ($searchKey) {
                            return $subQuery->where('id', $searchKey)
                                ->orWhere('name', 'LIKE', '%' . $searchKey . '%');
                        });
                });
            })
            ->orderByTabulator($request)
            ->paginate($request->size)
            ->appends(array_merge($this->filters, $filters));

        return response()->json($whatsappGroups->toArray());
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function myWhatsappGroups(Request $request): JsonResponse
    {
        $request->merge(['user_id' => Auth::id()]);

        return $this->index($request);
    }

    /**
     * @param  Request  $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', WhatsappGroup::class);

        $validatedWhatsappGroupData = $this->validate(
            $request,
            [
                'course_id' => 'required|integer|min:0|exists:courses,id',
                'type' => 'required|string|in:whatshafiz,whatsenglish,whatsarapp',
                'gender' => 'required|string|in:male,female',
                'name' => 'required|string|max:100|unique:whatsapp_groups,name',
                'is_active' => 'required|boolean',
                'join_url' => 'required|url',
            ]
        );

        return response()->json(WhatsappGroup::create($validatedWhatsappGroupData)->toArray(), Response::HTTP_CREATED);
    }

    /**
     * @param  WhatsappGroup  $whatsappGroup
     * @return JsonResponse
     */
    public function show(WhatsappGroup $whatsappGroup): JsonResponse
    {
        $this->authorize('view', [WhatsappGroup::class, $whatsappGroup]);

        $whatsappGroup = $whatsappGroup->load('users.user', 'course')->toArray();

        return response()->json(compact('whatsappGroup'));
    }

    /**
     * @param  Request  $request
     * @param  WhatsappGroup  $whatsappGroup
     * @return JsonResponse
     */
    public function update(Request $request, WhatsappGroup $whatsappGroup): JsonResponse
    {
        $this->authorize('update', [WhatsappGroup::class, $whatsappGroup]);

        $validatedWhatsappGroupData = $this->validate(
            $request,
            [
                'course_id' => 'required|integer|min:0|exists:courses,id',
                'type' => 'required|string|in:whatshafiz,whatsenglish,whatsarapp',
                'gender' => 'required|string|in:male,female',
                'name' => 'required|string|max:100|unique:whatsapp_groups,name,' . $whatsappGroup->id,
                'is_active' => 'required|boolean',
                'join_url' => 'required|url',
            ]
        );

        $whatsappGroup->update($validatedWhatsappGroupData);

        return response()->json(compact('whatsappGroup'));
    }

    /**
     * @param  WhatsappGroup  $whatsappGroup
     * @return JsonResponse
     */
    public function destroy(WhatsappGroup $whatsappGroup): JsonResponse
    {
        $this->authorize('delete', WhatsappGroup::class);

        $whatsappGroup->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @param  Request  $request
     * @param  WhatsappGroup  $whatsappGroup
     * @return JsonResponse
     */
    public function createUser(Request $request, WhatsappGroup $whatsappGroup): JsonResponse
    {
        $this->authorize('update', [WhatsappGroup::class, $whatsappGroup]);

        $validatedWhatsappGroupUserData = $this->validate(
            $request,
            [
                'user_id' => 'required|integer|min:0|exists:users,id' .
                    '|unique:whatsapp_group_users,user_id,NULL,NULL,whatsapp_group_id,' . $whatsappGroup->id,
                'role_type' => 'required|string|in:hafizol,hafizkal',
                'is_moderator' => 'required|boolean',
                'moderation_started_at' => 'required_if:is_moderator,true|nullable|date_format:Y-m-d H:i:s',
            ]
        );

        $validatedWhatsappGroupUserData['joined_at'] = Carbon::now();
        $whatsappGroupUser = $whatsappGroup->users()->create($validatedWhatsappGroupUserData);

        return response()->json($whatsappGroupUser->refresh()->load('user')->toArray(), Response::HTTP_CREATED);
    }

    /**
     * @param  Request  $request
     * @param  WhatsappGroup  $whatsappGroup
     * @param  WhatsappGroupUser  $whatsappGroupUser
     * @return JsonResponse
     */
    public function updateUser(
        Request $request,
        WhatsappGroup $whatsappGroup,
        WhatsappGroupUser $whatsappGroupUser
    ): JsonResponse {
        $this->authorize('update', [WhatsappGroup::class, $whatsappGroup]);

        $validatedWhatsappGroupUserData = $this->validate(
            $request,
            [
                'role_type' => 'required|string|in:hafizol,hafizkal',
                'is_moderator' => 'required|boolean',
                'moderation_started_at' => 'required_if:is_moderator,true|nullable|date_format:Y-m-d H:i:s',
            ]
        );

        $whatsappGroupUser->update($validatedWhatsappGroupUserData);

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @param  WhatsappGroup  $whatsappGroup
     * @param  WhatsappGroupUser  $whatsappGroupUser
     * @return JsonResponse
     */
    public function destroyUser(WhatsappGroup $whatsappGroup, WhatsappGroupUser $whatsappGroupUser): JsonResponse
    {
        $this->authorize('update', [WhatsappGroup::class, $whatsappGroup]);

        $whatsappGroupUser->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
