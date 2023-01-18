<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\BaseFeatureTest;

class UserTest extends BaseFeatureTest
{
    protected string $uri;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->uri = self::BASE_URI . '/users';
    }

    /** @test */
    public function it_should_not_get_users_list_when_does_not_have_permission()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->json('GET', $this->uri);

        $response->assertForbidden();
    }

    /** @test */
    public function it_should_get_users_list_when_has_permission()
    {
        $users = User::factory()->count(2, 5)->create();
        $user = User::factory()->create();
        $user->givePermissionTo('users.list');

        $response = $this->actingAs($user)->json('GET', $this->uri);

        $response->assertOk();

        foreach ($users as $user) {
            $response->assertJsonFragment($user->toArray());
        }
    }

    /** @test */
    public function it_should_check_a_phone_number_registered_or_not()
    {
        $registeredUser = User::factory()->create();

        $response = $this->json('POST', $this->uri . '/check', ['phone_number' => $registeredUser->phone_number]);

        $response->assertOk()
            ->assertJsonFragment(['phone_number' => $registeredUser->phone_number, 'is_registered' => true]);

        $unregisteredUser = User::factory()->make();

        $response = $this->json('POST', $this->uri . '/check', ['phone_number' => $unregisteredUser->phone_number]);

        $response->assertOk()
            ->assertJsonFragment(['phone_number' => $unregisteredUser->phone_number, 'is_registered' => false]);
    }

    /** @test */
    public function it_should_check_a_phone_number_banned_or_not()
    {
        $normalUser = User::factory()->create();

        $response = $this->json('POST', $this->uri . '/check', ['phone_number' => $normalUser->phone_number]);

        $response->assertOk()
            ->assertJsonFragment([
                'phone_number' => $normalUser->phone_number,
                'is_registered' => true,
                'is_banned' => false,
            ]);

        $bannedUser = User::factory()->create(['is_banned' => true]);

        $response = $this->json('POST', $this->uri . '/check', ['phone_number' => $bannedUser->phone_number]);

        $response->assertOk()
            ->assertJsonFragment([
                'phone_number' => $bannedUser->phone_number,
                'is_registered' => true,
                'is_banned' => true,
            ]);
    }
}
