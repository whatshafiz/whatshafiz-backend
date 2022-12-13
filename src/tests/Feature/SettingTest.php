<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;
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
        $user = User::factory()->create();

        $response = $this->actingAs($user)->json('GET', $this->uri);

        $response->assertOk();

        foreach ($settings as $setting) {
            $response->assertJsonFragment($setting->only('id', 'name', 'value'));
        }
    }

    /** @test */
    public function it_should_get_settings_list_when_logged_in_from_cache_when_settings_list_cached_before()
    {
        $settings = Setting::factory()->count(round(3, 5))->create();
        Cache::shouldReceive('has')->with('settings')->once()->andReturn(true);
        Cache::shouldReceive('get')->with('settings')->once()->andReturn($settings);
        Cache::shouldReceive('put')->never();
        $user = User::factory()->create();

        $response = $this->actingAs($user)->json('GET', $this->uri);

        $response->assertOk();

        foreach ($settings as $setting) {
            $response->assertJsonFragment($setting->only('id', 'name', 'value'));
        }
    }
}
