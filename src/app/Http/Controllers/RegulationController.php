<?php

namespace App\Http\Controllers;

use App\Models\Regulation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class RegulationController extends Controller
{
    /**
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        return response()->json(Regulation::get());
    }

    /**
     * @param  string  $regulationSlug
     * @return JsonResponse
     */
    public function show(string $regulationSlug): JsonResponse
    {
        if (Cache::has(Regulation::BASE_CACHE_KEY . $regulationSlug)) {
            $regulation = Cache::get(Regulation::BASE_CACHE_KEY . $regulationSlug);
        } else {
            $regulation = Regulation::where('slug', $regulationSlug)
                ->select('name', 'slug', 'text', 'summary')
                ->first()
                ->toArray();
            Cache::put(Regulation::BASE_CACHE_KEY . $regulationSlug, $regulation);
        }

        return response()->json($regulation);
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

        $regulation->update($validatedData);

        return response()->json(['status' => 'success']);
    }
}
