<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePeriodRequest;
use App\Http\Requests\UpdatePeriodRequest;
use App\Models\Period;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PeriodController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Period::class);

        return response()->json(Period::latest()->get());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Period::class);

        $validatedPeriodData = $this->validate(
            $request,
            [
                'type' => 'required|string|in:hafizol,hafizkal',
                'name' => 'required|string|min:3|max:100|unique:periods',
                'is_active' => 'required|boolean',
                'can_be_applied' => [
                    'required',
                    'boolean',
                    function ($attribute, $can_be_applied, $fail) use ($request) {
                        if ($can_be_applied &&
                            Period::where('can_be_applied', true)->where('type', $request->type)->exists()
                        ) {
                            $fail('Mevcutta zaten başvuruya açık dönem bulunuyor.');
                        }
                    },
                ],
                'can_be_applied_until' => 'nullable|date_format:Y-m-d H:i:s',
            ]
        );

        Period::create($validatedPeriodData);

        return response()->json([], Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     *
     * @param  Period  $period
     * @return JsonResponse
     */
    public function show(Period $period): JsonResponse
    {
        $this->authorize('view', Period::class);

        return response()->json($period->toArray());
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @param  Period  $period
     * @return JsonResponse
     */
    public function update(Request $request, Period $period): JsonResponse
    {
        $this->authorize('update', Period::class);

        $validatedPeriodData = $this->validate(
            $request,
            [
                'type' => 'required|string|in:hafizol,hafizkal',
                'name' => 'required|string|min:3|max:100|unique:periods',
                'is_active' => 'required|boolean',
                'can_be_applied' => [
                    'required',
                    'boolean',
                    function ($attribute, $can_be_applied, $fail) use ($request) {
                        if ($can_be_applied &&
                            Period::where('can_be_applied', true)->where('type', $request->type)->exists()
                        ) {
                            $fail('Mevcutta zaten başvuruya açık dönem bulunuyor.');
                        }
                    },
                ],
                'can_be_applied_until' => 'nullable|date_format:Y-m-d H:i:s',
            ]
        );

        $period->update($validatedPeriodData);

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  Period  $period
     * @return JsonResponse
     */
    public function destroy(Period $period): JsonResponse
    {
        $this->authorize('delete', Period::class);

        //TODO: Period için başvuru yapılmışsa, aktif dönem varsa vb. durumlarda silme işlemi kontrole bağlı olacak.
        $period->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
