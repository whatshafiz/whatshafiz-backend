<?php

namespace Tests\Feature;

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
    public function it_should_get_hafizol_regulations_from_cache_when_cached_before()
    {
        $hafizolRegulation = Regulation::where('slug', 'hafizol')->first();

        Cache::shouldReceive('has')->with(Regulation::BASE_CACHE_KEY . 'hafizol')->once()->andReturn(true);
        Cache::shouldReceive('get')->with(Regulation::BASE_CACHE_KEY . 'hafizol')->once()->andReturn($hafizolRegulation);

        $response = $this->json('GET', $this->uri . '/hafizol');

        $response->assertOk()
            ->assertJsonFragment([
                'name' => 'HafızOl',
                'slug' => 'hafizol',
                'summary' => $hafizolRegulation->summary,
                'text' => $hafizolRegulation->text,
            ]);
    }

    /** @test */
    public function it_should_get_hafizol_regulations_from_database_and_put_it_to_cache_when_did_not_cached_before()
    {
        $hafizolRegulation = Regulation::where('slug', 'hafizol')->first();

        Cache::shouldReceive('has')->with(Regulation::BASE_CACHE_KEY . 'hafizol')->once()->andReturn(false);
        Cache::shouldReceive('get')->with(Regulation::BASE_CACHE_KEY . 'hafizol')->never();
        Cache::shouldReceive('put')->once();

        $response = $this->json('GET', $this->uri . '/hafizol');

        $response->assertOk()
            ->assertJsonFragment([
                'name' => 'HafızOl',
                'slug' => 'hafizol',
                'summary' => $hafizolRegulation->summary,
                'text' => $hafizolRegulation->text,
            ]);
    }

    /** @test */
    public function it_should_get_hafizol_regulations()
    {
        $hafizolRegulation = Regulation::where('slug', 'hafizol')->first();

        $response = $this->json('GET', $this->uri . '/hafizol');

        $response->assertOk()
            ->assertJsonFragment([
                'name' => 'HafızOl',
                'slug' => 'hafizol',
                'summary' => $hafizolRegulation->summary,
                'text' => $hafizolRegulation->text,
            ]);
    }

    /** @test */
    public function it_should_get_hafizkal_regulations()
    {
        $hafizkalRegulation = Regulation::where('slug', 'hafizkal')->first();

        $response = $this->json('GET', $this->uri . '/hafizkal');

        $response->assertOk()
            ->assertJsonFragment([
                'name' => 'HafızKal',
                'slug' => 'hafizkal',
                'summary' => $hafizkalRegulation->summary,
                'text' => $hafizkalRegulation->text,
            ]);
    }

    /** @test */
    public function it_should_get_whatsenglish_regulations()
    {
        $whatsenglishRegulation = Regulation::where('slug', 'whatsenglish')->first();

        $response = $this->json('GET', $this->uri . '/whatsenglish');

        $response->assertOk()
            ->assertJsonFragment([
                'name' => 'WhatsEnglish',
                'slug' => 'whatsenglish',
                'summary' => $whatsenglishRegulation->summary,
                'text' => $whatsenglishRegulation->text,
            ]);
    }

    /** @test */
    public function it_should_get_whatsarapp_regulations()
    {
        $whatsarappRegulation = Regulation::where('slug', 'whatsarapp')->first();

        $response = $this->json('GET', $this->uri . '/whatsarapp');

        $response->assertOk()
            ->assertJsonFragment([
                'name' => 'WhatsArapp',
                'slug' => 'whatsarapp',
                'summary' => $whatsarappRegulation->summary,
                'text' => $whatsarappRegulation->text,
            ]);
    }

    /** @test */
    public function it_should_list_regulations_when_has_permission()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('regulations.list');

        $response = $this->actingAs($user)->json('GET', $this->uri);

        $response->assertOk()
            ->assertJsonFragment(['name' => 'HafızKal', 'slug' => 'hafizkal'])
            ->assertJsonFragment(['name' => 'HafızOl', 'slug' => 'hafizol'])
            ->assertJsonFragment(['name' => 'WhatsEnglish', 'slug' => 'whatsenglish'])
            ->assertJsonFragment(['name' => 'WhatsArapp', 'slug' => 'whatsarapp']);
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
