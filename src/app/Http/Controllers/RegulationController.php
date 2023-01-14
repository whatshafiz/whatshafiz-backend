<?php

namespace App\Http\Controllers;

use App\Models\Regulation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RegulationController extends Controller
{
    /**
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $this->authorize('list', Regulation::class);

        return response()->json(Regulation::get());
    }

    /**
     * @param  Regulation  $regulation
     * @return JsonResponse
     */
    public function show(Regulation $regulation): JsonResponse
    {
        return response()->json($regulation->toArray());
    }

    /**
     * @param  Request  $request
     * @param  Regulation  $regulation
     * @return JsonResponse
     */
    public function update(Request $request, Regulation $regulation): JsonResponse
    {
        $this->authorize('update', Regulation::class);

        $validatedData = $this->validate(
            $request,
            [
                'summary' => 'nullable|string',
                'text' => 'required|string',
            ]
        );

        if ($regulation->update($validatedData)) {
            return response()->json(['status' => 'success']);
        }

        return response()->json(['status' => 'failed'], Response::HTTP_BAD_REQUEST);
    }
}
