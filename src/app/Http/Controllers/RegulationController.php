<?php

namespace App\Http\Controllers;

use App\Models\Regulation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class RegulationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function indexPaginate(Request $request): JsonResponse
    {
        $searchKey = $this->getTabulatorSearchKey($request);

        $regulations = Regulation::with('courseType:id,name')
            ->when(!empty($searchKey), function ($query) use ($searchKey) {
                return $query->where('id', $searchKey)
                    ->orWhere('name', 'LIKE', '%' . $searchKey . '%')
                    ->orWhere('slug', 'LIKE', '%' . $searchKey . '%');
            })
            ->orderByTabulator($request)
            ->paginate($request->size)
            ->appends($this->filters);

        return response()->json($regulations->toArray());
    }

    /**
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        return response()->json(Regulation::get());
    }

    /**
     * @param  int  $regulationId
     * @return JsonResponse
     */
    public function show(int $regulationId): JsonResponse
    {
        if (Cache::has(Regulation::BASE_CACHE_KEY . $regulationId)) {
            $regulation = Cache::get(Regulation::BASE_CACHE_KEY . $regulationId);
        } else {
            $regulation = Regulation::where('id', $regulationId)
                ->select('name', 'slug', 'text', 'summary')
                ->first()
                ->toArray();
            Cache::put(Regulation::BASE_CACHE_KEY . $regulationId, $regulation);
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
        Cache::forget(Regulation::BASE_CACHE_KEY . $regulation->slug);

        return response()->json(['status' => 'success']);
    }
}
