<?php

namespace Tests\Feature;

use App\Models\Period;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;
use Tests\BaseFeatureTest;

class PeriodTest extends BaseFeatureTest
{
    protected string $uri;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->uri = self::BASE_URI . '/periods';
    }

    /** @test */
    public function it_should_not_get_period_when_does_not_have_permission()
    {
        $period = Period::factory()->create();
        $user = User::factory()->create();

        $response = $this->actingAs($user)->json('GET', $this->uri . '/' . $period->id);

        $response->assertForbidden();
    }

    /** @test */
    public function it_should_get_period_details_when_has_permission()
    {
        $period = Period::factory()->create();
        $user = User::factory()->create();
        $user->givePermissionTo('periods.view');

        $response = $this->actingAs($user)->json('GET', $this->uri . '/' . $period->id);

        $response->assertOk()
            ->assertJsonFragment($period->toArray());
    }

    /** @test */
    public function it_should_not_get_periods_list_when_does_not_have_permission()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->json('GET', $this->uri);

        $response->assertForbidden();
    }

    /** @test */
    public function it_should_get_periods_list_when_has_permission()
    {
        $periods = Period::factory()->count(2, 5)->create();
        $user = User::factory()->create();
        $user->givePermissionTo('periods.list');

        $response = $this->actingAs($user)->json('GET', $this->uri);

        $response->assertOk();

        foreach ($periods as $period) {
            $response->assertJsonFragment($period->toArray());
        }
    }

    /** @test */
    public function it_should_not_create_period_when_does_not_have_permission()
    {
        $user = User::factory()->create();
        $periodData = Period::factory()->raw();

        $response = $this->actingAs($user)->json('POST', $this->uri, $periodData);

        $response->assertForbidden();
    }

    /** @test */
    public function it_should_create_period_when_has_permission()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('periods.create');
        Period::query()->update(['can_be_applied' => false]);

        $periodData = Period::factory()->raw();

        $response = $this->actingAs($user)->json('POST', $this->uri, $periodData);

        $response->assertCreated();

        $this->assertDatabaseHas('periods', $periodData);
    }

    /** @test */
    public function it_should_not_create_period_as_can_be_applied_when_there_is_already_can_be_applied_period_for_type()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('periods.create');

        $periodData = Period::factory()->raw(['can_be_applied' => true]);
        Period::factory()->create(['can_be_applied' => true, 'type' => $periodData['type']]);

        $response = $this->actingAs($user)->json('POST', $this->uri, $periodData);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors('can_be_applied');
    }

    /** @test */
    public function it_should_not_update_period_when_does_not_have_permission()
    {
        $period = Period::factory()->create();
        $user = User::factory()->create();
        $periodData = Period::factory()->raw();

        $response = $this->actingAs($user)->json('PUT', $this->uri . '/' . $period->id, $periodData);

        $response->assertForbidden();
    }

    /** @test */
    public function it_should_update_period_when_has_permission()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('periods.update');
        Period::query()->update(['can_be_applied' => false]);
        $period = Period::factory()->create();

        $periodData = Period::factory()->raw();

        $response = $this->actingAs($user)->json('PUT', $this->uri . '/' . $period->id, $periodData);

        $response->assertSuccessful();

        $this->assertDatabaseHas('periods', array_merge(['id' => $period->id], $periodData));
    }

    /** @test */
    public function it_should_not_update_period_as_can_be_applied_when_there_is_already_can_be_applied_period_for_type()
    {
        $period = Period::factory()->create(['can_be_applied' => false]);
        Period::factory()->create(['can_be_applied' => true, 'type' => $period->type]);
        $user = User::factory()->create();
        $user->givePermissionTo('periods.update');

        $periodData = Period::factory()->raw(['can_be_applied' => true, 'type' => $period->type]);

        $response = $this->actingAs($user)->json('PUT', $this->uri . '/' . $period->id, $periodData);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors('can_be_applied');
    }

    /** @test */
    public function it_should_not_delete_period_when_does_not_have_permission()
    {
        $period = Period::factory()->create();
        $user = User::factory()->create();

        $response = $this->actingAs($user)->json('DELETE', $this->uri . '/' . $period->id);

        $response->assertForbidden();
    }

    /** @test */
    public function it_should_delete_period_when_has_permission()
    {
        $period = Period::factory()->create();
        $user = User::factory()->create();
        $user->givePermissionTo('periods.delete');

        $response = $this->actingAs($user)->json('DELETE', $this->uri . '/' . $period->id);

        $response->assertSuccessful();

        $this->assertSoftDeleted('periods', ['id' => $period->id]);
    }
}
