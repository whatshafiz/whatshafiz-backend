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
        $cacheKey = 'countries';

        if (Cache::has($cacheKey)) {
            $countries = Cache::get($cacheKey);
        } else {
            $countries = Country::get();
            Cache::put($cacheKey, $countries);
        }

        return response()->json(compact('countries'));
    }

    /**
     * Display a listing of the resource.
     *
     * @param  Country  $country
     * @return JsonResponse
     */
    public function cities(Country $country): JsonResponse
    {
        $cacheKey = "countries:{$country->id}:cities";

        if (Cache::has($cacheKey)) {
            $cities = Cache::get($cacheKey);
        } else {
            $cities = $country->cities()->get(['id', 'name']);
            Cache::put($cacheKey, $cities);
        }

        return response()->json(compact('cities'));
    }

    /**
     * Display a listing of the resource.
     *
     * @param  Request  $request
     * @param  Country  $country
     * @return JsonResponse
     */
    public function storeCity(Request $request, Country $country): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|min:2|max:50|unique:cities,name,NULL,NULL,country_id,' . $country->id,
        ]);

        $city = $country->cities()->create(['name' => $request->name]);

        Cache::forget("countries:{$country->id}:cities");

        return response()->json(compact('city'), Response::HTTP_CREATED);
    }
}
