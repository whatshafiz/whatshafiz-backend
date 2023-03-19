<?php

namespace Tests\Feature;

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Tests\BaseFeatureTest;

class PermissionTest extends BaseFeatureTest
{
    protected string $uri;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->uri = self::BASE_URI . '/permissions';
    }

    /** @test */
    public function user_can_not_list_permissions_when_has_not_permission()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->json('GET', $this->uri);

        $response->assertForbidden();
    }

    /** @test */
    public function user_can_list_permissions_when_has_permission()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('permissions.list');

        $permissions = Permission::latest('id');

        $response = $this->actingAs($user)->json('GET', $this->uri);

        $response->assertOk();

        foreach ($permissions as $permission) {
            $response->assertJsonFragment($permission->toArray());
        }
    }

    /** @test */
    public function user_can_filter_permissions_while_listing_and_has_permission()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('permissions.list');

        $searchPermission = Permission::inRandomOrder()->get()->first();
        $searchQuery = [
            'name' => $searchPermission->name,
        ];

        $response = $this->actingAs($user)->json('GET', $this->uri, $searchQuery);

        $response->assertOk();
        $response->assertJsonFragment($searchPermission->toArray());
    }

    /** @test */
    public function user_can_not_create_permission_when_has_not_permission()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->json('POST', $this->uri);

        $response->assertForbidden();
    }

    /** @test */
    public function user_can_create_permission_when_has_permission()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('permissions.create');

        $permission = [
            'name' => 'test.permission',
            'guard_name' => 'web',
            ];

        $response = $this->actingAs($user)->json('POST', $this->uri, $permission);

        $response->assertSuccessful();
    }

    /** @test */
    public function user_can_not_update_permission_when_has_not_permission()
    {
        $user = User::factory()->create();

        $permission = Permission::inRandomOrder()->get()->first();

        $response = $this->actingAs($user)->json('PUT', $this->uri . '/' . $permission->id);

        $response->assertForbidden();
    }

    /** @test */
    public function user_can_update_permission_when_has_permission()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('permissions.update');

        $permission = Permission::inRandomOrder()->get()->first();
        $updatedPermission = [
            'name' => 'test.permission',
            'guard_name' => 'web',
            ];

        $response = $this->actingAs($user)->json('PUT', $this->uri . '/' . $permission->id, $updatedPermission);

        print_r($response->getContent());

        $response->assertOk();
        $response->assertJsonFragment($updatedPermission);
    }

    /** @test */
    public function user_can_not_delete_permission_when_has_not_permission()
    {
        $user = User::factory()->create();

        $permission = Permission::inRandomOrder()->get()->first();

        $response = $this->actingAs($user)->json('DELETE', $this->uri . '/' . $permission->id);

        $response->assertForbidden();
    }

    /** @test */
    public function user_can_delete_permission_when_has_permission()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('permissions.delete');

        $permission = Permission::inRandomOrder()->get()->first();

        $response = $this->actingAs($user)->json('DELETE', $this->uri . '/' . $permission->id);

        $response->assertNoContent();
    }

    /** @test */
    public function user_can_view_permission_when_has_permission()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('permissions.view');

        $permission = Permission::inRandomOrder()->get()->first();

        $response = $this->actingAs($user)->json('GET', $this->uri . '/' . $permission->id);

        $response->assertOk();
        $response->assertJsonFragment($permission->toArray());
    }

    /** @test */
    public function user_can_not_view_permission_when_has_not_permission()
    {
        $user = User::factory()->create();

        $permission = Permission::inRandomOrder()->get()->first();

        $response = $this->actingAs($user)->json('GET', $this->uri . '/' . $permission->id);

        $response->assertForbidden();
    }
}
