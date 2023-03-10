<?php

namespace Tests\Feature;

use App\Models\Complaint;
use App\Models\User;
use Tests\BaseFeatureTest;

class ComplaintTest extends BaseFeatureTest
{
    protected string $uri;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->uri = self::BASE_URI . '/complaints';
    }

    /** @test */
    public function it_should_not_get_complaints_list_when_does_not_have_permission()
    {
        $compalints = Complaint::factory()->count(5)->create();
        $user = User::factory()->create();

        $response = $this->actingAs($user)->json('GET', $this->uri);

        $response->assertForbidden();
    }

    /** @test */
    public function it_should_get_complaints_list_when_does_have_permission()
    {
        $compalints = Complaint::factory()->count(5)->create();
        $user = User::factory()->create();
        $user->givePermissionTo('complaints.list');

        $response = $this->actingAs($user)->json('GET', $this->uri);

        $response->assertOk();
        foreach ($compalints as $compalint) {
            $response->assertJsonFragment($compalint->toArray());
        }
    }

    /** @test */
    public function it_should_not_get_complaints_when_does_not_have_permission()
    {
        $complaint = Complaint::factory()->create();

        $user = User::factory()->create();

        $response = $this->actingAs($user)->json('GET', $this->uri . '/' . $complaint->id);

        $response->assertForbidden();
    }

    /** @test */
    public function it_should_get_complaints_when_has_permission_to_view_any()
    {
        $complaint = Complaint::factory()->create();

        $user = User::factory()->create()->givePermissionTo('complaints.list');

        $response = $this->actingAs($user)->json('GET', $this->uri . '/' . $complaint->id);

        $response->assertOk();
        $response->assertJsonFragment(
            ['complaint' => $complaint->load('createdUser', 'reviewedUser', 'relatedUser')->toArray()]
        );
    }

    /** @test */
    public function it_should_get_complaints_when_created_user_requested()
    {
        $complaint = Complaint::factory()->create();

        $response = $this->actingAs($complaint->createdUser)
            ->json('GET', $this->uri . '/' . $complaint->id);

        $response->assertOk();
        $response->assertJsonFragment(
            ['complaint' => $complaint->load('createdUser', 'reviewedUser', 'relatedUser')->toArray()]
        );
    }

    /** @test */
    public function it_should_not_update_complain_when_does_not_have_permission()
    {
        $complaint = Complaint::factory()->create();

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->json(
                'PUT',
                $this->uri . '/' . $complaint->id,
                ['description' => 'new description',]
            );

        $response->assertForbidden();
    }

    /** @test */
    public function it_should_update_complain_when_has_permission()
    {
        $complaint = Complaint::factory()->create();

        $user = User::factory()->create()->givePermissionTo('complaints.update');

        $response = $this->actingAs($user)
            ->json(
                'PUT',
                $this->uri . '/' . $complaint->id,
                ['description' => 'new description',]
            );

        $response->assertSuccessful();
    }

    /** @test */
    public function it_should_update_complain_when_created_user_requested()
    {
        $complaint = Complaint::factory()->create();

        $response = $this->actingAs($complaint->createdUser)
            ->json(
                'PUT',
                $this->uri . '/' . $complaint->id,
                ['description' => 'new description',]
            );

        $response->assertSuccessful();
    }
}
