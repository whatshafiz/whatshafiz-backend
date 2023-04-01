<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Spatie\Permission\Models\Permission;
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
    public function user_can_not_list_roles_when_has_not_admin_role()
    {
        $loginUser = User::factory()->create();

        $response = $this->actingAs($loginUser)->json('GET', $this->uri);

        $response->assertForbidden();
    }

    /** @test */
    public function user_can_list_roles_when_has_admin_role()
    {
        $loginUser = User::factory()->create();
        $loginUser->assignRole('Admin');

        $roles = Role::where('name', '!=', 'Admin')->latest('id');

        $response = $this->actingAs($loginUser)->json('GET', $this->uri);

        $response->assertOk();

        foreach ($roles as $role) {
            $response->assertJsonFragment($role->toArray());
        }
    }

    /** @test */
    public function user_can_not_view_role_when_has_not_admin_role()
    {
        $loginUser = User::factory()->create();

        $role = Role::inRandomOrder()->first();

        $response = $this->actingAs($loginUser)->json('GET', $this->uri . '/' . $role->id);

        $response->assertForbidden();
    }

    /** @test */
    public function user_can_view_role_when_has_admin_role()
    {
        $loginUser = User::factory()->create();
        $loginUser->assignRole('Admin');

        $role = Role::inRandomOrder()->where('name', '!=', 'Admin')->first();

        $response = $this->actingAs($loginUser)->json('GET', $this->uri . '/' . $role->id);

        $response->assertOk()
            ->assertJsonFragment($role->toArray());
    }

    /** @test */
    public function user_can_not_create_role_when_has_not_admin_role()
    {
        $loginUser = User::factory()->create();

        $newRoleData = ['name' => $this->faker->jobTitle . rand(100, 999)];

        $response = $this->actingAs($loginUser)->json('POST', $this->uri, $newRoleData);

        $response->assertForbidden();
    }

    /** @test */
    public function user_can_create_role_when_has_admin_role()
    {
        $loginUser = User::factory()->create();
        $loginUser->assignRole('Admin');
        $permissions = Permission::inRandomOrder()->limit(rand(0, 5))->get();
        $permisssionIds = $permissions->pluck('id')->toArray();

        $newRoleData = [
            'name' => $this->faker->jobTitle . rand(100, 999),
            'permissions' => $permisssionIds,
        ];

        $response = $this->actingAs($loginUser)->json('POST', $this->uri, $newRoleData);

        unset($newRoleData['permissions']);

        $response->assertOk()
            ->assertJsonFragment($newRoleData);

        foreach ($permissions as $permission) {
            $response->assertJsonFragment($permission->toArray());
        }
    }

    /** @test */
    public function user_can_not_update_role_when_has_not_admin_role()
    {
        $loginUser = User::factory()->create();

        $role = Role::inRandomOrder()->first();

        $response = $this->actingAs($loginUser)->json('PUT', $this->uri . '/' . $role->id);

        $response->assertForbidden();
    }

    /** @test */
    public function user_can_update_role_when_has_admin_role()
    {
        $loginUser = User::factory()->create();
        $loginUser->assignRole('Admin');
        $permissions = Permission::inRandomOrder()->limit(2)->get();
        $permisssionIds = $permissions->pluck('id')->toArray();

        $role = Role::where('name', '!=', 'Admin')->inRandomOrder()->first();

        $newRoleData = [
            'name' => $this->faker->jobTitle . rand(100, 999),
            'permissions' => $permisssionIds,
        ];

        $response = $this->actingAs($loginUser)->json('PUT', $this->uri . '/' . $role->id, $newRoleData);

        unset($newRoleData['permissions']);

        $response->assertOk()
            ->assertJsonFragment($newRoleData);

        foreach ($permissions as $permission) {
            $response->assertJsonFragment($permission->toArray());
        }

        $this->assertDatabaseHas('roles', ['id' => $role->id, 'name' => $newRoleData['name']]);
    }

    /** @test */
    public function user_can_not_delete_role_when_has_not_admin_role()
    {
        $loginUser = User::factory()->create();

        $role = Role::inRandomOrder()->first();

        $response = $this->actingAs($loginUser)->json('DELETE', $this->uri . '/' . $role->id);

        $response->assertForbidden();
    }

    /** @test */
    public function user_can_not_delete_role_when_role_has_users_even_has_admin_role()
    {
        $loginUser = User::factory()->create();
        $loginUser->assignRole('Admin');

        $role = Role::inRandomOrder()->where('name', '!=', 'Admin')->first();
        User::factory()->create()->assignRole($role->name);

        $response = $this->actingAs($loginUser)->json('DELETE', $this->uri . '/' . $role->id);

        $response->assertUnprocessable()
            ->assertJsonFragment(['message' => 'Rol silinemez, çünkü atanmış kullanıcılar mevcut.']);
    }

    /** @test */
    public function user_can_delete_role_when_has_admin_role()
    {
        $loginUser = User::factory()->create();
        $loginUser->assignRole('Admin');

        $role = Role::inRandomOrder()->where('name', '!=', 'Admin')->first();
        $role->users()->detach();

        $response = $this->actingAs($loginUser)->json('DELETE', $this->uri . '/' . $role->id);

        $response->assertSuccessful();
    }
}
