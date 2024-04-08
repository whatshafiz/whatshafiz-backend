<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseType;
use App\Models\Regulation;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;
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
    public function it_should_create_regulations_when_has_permission()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('regulations.create');

        $regulationData = Regulation::factory()->raw(['course_type_id' => CourseType::factory()->create()->id]);

        $response = $this->actingAs($user)->json('POST', $this->uri, $regulationData);

        $response->assertCreated();

        $this->assertDatabaseHas('regulations', $regulationData);
    }

    /** @test */
    public function it_should_not_update_regulations_when_does_not_have_permission()
    {
        $user = User::factory()->create();
        $regulation = Regulation::inRandomOrder()->first();

        $response = $this->actingAs($user)->json('PUT', $this->uri . '/' . $regulation->id, $regulation->toArray());

        $response->assertForbidden();
    }

    /** @test */
    public function it_should_update_regulations_when_has_permission()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('regulations.update');

        $courseType = CourseType::factory()->create();
        $regulation = Regulation::factory()->create(['course_type_id' => $courseType->id]);

        $regulationData = Regulation::factory()->raw(['course_type_id' => $courseType->id]);

        $response = $this->actingAs($user)->json('PUT', $this->uri . '/' . $regulation->id, $regulationData);

        $response->assertOk();

        $this->assertDatabaseHas('regulations', array_merge(['id' => $regulation->id], $regulationData));
    }

    /** @test */
    public function it_should_not_delete_regulation_when_regulation_has_course()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('regulations.delete');

        $courseType = CourseType::factory()->create();
        $regulation = Regulation::factory()->create(['course_type_id' => $courseType->id]);
        Course::factory()->create(['course_type_id' => $courseType->id]);

        $response = $this->actingAs($user)->json('DELETE', $this->uri . '/' . $regulation->id);

        $response->assertStatus(Response::HTTP_BAD_REQUEST);

        $this->assertNotSoftDeleted($regulation);
    }

    /** @test */
    public function it_should_delete_regulation_when_has_permission_and_regulation_has_not_course()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('regulations.delete');

        $courseType = CourseType::factory()->create();
        $regulation = Regulation::factory()->create(['course_type_id' => $courseType->id]);

        $response = $this->actingAs($user)->json('DELETE', $this->uri . '/' . $regulation->id);

        $response->assertSuccessful();

        $this->assertSoftDeleted($regulation);
    }
}
