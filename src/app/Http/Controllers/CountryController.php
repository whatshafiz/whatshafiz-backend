<?php

namespace App\Http\Controllers;

use App\Models\City;
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
        $this->authorize('update', Country::class);

        $countries = Country::withCount('cities', 'users')->orderByTabulator($request)->paginate($request->size);

        return response()->json($countries->toArray());
    }

    /**
     * @param  Request  $request
     * @return JsonResponse
     */
    public function indexCitiesPaginate(Request $request): JsonResponse
    {
        $this->authorize('update', Country::class);

        $cities = City::with('country')->withCount('users')->orderByTabulator($request)->paginate($request->size);

        return response()->json($cities->toArray());
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
     * Display the specified resource.
     *
     * @param  City  $city
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function showCity(City $city): JsonResponse
    {
        return response()->json(compact('city'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @param  City  $city
     * @return JsonResponse
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function updateCity(Request $request, City $city): JsonResponse
    {
        $this->authorize('update', Country::class);

        $validatedCityData = $this->validate(
            $request,
            [
                'country_id' => 'required|integer|min:1|exists:countries,id',
                'name' => "required|string|unique:cities,name,{$city->id},id,country_id,{$request->country_id}",
            ]
        );

        if ($request->country_id !== $city->country_id) {
            Cache::forget("countries:{$city->country_id}:cities");
            Cache::forget("countries:{$request->country_id}:cities");
        }

        $city->update($validatedCityData);

        return response()->json(compact('city'));
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

    /**
     * @param  City  $city
     * @return JsonResponse
     */
    public function destroyCity(City $city): JsonResponse
    {
        $this->authorize('delete', Country::class);

        if ($city->users()->exists()) {
            return response()->json(
                ['message' => 'Şehir silinemez, çünkü bu şehri seçmiş kullanıcılar mevcut.'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        Cache::forget("countries:{$city->country_id}:cities");
        $city->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
