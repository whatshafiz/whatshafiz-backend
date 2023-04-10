<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use App\Models\WhatsappGroup;
use App\Models\WhatsappGroupUser;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
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
            $response->assertJsonFragment($whatsappGroup->toArray())
                ->assertJsonFragment($whatsappGroup->course->toArray());
        }
    }

    /** @test */
    public function it_should_get_whatsapp_groups_list_when_has_permission_filtering_by_course_id()
    {
        $otherWhatsappGroups = WhatsappGroup::factory()->count(2, 5)->create();
        $filterCourse = Course::factory()->create();
        $filteredWhatsappGroups = WhatsappGroup::factory()->count(2, 5)->create(['course_id' => $filterCourse->id]);
        $user = User::factory()->create();
        $user->givePermissionTo('whatsappGroups.list');

        $response = $this->actingAs($user)->json('GET', $this->uri, ['course_id' => $filterCourse->id]);

        $response->assertOk();

        foreach ($filteredWhatsappGroups as $filteredWhatsappGroup) {
            $response->assertJsonFragment($filteredWhatsappGroup->toArray())
                ->assertJsonFragment($filteredWhatsappGroup->course->toArray());
        }
    }

    /** @test */
    public function it_should_get_whatsapp_groups_list_when_has_permission_filtering_by_type()
    {
        $allTypes = collect(['whatshafiz', 'whatsenglish', 'whatsarapp']);
        $filterType = $allTypes->random();
        $filteredWhatsappGroups = WhatsappGroup::factory()->count(2, 5)->create(['type' => $filterType]);
        $otherWhatsappGroups = WhatsappGroup::factory()
            ->count(2, 5)
            ->create(['type' => $allTypes->filter(fn ($type) => $type !== $filterType)->random()]);
        $user = User::factory()->create();
        $user->givePermissionTo('whatsappGroups.list');

        $response = $this->actingAs($user)->json('GET', $this->uri, ['type' => $filterType]);

        $response->assertOk();

        foreach ($filteredWhatsappGroups as $filteredWhatsappGroup) {
            $response->assertJsonFragment($filteredWhatsappGroup->toArray())
                ->assertJsonFragment($filteredWhatsappGroup->course->toArray());
        }
    }

    /** @test */
    public function it_should_get_whatsapp_groups_list_when_has_permission_filtering_by_gender()
    {
        $allTypes = collect(['male', 'female']);
        $filterType = $allTypes->random();
        $filteredWhatsappGroups = WhatsappGroup::factory()->count(2, 5)->create(['gender' => $filterType]);
        $otherWhatsappGroups = WhatsappGroup::factory()
            ->count(2, 5)
            ->create(['gender' => $allTypes->filter(fn ($type) => $type !== $filterType)->random()]);
        $user = User::factory()->create();
        $user->givePermissionTo('whatsappGroups.list');

        $response = $this->actingAs($user)->json('GET', $this->uri, ['gender' => $filterType]);

        $response->assertOk();

        foreach ($filteredWhatsappGroups as $filteredWhatsappGroup) {
            $response->assertJsonFragment($filteredWhatsappGroup->toArray())
                ->assertJsonFragment($filteredWhatsappGroup->course->toArray());
        }
    }

    /** @test */
    public function it_should_get_whatsapp_groups_list_when_has_permission_by_searching_name()
    {
        $searchKey = Str::random(rand(7, 15));

        $filteredWhatsappGroups = collect();
        $filteredWhatsappGroups->push(WhatsappGroup::factory()->create(['name' => $searchKey]));
        $filteredWhatsappGroups->push(
            WhatsappGroup::factory()->create(['name' => Str::random(rand(1, 5)) . $searchKey])
        );
        $filteredWhatsappGroups->push(
            WhatsappGroup::factory()->create(['name' => $searchKey . Str::random(rand(1, 5))])
        );
        $filteredWhatsappGroups->push(
            WhatsappGroup::factory()->create(['name' => Str::random(rand(1, 5)) . $searchKey . Str::random(rand(1, 5))])
        );
        $otherWhatsappGroups = WhatsappGroup::factory()->count(2, 5)->create();
        $user = User::factory()->create();
        $user->givePermissionTo('whatsappGroups.list');

        $response = $this->actingAs($user)->json('GET', $this->uri, ['name' => $searchKey]);

        $response->assertOk();

        foreach ($filteredWhatsappGroups as $filteredWhatsappGroup) {
            $response->assertJsonFragment($filteredWhatsappGroup->toArray())
                ->assertJsonFragment($filteredWhatsappGroup->course->toArray());
        }
    }

    /** @test */
    public function it_should_get_whatsapp_groups_list_when_has_permission_filtering_by_activity_status()
    {
        $isActive = $this->faker->boolean;
        $filteredWhatsappGroups = WhatsappGroup::factory()->count(2, 5)->create(['is_active' => $isActive]);
        $otherWhatsappGroups = WhatsappGroup::factory()->count(2, 5)->create(['is_active' => !$isActive]);
        $user = User::factory()->create();
        $user->givePermissionTo('whatsappGroups.list');

        $response = $this->actingAs($user)->json('GET', $this->uri, ['is_active' => $isActive]);

        $response->assertOk();

        foreach ($filteredWhatsappGroups as $filteredWhatsappGroup) {
            $response->assertJsonFragment($filteredWhatsappGroup->toArray())
                ->assertJsonFragment($filteredWhatsappGroup->course->toArray());
        }
    }

    /** @test */
    public function it_should_get_whatsapp_groups_list_when_has_permission_by_searching_join_url()
    {
        $searchKey = Str::random(rand(7, 15));

        $filteredWhatsappGroups = collect();
        $filteredWhatsappGroups->push(WhatsappGroup::factory()->create(['join_url' => $searchKey]));
        $filteredWhatsappGroups->push(
            WhatsappGroup::factory()->create(['join_url' => Str::random(rand(1, 5)) . $searchKey])
        );
        $filteredWhatsappGroups->push(
            WhatsappGroup::factory()->create(['join_url' => $searchKey . Str::random(rand(1, 5))])
        );
        $filteredWhatsappGroups->push(
            WhatsappGroup::factory()
                ->create(['join_url' => Str::random(rand(1, 5)) . $searchKey . Str::random(rand(1, 5))])
        );
        $otherWhatsappGroups = WhatsappGroup::factory()->count(2, 5)->create();
        $user = User::factory()->create();
        $user->givePermissionTo('whatsappGroups.list');

        $response = $this->actingAs($user)->json('GET', $this->uri, ['join_url' => $searchKey]);

        $response->assertOk();

        foreach ($filteredWhatsappGroups as $filteredWhatsappGroup) {
            $response->assertJsonFragment($filteredWhatsappGroup->toArray())
                ->assertJsonFragment($filteredWhatsappGroup->course->toArray());
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
        WhatsappGroupUser::factory()
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

    /** @test */
    public function it_should_create_whatsapp_group_users_when_has_permission()
    {
        $now = Carbon::now();
        Carbon::setTestNow($now);
        $user = User::factory()->create();
        $user->givePermissionTo('whatsappGroups.update');
        $whatsappGroup = WhatsappGroup::factory()->create();

        $whatsappGroupUserData = WhatsappGroupUser::factory()
            ->raw([
                'user_id' => User::factory()->create()->id,
                'whatsapp_group_id' => $whatsappGroup->id,
                'joined_at' => $now->format('Y-m-d H:i:s'),
            ]);

        $response = $this->actingAs($user)
            ->json('POST', $this->uri . '/' . $whatsappGroup->id . '/users', $whatsappGroupUserData);

        $response->assertCreated();

        $this->assertDatabaseHas('whatsapp_group_users', $whatsappGroupUserData);

        $response->assertJsonFragment(WhatsappGroupUser::find($response->json('id'))->toArray());
    }

    /** @test */
    public function it_should_create_whatsapp_group_users_when_user_is_moderator_of_group()
    {
        $now = Carbon::now();
        Carbon::setTestNow($now);
        $user = User::factory()->create();
        $whatsappGroup = WhatsappGroup::factory()->create();
        WhatsappGroupUser::factory()
            ->create(['user_id' => $user->id, 'whatsapp_group_id' => $whatsappGroup->id, 'is_moderator' => true]);

        $whatsappGroupUserData = WhatsappGroupUser::factory()
            ->raw([
                'user_id' => User::factory()->create()->id,
                'whatsapp_group_id' => $whatsappGroup->id,
                'joined_at' => $now->format('Y-m-d H:i:s'),
            ]);

        $response = $this->actingAs($user)
            ->json('POST', $this->uri . '/' . $whatsappGroup->id . '/users', $whatsappGroupUserData);

        $response->assertCreated();

        $this->assertDatabaseHas('whatsapp_group_users', $whatsappGroupUserData);

        $response->assertJsonFragment(WhatsappGroupUser::find($response->json('id'))->toArray());
    }

    /** @test */
    public function it_should_update_whatsapp_group_users_when_has_permission()
    {
        $now = Carbon::now();
        Carbon::setTestNow($now);
        $user = User::factory()->create();
        $user->givePermissionTo('whatsappGroups.update');
        $whatsappGroup = WhatsappGroup::factory()->create();
        $whatsappGroupUser = WhatsappGroupUser::factory()->create(['whatsapp_group_id' => $whatsappGroup->id]);

        $whatsappGroupUserNewData = Arr::only(
            WhatsappGroupUser::factory()->raw(),
            ['role_type', 'is_moderator', 'moderation_started_at']
        );

        $response = $this->actingAs($user)
            ->json(
                'PUT',
                $this->uri . '/' . $whatsappGroup->id . '/users/' . $whatsappGroupUser->id,
                $whatsappGroupUserNewData
            );

        $response->assertSuccessful();

        $this->assertDatabaseHas(
            'whatsapp_group_users',
            array_merge($whatsappGroupUser->only('id', 'user_id'), $whatsappGroupUserNewData)
        );
    }

    /** @test */
    public function it_should_update_whatsapp_group_users_when_user_is_moderator_of_group()
    {
        $now = Carbon::now();
        Carbon::setTestNow($now);
        $user = User::factory()->create();
        $whatsappGroup = WhatsappGroup::factory()->create();
        WhatsappGroupUser::factory()
            ->create(['user_id' => $user->id, 'whatsapp_group_id' => $whatsappGroup->id, 'is_moderator' => true]);
        $whatsappGroupUser = WhatsappGroupUser::factory()->create(['whatsapp_group_id' => $whatsappGroup->id]);

        $whatsappGroupUserNewData = Arr::only(
            WhatsappGroupUser::factory()->raw(),
            ['role_type', 'is_moderator', 'moderation_started_at']
        );

        $response = $this->actingAs($user)
            ->json(
                'PUT',
                $this->uri . '/' . $whatsappGroup->id . '/users/' . $whatsappGroupUser->id,
                $whatsappGroupUserNewData
            );

        $response->assertSuccessful();

        $this->assertDatabaseHas(
            'whatsapp_group_users',
            array_merge($whatsappGroupUser->only('id', 'user_id'), $whatsappGroupUserNewData)
        );
    }

    /** @test */
    public function it_should_delete_whatsapp_group_users_when_has_permission()
    {
        $whatsappGroup = WhatsappGroup::factory()->create();
        $whatsappGroupUser = WhatsappGroupUser::factory()->create(['whatsapp_group_id' => $whatsappGroup->id]);
        $user = User::factory()->create();
        $user->givePermissionTo('whatsappGroups.update');

        $response = $this->actingAs($user)
            ->json('DELETE', $this->uri . '/' . $whatsappGroup->id . '/users/' . $whatsappGroupUser->id);

        $response->assertSuccessful();

        $this->assertSoftDeleted('whatsapp_group_users', ['id' => $whatsappGroupUser->id]);
    }

    /** @test */
    public function it_should_delete_whatsapp_group_users_when_user_is_moderator_of_group()
    {
        $whatsappGroup = WhatsappGroup::factory()->create();
        $whatsappGroupUser = WhatsappGroupUser::factory()->create(['whatsapp_group_id' => $whatsappGroup->id]);
        $user = User::factory()->create();
        WhatsappGroupUser::factory()
            ->create(['user_id' => $user->id, 'whatsapp_group_id' => $whatsappGroup->id, 'is_moderator' => true]);

        $response = $this->actingAs($user)
            ->json('DELETE', $this->uri . '/' . $whatsappGroup->id . '/users/' . $whatsappGroupUser->id);

        $response->assertSuccessful();

        $this->assertSoftDeleted('whatsapp_group_users', ['id' => $whatsappGroupUser->id]);
    }
}
