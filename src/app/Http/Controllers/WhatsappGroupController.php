<?php

namespace App\Http\Controllers;

use App\Models\WhatsappGroup;
use App\Models\WhatsappGroupUser;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WhatsappGroupController extends Controller
{
    /**
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', WhatsappGroup::class);

        return response()->json(WhatsappGroup::latest()->with('course')->paginate()->toArray());
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

        return response()->json($whatsappGroup->load('users.user')->toArray());
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
                'name' => 'required|string|max:100|unique:whatsapp_groups,name,' . $whatsappGroup->id,
                'is_active' => 'required|boolean',
                'join_url' => 'required|url',
            ]
        );

        $whatsappGroup->update($validatedWhatsappGroupData);

        return response()->json(null, Response::HTTP_NO_CONTENT);
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
