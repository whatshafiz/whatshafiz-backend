<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Tests\BaseFeatureTest;

class SettingTest extends BaseFeatureTest
{
    protected string $uri;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->uri = self::BASE_URI . '/settings';
    }

    /** @test */
    public function it_should_not_get_setting_list_when_logged_out()
    {
        $setting = Setting::factory()->create();

        $response = $this->json('GET', $this->uri);

        $response->assertUnauthorized();
    }

    /** @test */
    public function it_should_get_settings_list_when_logged_in()
    {
        Cache::shouldReceive('has')->with('settings')->once()->andReturn(false);
        Cache::shouldReceive('get')->with('settings')->never();
        Cache::shouldReceive('put')->once();
        $settings = Setting::factory()->count(round(3, 5))->create();
        $loginUser = User::factory()->create();

        $response = $this->actingAs($loginUser)->json('GET', $this->uri);

        $response->assertOk();

        foreach ($settings as $setting) {
            $response->assertJsonFragment($setting->only('id', 'name', 'value'));
        }
    }

    /** @test */
    public function it_should_get_settings_list_when_has_permission_by_filtering_and_as_paginated()
    {
        $user = User::factory()->create();

        $settings = Setting::factory()->count(5)->create();
        $searchSetting = $settings->random();
        $searchQuery = [
            'filter' => [['value' => $searchSetting->name]],
        ];

        $response = $this->actingAs($user)->json('GET', $this->uri . '/paginate', $searchQuery);

        $response->assertOk()
            ->assertJsonFragment($searchSetting->only('id', 'name', 'value'));
    }

    /** @test */
    public function it_should_get_setting_details()
    {
        $user = User::factory()->create();

        $setting = Setting::factory()->create();

        $response = $this->actingAs($user)->json('GET', $this->uri . '/' . $setting->id);

        $response->assertOk()
            ->assertJsonFragment($setting->only('id', 'name', 'value'));
    }

    /** @test */
    public function it_should_get_settings_list_when_logged_in_from_cache_when_settings_list_cached_before()
    {
        $settings = Setting::factory()->count(round(3, 5))->create();
        Cache::shouldReceive('has')->with('settings')->once()->andReturn(true);
        Cache::shouldReceive('get')->with('settings')->once()->andReturn($settings);
        Cache::shouldReceive('put')->never();
        $loginUser = User::factory()->create();

        $response = $this->actingAs($loginUser)->json('GET', $this->uri);

        $response->assertOk();

        foreach ($settings as $setting) {
            $response->assertJsonFragment($setting->only('id', 'name', 'value'));
        }
    }

    /** @test */
    public function it_should_not_update_settings_when_logged_in_user_has_admin_role()
    {
        $loginUser = User::factory()->create();
        $setting = Setting::factory()->create();

        $response = $this->actingAs($loginUser)
            ->json('PUT', $this->uri . '/' . $setting->id, ['value' => $this->faker->word]);

        $response->assertForbidden();
    }

    /** @test */
    public function it_should_update_settings_when_logged_in_user_has_admin_role()
    {
        $loginUser = User::factory()->create();
        $loginUser->assignRole('Admin');
        $setting = Setting::factory()->create();
        $newValue = $this->faker->word;

        $response = $this->actingAs($loginUser)
            ->json('PUT', $this->uri . '/' . $setting->id, ['value' => $newValue]);

        $response->assertSuccessful();

        $this->assertDatabaseHas('settings', ['id' => $setting->id, 'value' => $newValue]);
    }
}
