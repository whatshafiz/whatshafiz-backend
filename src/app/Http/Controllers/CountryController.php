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
     * @param  Request  $request
     * @return JsonResponse
     */
    public function indexPaginate(Request $request): JsonResponse
    {
        $countries = Country::withCount('cities', 'users')->orderByTabulator($request)->paginate($request->size);

        return response()->json($countries->toArray());
    }

    /**
     * Display the specified resource.
     *
     * @param  Country  $country
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function show(Country $country): JsonResponse
    {
        $country->load('cities');

        return response()->json(compact('country'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @param  Country  $country
     * @return JsonResponse
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function update(Request $request, Country $country): JsonResponse
    {
        $this->authorize('update', Country::class);

        $validatedCountryData = $this->validate(
            $request,
            [
                'name' => 'required|string|unique:countries,name,' . $country->id,
                'iso' => 'required|string|unique:countries,iso,' . $country->id,
                'phone_code' => 'required|string|unique:countries,phone_code,' . $country->id,
            ]
        );

        $country->update($validatedCountryData);

        return response()->json(compact('country'));
    }

    /**
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

    /**
     * @param  Country  $country
     * @return JsonResponse
     */
    public function destroy(Country $country): JsonResponse
    {
        $this->authorize('delete', Country::class);

        if ($country->cities()->exists()) {
            return response()->json(
                ['message' => 'Ülke silinemez, çünkü içinde şehirler mevcut.'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        if ($country->users()->exists()) {
            return response()->json(
                ['message' => 'Ülke silinemez, çünkü bu ülkeyi seçmiş kullanıcılar mevcut.'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $country->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
