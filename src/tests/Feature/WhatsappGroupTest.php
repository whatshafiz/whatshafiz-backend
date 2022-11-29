<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WhatsappGroup;
use App\Models\WhatsappGroupUser;
use Symfony\Component\HttpFoundation\Response;
use Tests\BaseFeatureTest;

class WhatsappGroupTest extends BaseFeatureTest
{
    protected string $uri;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->uri = self::BASE_URI . '/whatsapp-groups';
    }

    /** @test */
    public function it_should_not_get_whatsapp_groups_when_does_not_have_permission_and_if_not_in_group_even_has_permission()
    {
        $whatsappGroup = WhatsappGroup::factory()->create();
        $user = User::factory()->create();

        $response = $this->actingAs($user)->json('GET', $this->uri . '/' . $whatsappGroup->id);

        $response->assertForbidden();

        $user->givePermissionTo('whatsappGroups.view'); 

        $response = $this->actingAs($user)->json('GET', $this->uri . '/' . $whatsappGroup->id);

        $response->assertForbidden();
    }

    /** @test */
    public function it_should_get_whatsapp_groups_details_when_has_permission()
    {
        $whatsappGroup = WhatsappGroup::factory()->create();
        $whatsappGroupUsers = WhatsappGroupUser::factory()
            ->count(rand(1, 3))
            ->create(['whatsapp_group_id' => $whatsappGroup->id]);
        $user = User::find($whatsappGroupUsers->random()->user_id);
        $user->givePermissionTo('whatsappGroups.view');

        $response = $this->actingAs($user)->json('GET', $this->uri . '/' . $whatsappGroup->id);

        $response->assertOk()
            ->assertJsonFragment($whatsappGroup->toArray());

        foreach ($whatsappGroupUsers as $whatsappGroupUser) {
            $response->assertJsonFragment($whatsappGroupUser->toArray());
        }
    }

    /** @test */
    public function it_should_not_get_whatsapp_groups_list_when_does_not_have_permission()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->json('GET', $this->uri);

        $response->assertForbidden();
    }

    /** @test */
    public function it_should_get_whatsapp_groups_list_when_has_permission()
    {
        $whatsappGroups = WhatsappGroup::factory()->count(2, 5)->create();
        $user = User::factory()->create();
        $user->givePermissionTo('whatsappGroups.list');

        $response = $this->actingAs($user)->json('GET', $this->uri);

        $response->assertOk();

        foreach ($whatsappGroups as $whatsappGroup) {
            $response->assertJsonFragment($whatsappGroup->toArray());
        }
    }

    /** @test */
    public function it_should_not_create_whatsapp_groups_when_does_not_have_permission()
    {
        $user = User::factory()->create();
        $whatsappGroupData = WhatsappGroup::factory()->raw();

        $response = $this->actingAs($user)->json('POST', $this->uri, $whatsappGroupData);

        $response->assertForbidden();
    }

    /** @test */
    public function it_should_create_whatsapp_groups_when_has_permission()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('whatsappGroups.create');

        $whatsappGroupData = WhatsappGroup::factory()->raw();

        $response = $this->actingAs($user)->json('POST', $this->uri, $whatsappGroupData);

        $response->assertCreated();

        $this->assertDatabaseHas('whatsapp_groups', $whatsappGroupData);
    }

    /** @test */
    public function it_should_not_update_whatsapp_groups_when_does_not_have_permission()
    {
        $whatsappGroup = WhatsappGroup::factory()->create();
        $user = User::factory()->create();
        $whatsappGroupData = WhatsappGroup::factory()->raw();

        $response = $this->actingAs($user)->json('PUT', $this->uri . '/' . $whatsappGroup->id, $whatsappGroupData);

        $response->assertForbidden();
    }

    /** @test */
    public function it_should_update_whatsapp_groups_when_has_permission()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('whatsappGroups.update');
        $whatsappGroup = WhatsappGroup::factory()->create();

        $whatsappGroupData = WhatsappGroup::factory()->raw();

        $response = $this->actingAs($user)->json('PUT', $this->uri . '/' . $whatsappGroup->id, $whatsappGroupData);

        $response->assertSuccessful();

        $this->assertDatabaseHas('whatsapp_groups', array_merge(['id' => $whatsappGroup->id], $whatsappGroupData));
    }

    /** @test */
    public function it_should_update_whatsapp_groups_when_user_is_moderator_of_group()
    {
        $user = User::factory()->create();
        $whatsappGroup = WhatsappGroup::factory()->create();
        whatsappGroupUser::factory()
            ->create(['user_id' => $user->id, 'whatsapp_group_id' => $whatsappGroup->id, 'is_moderator' => true]);

        $whatsappGroupData = WhatsappGroup::factory()->raw();

        $response = $this->actingAs($user)->json('PUT', $this->uri . '/' . $whatsappGroup->id, $whatsappGroupData);

        $response->assertSuccessful();

        $this->assertDatabaseHas('whatsapp_groups', array_merge(['id' => $whatsappGroup->id], $whatsappGroupData));
    }

    /** @test */
    public function it_should_not_delete_whatsapp_groups_when_does_not_have_permission()
    {
        $whatsappGroup = WhatsappGroup::factory()->create();
        $user = User::factory()->create();

        $response = $this->actingAs($user)->json('DELETE', $this->uri . '/' . $whatsappGroup->id);

        $response->assertForbidden();
    }

    /** @test */
    public function it_should_delete_whatsapp_groups_when_has_permission()
    {
        $whatsappGroup = WhatsappGroup::factory()->create();
        $user = User::factory()->create();
        $user->givePermissionTo('whatsappGroups.delete');

        $response = $this->actingAs($user)->json('DELETE', $this->uri . '/' . $whatsappGroup->id);

        $response->assertSuccessful();

        $this->assertSoftDeleted('whatsapp_groups', ['id' => $whatsappGroup->id]);
    }
}
