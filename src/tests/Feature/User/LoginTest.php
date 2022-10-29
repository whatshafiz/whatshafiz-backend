<?php

namespace Tests\Feature\User;

use App\Models\User;
use Symfony\Component\HttpFoundation\Response;
use Tests\BaseFeatureTest;

class LoginTest extends BaseFeatureTest
{
    protected string $uri;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->uri = self::BASE_URI . '/login';
    }

    /** @test */
    public function it_should_not_login_an_unregistered_user()
    {
        $unregisteredUser = User::factory()->make();

        $response = $this->json(
            'POST',
            $this->uri,
            ['phone_number' => $unregisteredUser->phone_number]
        );

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrorFor('phone_number');
    }

    /** @test */
    public function it_should_not_login_a_banned_user()
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
    public function it_should_not_login_when_tries_wrong_password()
    {
        $registeredUser = User::factory()->create();

        $response = $this->json(
            'POST',
            $this->uri,
            [
                'phone_number' => $registeredUser->phone_number,
                'password' => 'wrongpassword',
            ]
        );

        $response->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJsonFragment(['message' => 'Telefon No veya Parola Hatalı']);
    }

    /** @test */
    public function it_should_login_a_user_and_should_return_authorizaten_token()
    {
        $registeredUser = User::factory()->create();

        $response = $this->json(
            'POST',
            $this->uri,
            [
                'phone_number' => $registeredUser->phone_number,
                'password' => 'password',
            ]
        );

        $response->assertOk()
            ->assertSeeText('token')
            ->assertJsonFragment(['phone_number' => $registeredUser->phone_number]);
    }
}
