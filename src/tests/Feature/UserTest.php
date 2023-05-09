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
    public function it_should_get_user_details_when_has_permission()
    {
        $loginUser = User::factory()->create();
        $relatedUser = User::inRandomOrder()->first();

        $response = $this->actingAs($loginUser)->json('GET', $this->uri . '/' . $relatedUser->id);

        $response->assertOk();
    }

    /** @test */
    public function it_should_not_get_users_list_when_does_not_have_permission()
    {
        $loginUser = User::factory()->create();

        $response = $this->actingAs($loginUser)->json('GET', $this->uri);

        $response->assertForbidden();
    }

    /** @test */
    public function it_should_get_users_list_when_has_permission()
    {
        $users = User::factory()->count(2, 5)->create();
        $loginUser = User::factory()->create();
        $loginUser->givePermissionTo('users.list');

        $response = $this->actingAs($loginUser)->json('GET', $this->uri);

        $response->assertOk();

        foreach ($users as $user) {
            $response->assertJsonFragment($user->toArray());
        }
    }

    /** @test */
    public function it_should_test_get_users_list_when_has_permission_and_filters()
    {
        $loginUser = User::factory()->create();
        $loginUser->givePermissionTo('users.list');

        $users = User::factory()->count(2, 5)->create();
        $userForFilter = $users->random();
        $filters = [
            'name' => $userForFilter->name,
            'surname' => $userForFilter->surname,
            'email' => $userForFilter->email,
            'gender' => $userForFilter->gender,
            'phone_number' => $userForFilter->phone_number,
            'country_id' => $userForFilter->country_id,
            'city_id' => $userForFilter->city_id,
            'university_id' => $userForFilter->university_id,
            'university_faculty_id' => $userForFilter->university_faculty_id,
            'university_department_id' => $userForFilter->university_department_id,
            'is_banned' => $userForFilter->is_banned,
        ];

        $response = $this->actingAs($loginUser)->json('GET', $this->uri, $filters);

        $response->assertOk();

        foreach (User::where($filters)->get() as $user) {
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

    /** @test */
    public function it_should_not_ban_any_user_when_does_not_have_permission()
    {
        $loginUser = User::factory()->create();

        $registeredUser = User::factory()->create();
        $newBanStatus = !$registeredUser->is_banned;

        $response = $this->actingAs($loginUser)
            ->json(
                'POST',
                $this->uri . '/' . $registeredUser->id . '/ban',
                ['is_banned' => $newBanStatus]
            );

        $response->assertForbidden();
    }

    /** @test */
    public function it_should_ban_user_when_has_permission()
    {
        $loginUser = User::factory()->create();
        $loginUser->givePermissionTo('users.delete');

        $registeredUser = User::factory()->create();
        $newBanStatus = !$registeredUser->is_banned;

        $response = $this->actingAs($loginUser)
            ->json(
                'POST',
                $this->uri . '/' . $registeredUser->id . '/ban',
                ['is_banned' => $newBanStatus]
            );

        $response->assertSuccessful();

        $this->assertDatabaseHas(
            'users',
            [
                'id' => $registeredUser->id,
                'is_banned' => $newBanStatus,
            ]
        );
    }
}
