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
                ->first()
                ->toArray();

            Cache::put(Regulation::BASE_CACHE_KEY . $regulationId, $regulation);
        }

        return response()->json($regulation);
    }

    /**
     * @param  Request  $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Regulation::class);

        $validatedRegulationData = $this->validate(
            $request,
            [
                'course_type_id' => 'required|integer|min:1|exists:course_types,id' .
                    '|unique:regulations,course_type_id,NULL,id,deleted_at,NULL',
                'name' => 'required|string|min:3|max:100|unique:regulations,name,NULL,id,deleted_at,NULL',
                'slug' => 'required|string|min:3|max:100|unique:regulations,slug,NULL,id,deleted_at,NULL',
                'summary' => 'nullable|string',
                'text' => 'required|string',
            ],
            [
                'course_type_id.unique' => 'Kurs türü için yönetmelik mevcut.',
                'text.required' => 'Yönetmelik metni zorunludur.',
            ]
        );

        $regulation = Regulation::create($validatedRegulationData);

        return response()->json($regulation->toArray(), Response::HTTP_CREATED);
    }

    /**
     * @param  Request  $request
     * @param  Regulation  $regulation
     * @return JsonResponse
     */
    public function update(Request $request, Regulation $regulation): JsonResponse
    {
        $this->authorize('update', Regulation::class);

        $validatedRegulationData = $this->validate(
            $request,
            [
                'course_type_id' => 'required|integer|min:1|exists:course_types,id' .
                    '|unique:regulations,course_type_id,' . $regulation->id . ',id,deleted_at,NULL',
                'name' => 'required|string|min:3|max:100|unique:regulations,name,'
                    . $regulation->id . ',id,deleted_at,NULL',
                'slug' => 'required|string|min:3|max:100|unique:regulations,slug,'
                    . $regulation->id . ',id,deleted_at,NULL',
                'summary' => 'nullable|string',
                'text' => 'required|string',
            ],
            [
                'course_type_id.unique' => 'Kurs türü için yönetmelik mevcut.',
                'text.required' => 'Yönetmelik metni zorunludur.',
            ]
        );

        $regulation->update($validatedRegulationData);
        Cache::forget(Regulation::BASE_CACHE_KEY . $regulation->id);

        return response()->json(['status' => 'success']);
    }

    /**
     * @param  Regulation  $regulation
     * @return JsonResponse
     */
    public function destroy(Regulation $regulation): JsonResponse
    {
        $this->authorize('delete', Regulation::class);

        if ($regulation->courseType?->courses()->exists()) {
            return response()->json(['message' => 'Kurs açılmış yönetmelikler silinemez.'], Response::HTTP_BAD_REQUEST);
        }

        $regulation->delete();
        Cache::forget(Regulation::BASE_CACHE_KEY . $regulation->id);

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
