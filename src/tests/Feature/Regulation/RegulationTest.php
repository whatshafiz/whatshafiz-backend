<?php

namespace Tests\Feature\Regulation;

use App\Models\Regulation;
use App\Models\User;
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
    public function it_should_get_hafizol_regulations()
    {
        $response = $this->json('GET', $this->uri . '/hafizol');

        $response->assertOk()
            ->assertJsonFragment([
                'name' => 'HafızOl',
                'slug' => 'hafizol',
                'text' => Regulation::where('slug', 'hafizol')->value('text'),
            ]);
    }

    /** @test */
    public function it_should_get_hafizkal_regulations()
    {
        $response = $this->json('GET', $this->uri . '/hafizkal');

        $response->assertOk()
            ->assertJsonFragment([
                'name' => 'HafızKal',
                'slug' => 'hafizkal',
                'text' => Regulation::where('slug', 'hafizkal')->value('text'),
            ]);
    }

    /** @test */
    public function it_should_not_list_regulations_when_does_not_have_permission()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->json('GET', $this->uri);

        $response->assertForbidden();
    }

    /** @test */
    public function it_should_list_regulations_when_has_permission()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('regulations.list');

        $response = $this->actingAs($user)->json('GET', $this->uri);

        $response->assertOk()
            ->assertJsonFragment(['name' => 'HafızKal', 'slug' => 'hafizkal'])
            ->assertJsonFragment(['name' => 'HafızOl', 'slug' => 'hafizol']);
    }

    /** @test */
    public function it_should_not_update_regulations_when_does_not_have_permission()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->json(
                'POST',
                $this->uri . '/' . ($this->faker->randomElement(['hafizol', 'hafizkal'])),
                ['text' => $this->faker->paragraph(2)]
            );

        $response->assertForbidden();
    }

    /** @test */
    public function it_should_update_regulations_when_has_permission()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('regulations.update');

        $regulationSlug = $this->faker->randomElement(['hafizol', 'hafizkal']);
        $newRegulationText = $this->faker->paragraph(rand(1, 5));

        $response = $this->actingAs($user)
            ->json(
                'POST',
                $this->uri . '/' . $regulationSlug,
                ['text' => $newRegulationText]
            );

        $response->assertOk();

        $this->assertDatabaseHas('regulations', ['slug' => $regulationSlug, 'text' => $newRegulationText]);
    }
}
