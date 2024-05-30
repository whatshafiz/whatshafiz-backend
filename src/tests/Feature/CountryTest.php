<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Country;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Tests\BaseFeatureTest;

class CountryTest extends BaseFeatureTest
{
    protected string $uri;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->uri = self::BASE_URI . '/countries';
    }

    /** @test */
    public function it_should_get_country_list()
    {
        Cache::shouldReceive('has')->with('countries')->once()->andReturn(false);
        Cache::shouldReceive('get')->with('countries')->never();
        Cache::shouldReceive('put')->once();

        $response = $this->json('GET', $this->uri);

        $response->assertOk();

        foreach (Country::get() as $country) {
            $response->assertJsonFragment($country->toArray());
        }
    }

    /** @test */
    public function it_should_get_country_details()
    {
        $user = User::factory()->create();

        $country = Country::inRandomOrder()->first();

        $response = $this->actingAs($user)->json('GET', $this->uri . '/' . $country->id);

        $response->assertOk()
            ->assertJsonFragment($country->toArray());
    }

    /** @test */
    public function it_should_get_country_list_from_cache_when_countries_data_cached_before()
    {
        $dummyCountries = Country::inRandomOrder()->take(rand(3, 5))->get();

        Cache::shouldReceive('has')->with('countries')->once()->andReturn(true);
        Cache::shouldReceive('get')->with('countries')->once()->andReturn($dummyCountries);

        $response = $this->json('GET', $this->uri);

        $response->assertOk();

        foreach ($dummyCountries as $dummyCountry) {
            $response->assertJsonFragment($dummyCountry->toArray());
        }
    }

    /** @test */
    public function it_should_paginate_country_list()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('countries.update');

        $perPage = 10;

        $response = $this->actingAs($user)->json('GET', $this->uri . '/paginate', ['size' => $perPage]);

        $response->assertOk()
            ->assertJsonFragment(['per_page' => $perPage]);

        foreach (Country::take($perPage)->latest('id')->get() as $country) {
            $response->assertJsonFragment($country->toArray());
        }
    }

    /** @test */
    public function it_should_paginate_country_list_by_filtering()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('countries.update');

        $searchCountry = Country::inRandomOrder()->first();
        $searchQuery = [
            'filter' => [['value' => $searchCountry->name]],
        ];

        $response = $this->actingAs($user)->json('GET', $this->uri . '/paginate', $searchQuery);

        $response->assertOk()
            ->assertJsonFragment($searchCountry->toArray());
    }

    /** @test */
    public function it_should_paginate_city_list()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('countries.update');

        $perPage = 10;

        $response = $this->actingAs($user)->json('GET', self::BASE_URI . '/cities/paginate', ['size' => $perPage]);

        $response->assertOk()
            ->assertJsonFragment(['per_page' => $perPage]);

        foreach (City::take($perPage)->latest('id')->get() as $city) {
            $response->assertJsonFragment($city->toArray());
        }
    }

    /** @test */
    public function it_should_paginate_city_list_by_filtering()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('countries.update');

        $searchCity = City::inRandomOrder()->first();
        $searchQuery = [
            'filter' => [['value' => $searchCity->name]],
        ];

        $response = $this->actingAs($user)->json('GET', self::BASE_URI . '/cities/paginate', $searchQuery);

        $response->assertOk()
            ->assertJsonFragment($searchCity->toArray());
    }

    /** @test */
    public function it_should_get_country_cities()
    {
        $country = Country::inRandomOrder()->first();
        $cities = City::factory()->count(1, 5)->create(['country_id' => $country->id]);
        $cacheKey = 'countries:' . $country->id . ':cities';
        Cache::shouldReceive('has')->with($cacheKey)->once()->andReturn(false);
        Cache::shouldReceive('get')->with($cacheKey)->never();
        Cache::shouldReceive('put')->once();
        $user = User::factory()->create();

        $response = $this->actingAs($user)->json('GET', $this->uri . '/' . $country->id . '/cities');

        $response->assertOk();

        foreach ($cities as $city) {
            $response->assertJsonFragment($city->only('id', 'name'));
        }
    }

    /** @test */
    public function it_should_get_country_cities_from_cache_when_cities_data_cached_before()
    {
        $country = Country::inRandomOrder()->first();
        $cities = City::factory()->count(1, 5)->create(['country_id' => $country->id]);
        $cacheKey = 'countries:' . $country->id . ':cities';
        Cache::shouldReceive('has')->with($cacheKey)->once()->andReturn(true);
        Cache::shouldReceive('get')->with($cacheKey)->once()->andReturn($cities);
        $user = User::factory()->create();

        $response = $this->actingAs($user)->json('GET', $this->uri . '/' . $country->id . '/cities');

        $response->assertOk();

        foreach ($cities as $city) {
            $response->assertJsonFragment($city->only('id', 'name'));
        }
    }

    /** @test */
    public function it_should_get_city_details()
    {
        $user = User::factory()->create();
        $city = City::inRandomOrder()->first();

        $response = $this->actingAs($user)->json('GET', self::BASE_URI . '/cities/' . $city->id);

        $response->assertSuccessful()
            ->assertJsonFragment($city->only('id', 'name'));
    }

    /** @test */
    public function it_should_create_city_for_a_country_and_invalidate_cache()
    {
        $country = Country::where('name', '!=', 'Türkiye')->inRandomOrder()->first();
        $cityData = City::factory()->raw(['country_id' => $country->id]);
        $cacheKey = 'countries:' . $country->id . ':cities';
        Cache::shouldReceive('forget')->with($cacheKey)->once();
        $user = User::factory()->create();

        $response = $this->actingAs($user)->json('POST', $this->uri . '/' . $country->id . '/cities', $cityData);

        $response->assertCreated()
            ->assertJsonFragment($cityData);

        $this->assertDatabaseHas('cities', $cityData);
    }

    /** @test */
    public function it_should_not_update_city_details_when_does_not_have_permission()
    {
        $user = User::factory()->create();

        $city = City::inRandomOrder()->first();
        $newCityData = [
            'country_id' => Country::inRandomOrder()->first('id')->id,
            'name' => $this->faker->words(3, true),
        ];

        $response = $this->actingAs($user)->json('PUT', self::BASE_URI . '/cities/' . $city->id, $newCityData);

        $response->assertForbidden();
    }

    /** @test */
    public function it_should_update_city_details_when_has_permission()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('countries.update');

        $city = City::inRandomOrder()->first();
        $newCityData = [
            'country_id' => Country::inRandomOrder()->first('id')->id,
            'name' => $this->faker->city,
        ];

        $response = $this->actingAs($user)->json('PUT', self::BASE_URI . '/cities/' . $city->id, $newCityData);

        $response->assertSuccessful();

        $this->assertDatabaseHas('cities', array_merge(['id' => $city->id], $newCityData));
    }

    /** @test */
    public function it_should_not_update_country_details_when_does_not_have_permission()
    {
        $user = User::factory()->create();

        $country = Country::where('name', '!=', 'Türkiye')->inRandomOrder()->first();
        $newCountryData = [
            'name' => $this->faker->words(3, true),
            'iso' => $this->faker->lexify('iso-???'),
            'phone_code' => $this->faker->numerify('+###'),
        ];

        $response = $this->actingAs($user)->json('PUT', $this->uri . '/' . $country->id, $newCountryData);

        $response->assertForbidden();
    }

    /** @test */
    public function it_should_update_country_details_when_has_permission()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('countries.update');

        $country = Country::where('name', '!=', 'Türkiye')->inRandomOrder()->first();
        $newCountryData = [
            'name' => $this->faker->words(3, true),
            'iso' => $this->faker->lexify('iso-???'),
            'phone_code' => $this->faker->numerify('+###'),
        ];

        $response = $this->actingAs($user)->json('PUT', $this->uri . '/' . $country->id, $newCountryData);

        $response->assertSuccessful();

        $this->assertDatabaseHas('countries', array_merge(['id' => $country->id], $newCountryData));
    }

    /** @test */
    public function it_should_not_delete_country_details_when_does_not_have_permission()
    {
        $user = User::factory()->create();

        $country = Country::whereDoesntHave('cities')->inRandomOrder()->first();

        $response = $this->actingAs($user)->json('DELETE', $this->uri . '/' . $country->id);

        $response->assertForbidden();
    }

    /** @test */
    public function it_should_not_delete_country_details_when_country_has_cities()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('countries.delete');

        $country = Country::whereHas('cities')->inRandomOrder()->first();

        $response = $this->actingAs($user)->json('DELETE', $this->uri . '/' . $country->id);

        $response->assertUnprocessable();
    }

    /** @test */
    public function it_should_not_delete_country_details_when_country_has_users()
    {
        $user = User::factory()->create(['country_id' => Country::inRandomOrder()->first()->id]);
        $user->givePermissionTo('countries.delete');

        $response = $this->actingAs($user)->json('DELETE', $this->uri . '/' . $user->country_id);

        $response->assertUnprocessable();
    }

    /** @test */
    public function it_should_delete_country_details_when_has_permission()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('countries.delete');

        $country = Country::whereDoesntHave('cities')->whereDoesntHave('users')->inRandomOrder()->first();

        $response = $this->actingAs($user)->json('DELETE', $this->uri . '/' . $country->id);

        $response->assertSuccessful();
    }

    /** @test */
    public function it_should_not_delete_city_details_when_city_has_users()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('countries.delete');

        $city = City::whereHas('users')->inRandomOrder()->first();

        $response = $this->actingAs($user)->json('DELETE', self::BASE_URI . '/cities/' . $city->id);

        $response->assertUnprocessable();
    }

    /** @test */
    public function it_should_delete_city_details_when_has_permission()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('countries.delete');

        $city = City::factory()->create();

        $response = $this->actingAs($user)->json('DELETE', self::BASE_URI . '/cities/' . $city->id);

        $response->assertSuccessful();
    }
}
