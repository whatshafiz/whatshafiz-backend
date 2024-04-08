<?php

namespace Tests\Feature;

use App\Models\CourseType;
use App\Models\Regulation;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Tests\BaseFeatureTest;

class RegulationTest extends BaseFeatureTest
{
    protected string $uri;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->uri = self::BASE_URI . '/regulations';
    }

    /** @test */
    public function it_should_can_list_regulations_as_paginated_by_filtering()
    {
        $user = User::factory()->create();

        $searchRegulation = Regulation::inRandomOrder()->first();

        $searchQuery = [
            'filter' => [['value' => $searchRegulation->name]],
        ];

        $response = $this->actingAs($user)->json('GET', $this->uri . '/paginate', $searchQuery);

        $response->assertOk()
            ->assertJsonFragment($searchRegulation->toArray());
    }

    /** @test */
    public function it_should_get_regulation_from_cache_when_cached_before()
    {
        $regulation = Regulation::inRandomOrder()->first();

        Cache::shouldReceive('has')->with(Regulation::BASE_CACHE_KEY . $regulation->id)->once()->andReturn(true);
        Cache::shouldReceive('get')->with(Regulation::BASE_CACHE_KEY . $regulation->id)->once()->andReturn($regulation);

        $response = $this->json('GET', $this->uri . '/' . $regulation->id);

        $response->assertOk()
            ->assertJsonFragment($regulation->only('name', 'slug', 'summary', 'text'));
    }

    /** @test */
    public function it_should_get_regulation_details()
    {
        $regulation = Regulation::inRandomOrder()->first();

        $response = $this->json('GET', $this->uri . '/' . $regulation->id);

        $response->assertOk()
            ->assertJsonFragment($regulation->only('name', 'slug', 'summary', 'text'));
    }

    /** @test */
    public function it_should_list_regulations_when_has_permission()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('regulations.list');

        $response = $this->actingAs($user)->json('GET', $this->uri);

        $response->assertOk();

        foreach (Regulation::get() as $regulation) {
            $response->assertJsonFragment($regulation->toArray());
        }
    }

    /** @test */
    public function it_should_not_update_regulations_when_does_not_have_permission()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->json(
                'POST',
                $this->uri . '/' . ($this->faker->randomElement(['hafizol', 'hafizkal', 'whatsenglish', 'whatsarapp'])),
                ['summary' => $this->faker->paragraph(2), 'text' => $this->faker->paragraph(2)]
            );

        $response->assertForbidden();
    }

    /** @test */
    public function it_should_update_regulations_when_has_permission()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('regulations.update');

        $regulationSlug = $this->faker->randomElement(['hafizol', 'hafizkal', 'whatsenglish', 'whatsarapp']);
        $newRegulationSummary = $this->faker->paragraph(rand(1, 5));
        $newRegulationText = $this->faker->paragraph(rand(1, 5));

        $response = $this->actingAs($user)
            ->json(
                'POST',
                $this->uri . '/' . $regulationSlug,
                ['summary' => $newRegulationSummary, 'text' => $newRegulationText]
            );

        $response->assertOk();

        $this->assertDatabaseHas(
            'regulations',
            ['slug' => $regulationSlug, 'summary' => $newRegulationSummary, 'text' => $newRegulationText]
        );
    }
}
