<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\User;
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
    public function user_can_not_list_permissions_when_has_not_admin_role()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->json('GET', $this->uri);

        $response->assertForbidden();
    }

    /** @test */
    public function user_can_list_permissions_when_has_admin_role()
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        $response = $this->actingAs($user)->json('GET', $this->uri);

        $response->assertOk();

        foreach (Permission::latest('id') as $permission) {
            $response->assertJsonFragment($permission->toArray());
        }
    }
}
