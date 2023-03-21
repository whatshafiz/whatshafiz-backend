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

        $searchPermission = Permission::inRandomOrder()->first();
        $searchQuery = [
            'name' => $searchPermission->name,
        ];

        $response = $this->actingAs($user)->json('GET', $this->uri, $searchQuery);

        $response->assertOk()
            ->assertJsonFragment($searchPermission->toArray());
    }
}
