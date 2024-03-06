<?php

namespace Tests\Feature;

use App\Models\PasswordReset;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
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

    /** @test */
    public function it_should_set_and_send_password_reset_code()
    {
        $now = Carbon::now();
        Carbon::setTestNow($now);
        $this->app->detectEnvironment(function () { return 'production'; });
        $user = User::factory()->create();
        Queue::shouldReceive('connection')->once()->with('messenger-sqs')->andReturnSelf();
        Queue::shouldReceive('pushRaw')->once();
        $dummyCodeHash = Str::random(30);
        Hash::shouldReceive('make')->once()->andReturn($dummyCodeHash);

        $response = $this->json('POST', self::BASE_URI . '/forgot-password', ['phone_number' => $user->phone_number]);

        $response->assertOk();

        $this->assertDatabaseHas(
            'password_resets',
            [
                'phone_number' => $user->phone_number,
                'token' => $dummyCodeHash,
                'created_at' => $now->format('Y-m-d H:i:s'),
            ]
        );
    }

    /** @test */
    public function it_should_not_set_new_password_reset_code_when_phone_number_is_not_registered()
    {
        Queue::shouldReceive('connection')->never();
        Queue::shouldReceive('pushRaw')->never();
        Hash::shouldReceive('make')->never();

        do {
            $unregisteredPhoneNumber = $this->faker->unique()->e164PhoneNumber();
        } while (User::where('phone_number', $unregisteredPhoneNumber)->exists());

        $response = $this->json(
            'POST',
            self::BASE_URI . '/forgot-password',
            ['phone_number' => $unregisteredPhoneNumber]
        );

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /** @test */
    public function it_should_not_set_new_password_reset_code_when_user_has_a_valid_reset_code()
    {
        $now = Carbon::now();
        Carbon::setTestNow($now);
        $user = User::factory()->create();
        $tokenLifetime = PasswordReset::TOKEN_LIFETIME_IN_MINUTE;
        $user->passwordResetCode()
            ->create([
                'token' => Str::random(30),
                'created_at' => $now->copy()->subMinutes(rand(0, $tokenLifetime)),
            ]);

        Queue::shouldReceive('connection')->never();
        Queue::shouldReceive('pushRaw')->never();
        Hash::shouldReceive('make')->never();

        $response = $this->json('POST', self::BASE_URI . '/forgot-password', ['phone_number' => $user->phone_number]);

        $response->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJsonFragment(['message' => $tokenLifetime . ' dakika içinde bir kere kod isteyebilirsiniz']);
    }

    /** @test */
    public function it_should_update_password_by_using_password_reset_code()
    {
        $now = Carbon::now();
        Carbon::setTestNow($now);
        $user = User::factory()->create();
        $newPassword = '123456789';
        $tokenLifetime = PasswordReset::TOKEN_LIFETIME_IN_MINUTE;
        $code = 123456;
        $token = bcrypt($code);
        $passwordReset = $user->passwordResetCode()->create(['token' => $token, 'created_at' => $now]);

        $response = $this->json(
            'POST',
            self::BASE_URI . '/update-password',
            [
                'phone_number' => $user->phone_number,
                'verification_code' => $code,
                'password' => $newPassword,
                'password_confirmation' => $newPassword,
            ]
        );

        $response->assertOk();

        $this->assertTrue(Hash::check($newPassword, $user->refresh()->password));
    }

    /** @test */
    public function it_should_not_update_password_when_the_password_reset_code_is_not_valid()
    {
        $now = Carbon::now();
        Carbon::setTestNow($now);
        $user = User::factory()->create();
        $newPassword = '123456789';
        $tokenLifetime = PasswordReset::TOKEN_LIFETIME_IN_MINUTE;
        $code = 123456;
        $token = bcrypt($code);
        $passwordReset = $user->passwordResetCode()
            ->create(['token' => $token, 'created_at' => $now->copy()->subMinutes($tokenLifetime + rand(1, 50))]);

        Hash::shouldReceive('make')->never();
        Hash::makePartial();

        $response = $this->json(
            'POST',
            self::BASE_URI . '/update-password',
            [
                'phone_number' => $user->phone_number,
                'verification_code' => $code,
                'password' => $newPassword,
                'password_confirmation' => $newPassword,
            ]
        );

        $response->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJsonFragment(['message' => 'Kod hatalı veya süresi dolmuş, lütfen tekrar deneyin.']);
    }

    /** @test */
    public function it_should_not_update_password_when_the_password_reset_code_is_not_correct()
    {
        $now = Carbon::now();
        Carbon::setTestNow($now);
        $user = User::factory()->create();
        $newPassword = '123456789';
        $tokenLifetime = PasswordReset::TOKEN_LIFETIME_IN_MINUTE;
        $code = 123456;
        $token = bcrypt($code);
        $passwordReset = $user->passwordResetCode()
            ->create(['token' => $token, 'created_at' => $now->copy()->subMinutes($tokenLifetime + rand(1, 50))]);

        Hash::shouldReceive('make')->never();
        Hash::makePartial();

        $response = $this->json(
            'POST',
            self::BASE_URI . '/update-password',
            [
                'phone_number' => $user->phone_number,
                'verification_code' => $code . rand(2, 60),
                'password' => $newPassword,
                'password_confirmation' => $newPassword,
            ]
        );

        $response->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJsonFragment(['message' => 'Kod hatalı veya süresi dolmuş, lütfen tekrar deneyin.']);
    }
}
