<?php

namespace App\Http\Controllers;

use App\Models\WhatsappGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WhatsappGroupController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', WhatsappGroup::class);

        return response()->json(WhatsappGroup::latest()->paginate()->toArray());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', WhatsappGroup::class);

        $validatedWhatsappGroupData = $this->validate(
            $request,
            [
                'period_id' => 'required|integer|min:0|exists:periods,id',
                'type' => 'required|string|in:whatshafiz,whatsenglish,whatsarapp',
                'name' => 'required|string|max:100|unique:whatsapp_groups,name',
                'is_active' => 'required|boolean',
                'join_url' => 'required|url',
            ]
        );

        return response()->json(WhatsappGroup::create($validatedWhatsappGroupData)->toArray(), Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     *
     * @param  WhatsappGroup  $whatsappGroup
     * @return JsonResponse
     */
    public function show(WhatsappGroup $whatsappGroup): JsonResponse
    {
        $this->authorize('view', [WhatsappGroup::class, $whatsappGroup]);

        return response()->json($whatsappGroup->load('users.user')->toArray());
    }

    /**
     * Update the specified resource in storage.
     *
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
                'period_id' => 'required|integer|min:0|exists:periods,id',
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
     * Remove the specified resource from storage.
     *
     * @param  WhatsappGroup  $whatsappGroup
     * @return JsonResponse
     */
    public function destroy(WhatsappGroup $whatsappGroup): JsonResponse
    {
        $this->authorize('delete', WhatsappGroup::class);

        $whatsappGroup->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
