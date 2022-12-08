<?php

namespace Tests\Feature;

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\Response;
use Tests\BaseFeatureTest;

class ProfileTest extends BaseFeatureTest
{
    protected string $uri;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->uri = self::BASE_URI . '/profile';
    }

    /** @test */
    public function it_should_get_own_profile_details()
    {
        $registeredUser = User::factory()->create();
        $permissions = Permission::inRandomOrder()->take(rand(1, 5))->get()->sortBy('name')->pluck('name');
        $roles = Role::inRandomOrder()->take(rand(1, 5))->get()->sortBy('name')->pluck('name');
        $registeredUser->givePermissionTo($permissions);
        $registeredUser->assignRole($roles);

        $response = $this->actingAs($registeredUser)->json('GET', $this->uri);

        $response->assertOk()
            ->assertJsonFragment(['user' => $registeredUser->toArray()])
            ->assertJsonFragment(['permissions' => $permissions])
            ->assertJsonFragment(['roles' => $roles]);
    }

    /** @test */
    public function it_should_update_own_profile_details()
    {
        $registeredUser = User::factory()->create();
        $newUserData = User::factory()
            ->make([
                'name' => $this->faker->firstname(),
                'surname' => $this->faker->lastname(),
                'gender' => $this->faker->randomElement(['male', 'female']),
            ])
            ->only([
                'name',
                'surname',
                'email',
                'gender',
                'country_id',
                'city_id',
                'university_id',
                'university_faculty_id',
                'university_department_id',
            ]);

        $response = $this->actingAs($registeredUser)->json('PUT', $this->uri, $newUserData);

        $response->assertOk();

        $this->assertDatabaseHas('users', array_merge(['id' => $registeredUser->id], $newUserData));
    }
}
