<?php

namespace Tests\Feature\User;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;
use Tests\BaseFeatureTest;

class RegisterTest extends BaseFeatureTest
{
    protected string $uri;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->uri = self::BASE_URI . '/register';
    }

    /** @test */
    public function it_should_not_register_a_user_already_registered()
    {
        $registeredUser = User::factory()->create();

        $response = $this->json(
            'POST',
            $this->uri,
            ['phone_number' => $registeredUser->phone_number]
        );

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrorFor('phone_number');
    }

    /** @test */
    public function it_should_not_register_a_banned_user()
    {
        $bannedUser = User::factory()->create(['is_banned' => true]);

        $response = $this->json(
            'POST',
            $this->uri,
            ['phone_number' => $bannedUser->phone_number]
        );

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors('phone_number');
    }

    /** @test */
    public function it_should_register_a_user_and_should_return_authorizaten_token()
    {
        $password = $this->faker->password();
        $hashedPassword = bcrypt($password);
        $newUserData = [
            'phone_number' => $this->faker->e164PhoneNumber(),
            'password' => $password,
            'password_confirmation' => $password,
        ];

        Hash::shouldReceive('make')->once()->with($password)->andReturn($hashedPassword);

        $response = $this->json('POST', $this->uri, $newUserData);

        $response->assertOk()
            ->assertJsonFragment(['phone_number' => $newUserData['phone_number']]);

        $this->assertDatabaseHas(
            'users',
            ['phone_number' => $newUserData['phone_number'], 'password' => $hashedPassword]
        );
    }
}
