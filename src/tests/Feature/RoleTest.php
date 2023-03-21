<?php

namespace Tests\Feature;

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\BaseFeatureTest;

class RoleTest extends BaseFeatureTest
{
    protected string $uri;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->uri = self::BASE_URI . '/roles';
    }

    /** @test */
    public function user_can_not_list_roles_when_has_not_permission()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->json('GET', $this->uri);

        $response->assertForbidden();
    }

    /** @test */
    public function user_can_list_roles_when_has_permission()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('roles.list');

        $roles = Role::latest('id');

        $response = $this->actingAs($user)->json('GET', $this->uri);

        $response->assertOk();

        foreach ($roles as $role) {
            $response->assertJsonFragment($role->toArray());
        }
    }

    /** @test */
    public function user_can_filter_roles_while_listing_and_has_permission()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('roles.list');

        $searchRole = Role::inRandomOrder()->first();
        $searchQuery = [
            'name' => $searchRole->name,
        ];

        $response = $this->actingAs($user)->json('GET', $this->uri, $searchQuery);

        $response->assertOk()
            ->assertJsonFragment($searchRole->toArray());
    }

    /** @test */
    public function user_can_not_create_role_when_has_not_permission()
    {
        $user = User::factory()->create();

        $newRole = [
            'name' => 'new role',
            'guard_name' => 'web',
        ];
        $response = $this->actingAs($user)->json('POST', $this->uri, $newRole);

        $response->assertForbidden();
    }

    /** @test */
    public function user_can_create_role_when_admin()
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        $role = [
            'name' => 'new role',
            'guard_name' => 'web',
        ];

        $response = $this->actingAs($user)->json('POST', $this->uri, $role);

        $response->assertOk()
            ->assertJsonFragment($role);
    }

    /** @test */
    public function user_can_not_update_role_when_has_not_permission()
    {
        $user = User::factory()->create();

        $role = Role::inRandomOrder()->first();

        $response = $this->actingAs($user)->json('PUT', $this->uri . '/' . $role->id);

        $response->assertForbidden();
    }

    /** @test */
    public function user_can_update_role_when_admin()
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        $role = Role::inRandomOrder()->first();

        $newRole = [
            'name' => 'new role',
            'guard_name' => 'web',
            ];

        $response = $this->actingAs($user)->json('PUT', $this->uri . '/' . $role->id, $newRole);

        $response->assertOk()
            ->assertJsonFragment($newRole);
    }

    /** @test */
    public function user_can_not_delete_role_when_has_not_permission()
    {
        $user = User::factory()->create();

        $role = Role::inRandomOrder()->first();

        $response = $this->actingAs($user)->json('DELETE', $this->uri . '/' . $role->id);

        $response->assertForbidden();
    }

    /** @test */
    public function user_can_delete_role_when_admin()
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        $role = Role::inRandomOrder()->first();

        $response = $this->actingAs($user)->json('DELETE', $this->uri . '/' . $role->id);

        $response->assertNoContent();
    }

    /** @test */
    public function user_can_not_view_role_when_has_not_permission()
    {
        $user = User::factory()->create();

        $role = Role::inRandomOrder()->first();

        $response = $this->actingAs($user)->json('GET', $this->uri . '/' . $role->id);

        $response->assertForbidden();
    }

    /** @test */
    public function user_can_view_role_when_has_permission()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('roles.view');

        $role = Role::inRandomOrder()->first();

        $response = $this->actingAs($user)->json('GET', $this->uri . '/' . $role->id);

        $response->assertOk()
            ->assertJsonFragment($role->toArray());
    }

    /** @test */
    public function user_can_not_assign_permission_to_role_when_has_not_permission()
    {
        $user = User::factory()->create();

        $role = Role::inRandomOrder()->first();
        $permissions = Permission::inRandomOrder()->limit(5)->get()->pluck('id');

        $response = $this->actingAs($user)->json('POST', $this->uri . '-permission/' . $role->id, [
            'permissions' => $permissions,
        ]);

        $response->assertForbidden();
    }

    /** @test */
    public function user_can_assign_permission_to_role_when_admin()
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        $role = Role::inRandomOrder()->first();
        $permissions = Permission::inRandomOrder()->limit(5)->get()->pluck('id');

        $response = $this->actingAs($user)->json(
            'POST',
            $this->uri . '-permission/' . $role->id,
            [
            'permissions' => $permissions
            ]
        );

        $response->assertSuccessful();
    }

    /** @test */
    public function user_can_not_assign_role_to_user_when_has_not_permission()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $roles = Role::inRandomOrder()->limit(5)->get()->pluck('id');

        $response = $this->actingAs($user)->json(
            'POST',
            $this->uri . '-user/' . $otherUser->id,
            [
            'roles' => $roles,
            ]
        );

        $response->assertForbidden();
    }

    /** @test */
    public function user_can_assign_role_to_user_when_has_permission()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('roles.user-update');

        $otherUser = User::factory()->create();

        $roles = Role::inRandomOrder()->limit(5)->get()->pluck('id');

        $response = $this->actingAs($user)->json(
            'POST',
            $this->uri . '-user/' . $otherUser->id,
            [
            'roles' => $roles
            ]
        );

        $response->assertSuccessful();
    }

    /** @test */
    public function user_can_not_view_user_roles_when_has_not_permission()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $response = $this->actingAs($user)->json('GET', $this->uri . '-user/' . $otherUser->id);

        $response->assertForbidden();
    }

    /** @test */
    public function user_can_view_user_roles_when_has_permission()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('roles.user-view');

        $otherUser = User::factory()->create();
        $role = Role::inRandomOrder()->first();
        $otherUser->assignRole($role);

        $response = $this->actingAs($user)->json('GET', $this->uri . '-user/' . $otherUser->id);

        $response->assertOk()
            ->assertJsonFragment($role->toArray());
    }
}
