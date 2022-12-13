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
}
