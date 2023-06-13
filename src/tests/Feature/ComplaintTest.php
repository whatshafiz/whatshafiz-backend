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
        $complaints = Complaint::factory()->count(5)->create();
        $user = User::factory()->create();

        $response = $this->actingAs($user)->json('GET', $this->uri);

        $response->assertForbidden();
    }

    /** @test */
    public function it_should_get_complaints_list_when_does_have_permission()
    {
        $complaints = Complaint::factory()->count(5)->create();
        $user = User::factory()->create();
        $user->givePermissionTo('complaints.list');

        $response = $this->actingAs($user)->json('GET', $this->uri);

        $response->assertOk();

        foreach ($complaints as $complaint) {
            $response->assertJsonFragment($complaint->toArray());
        }
    }

    /** @test */
    public function it_should_get_complaints_list_by_filtering_when_does_have_permission()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('complaints.list');

        $complaints = Complaint::factory()->count(5)->create();
        $searchComplaint = $complaints->random();
        $searchQuery = [
            'is_resolved' => $searchComplaint->is_resolved,
            'created_by' => $searchComplaint->created_by,
            'reviewed_by' => $searchComplaint->reviewed_by ?? $user->id,
            'related_user_id' => $searchComplaint->related_user_id ?? $user->id,
            'filter' => [['value' => $searchComplaint->subject]],
        ];

        $response = $this->actingAs($user)->json('GET', $this->uri, $searchQuery);

        $response->assertOk();

        $searchQuery['subject'] = $searchQuery['filter'][0]['value'];
        unset($searchQuery['filter']);

        foreach (Complaint::where($searchQuery)->get() as $complaint) {
            $response->assertJsonFragment($complaint->toArray());
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
    public function it_should_get_complaints_that_auth_user_created()
    {
        $user = User::factory()->create();
        $userComplaints = Complaint::factory()->count(rand(1, 3))->create(['created_by' => $user->id]);


        $response = $this->actingAs($user)->json('GET', self::BASE_URI . '/my/complaints');

        $response->assertOk();

        foreach ($userComplaints as $userComplaint) {
            $response->assertJsonFragment(
                $userComplaint->load('createdUser', 'reviewedUser', 'relatedUser')->toArray()
            );
        }
    }

    /** @test */
    public function it_should_get_complaints_when_has_permission_to_view_any()
    {
        $complaint = Complaint::factory()->create();

        $user = User::factory()->create()->givePermissionTo('complaints.list');

        $response = $this->actingAs($user)->json('GET', $this->uri . '/' . $complaint->id);

        $response->assertOk()
            ->assertJsonFragment(
                ['complaint' => $complaint->load('createdUser', 'reviewedUser', 'relatedUser')->toArray()]
            );
    }

    /** @test */
    public function it_should_get_complaints_when_created_user_requested()
    {
        $complaint = Complaint::factory()->create();

        $response = $this->actingAs($complaint->createdUser)
            ->json('GET', $this->uri . '/' . $complaint->id);

        $response->assertOk()
            ->assertJsonFragment(
                ['complaint' => $complaint->load('createdUser', 'reviewedUser', 'relatedUser')->toArray()]
            );
    }

    /** @test */
    public function it_should_create_complaint()
    {
        $complaintData = Complaint::factory()->make()->only('subject', 'description', 'related_user_id');

        $user = User::factory()->create();

        $response = $this->actingAs($user)->json('POST', $this->uri, $complaintData);

        $response->assertSuccessful();

        $complaintData['created_by'] = $user->id;

        $this->assertDatabaseHas('complaints', $complaintData);
    }

    /** @test */
    public function it_should_not_update_complaint_when_does_not_have_permission()
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
    public function it_should_update_complaint_when_has_permission()
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

        $this->assertDatabaseHas('complaints', ['id' => $complaint->id, 'description' => 'new description']);
    }

    /** @test */
    public function it_should_update_complaint_when_created_user_requested()
    {
        $complaint = Complaint::factory()->create();

        $response = $this->actingAs($complaint->createdUser)
            ->json(
                'PUT',
                $this->uri . '/' . $complaint->id,
                ['description' => 'new description',]
            );

        $response->assertSuccessful();

        $this->assertDatabaseHas('complaints', ['id' => $complaint->id, 'description' => 'new description']);
    }
}
