<?php

namespace App\Http\Controllers;

use App\Models\Country;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class CountryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $countries = Cache::has('countries') ? Cache::get('countries') : Country::get();

        return response()->json(compact('countries'));
    }

    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function cities(Country $country): JsonResponse
    {
        $cacheKey = "countries:{$country->id}:cities";
        $cities = Cache::has($cacheKey) ? Cache::get($cacheKey) : $country->cities()->get(['id', 'name']);

        return response()->json(compact('cities'));
    }

    /**
     * Display a listing of the resource.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function createCity(Request $request, Country $country): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|min:2|max:50|unique:cities,name,NULL,NULL,country_id,' . $country->id,
        ]);

        $city = $country->cities()->create(['name' => $request->name]);

        Cache::forget("countries:{$country->id}:cities");

        return response()->json(compact('city'), Response::HTTP_CREATED);
    }
}
