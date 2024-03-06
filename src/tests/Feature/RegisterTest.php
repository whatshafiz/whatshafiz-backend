<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
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
            [
                'phone_number' => $newUserData['phone_number'],
                'password' => $hashedPassword,
                'phone_number_verified_at' => null,
            ]
        );
    }

    /** @test */
    public function it_should_set_and_send_phone_number_verification_code()
    {
        $now = Carbon::now();
        Carbon::setTestNow($now);
        $this->app->detectEnvironment(function() { return 'production'; });
        $user = User::factory()->create(['phone_number_verified_at' => null]);
        Queue::shouldReceive('connection')->once()->with('messenger-sqs')->andReturnSelf();
        Queue::shouldReceive('pushRaw')->once();

        $response = $this->actingAs($user)
            ->json('POST', $this->uri . '/verification-code/send');

        $response->assertOk();

        $this->assertNotNull($user->refresh()->verification_code);
        $this->assertDatabaseHas(
            'users',
            ['id' => $user->id, 'verification_code_valid_until' => $now->addMinutes(3)->format('Y-m-d H:i:s')]
        );
    }

    /** @test */
    public function it_should_not_set_new_verification_code_when_user_has_already_verified_phone()
    {
        $user = User::factory()->create(['phone_number_verified_at' => now()]);
        Queue::shouldReceive('connection')->never();
        Queue::shouldReceive('pushRaw')->never();

        $response = $this->actingAs($user)
            ->json('POST', $this->uri . '/verification-code/send');

        $response->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJsonFragment(['message' => 'Telefon No daha önce doğrulanmış']);
    }

    /** @test */
    public function it_should_not_set_new_verification_code_when_the_code_is_valid()
    {
        $now = Carbon::now();
        Carbon::setTestNow($now);
        $user = User::factory()->create([
            'phone_number_verified_at' => null,
            'verification_code' => 111111,
            'verification_code_valid_until' => $now->copy()->addMinute(),
        ]);
        Queue::shouldReceive('connection')->never();
        Queue::shouldReceive('pushRaw')->never();

        $response = $this->actingAs($user)
            ->json('POST', $this->uri . '/verification-code/send');

        $response->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJsonFragment(['message' => '3 dakika içinde bir kere kod isteyebilirsiniz.']);
    }

    /** @test */
    public function it_should_verify_phone_number_verification_code()
    {
        $now = Carbon::now();
        Carbon::setTestNow($now);
        $code = random_int(100000, 999999);
        $user = User::factory()->create([
            'phone_number_verified_at' => null,
            'verification_code' => $code,
            'verification_code_valid_until' => $now->copy()->addMinute(),
        ]);

        $response = $this->actingAs($user)
            ->json('POST', $this->uri . '/verification-code/verify', ['code' => $code]);

        $response->assertOk()
            ->assertJsonFragment(['message' => 'Telefon numaranız başarılı şekilde doğrulandı.']);

        $this->assertDatabaseHas(
            'users',
            [
                'id' => $user->id,
                'phone_number_verified_at' => $now->format('Y-m-d H:i:s'),
                'verification_code' => null,
                'verification_code_valid_until' => null,
            ]
        );
    }

    /** @test */
    public function it_should_not_verify_phone_number_verification_code_when_the_code_is_not_valid()
    {
        $now = Carbon::now();
        Carbon::setTestNow($now);
        $code = random_int(100000, 999999);
        $user = User::factory()->create([
            'phone_number_verified_at' => null,
            'verification_code' => $code,
            'verification_code_valid_until' => $now->copy()->subMinutes(5),
        ]);

        $response = $this->actingAs($user)
            ->json('POST', $this->uri . '/verification-code/verify', ['code' => $code]);

        $response->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJsonFragment(['message' => 'Doğrulama kodu geçerli değil, lütfen tekrar deneyin.']);

        $this->assertDatabaseHas(
            'users',
            ['id' => $user->id, 'phone_number_verified_at' => null]
        );
    }

    /** @test */
    public function it_should_not_verify_phone_number_verification_code_when_the_code_is_not_correct()
    {
        $now = Carbon::now();
        Carbon::setTestNow($now);
        $code = 222222;
        $user = User::factory()->create([
            'phone_number_verified_at' => null,
            'verification_code' => $code,
            'verification_code_valid_until' => $now->copy()->addMinute(),
        ]);

        $response = $this->actingAs($user)
            ->json('POST', $this->uri . '/verification-code/verify', ['code' => 111111]);

        $response->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJsonFragment(['message' => 'Doğrulama kodu geçerli değil, lütfen tekrar deneyin.']);

        $this->assertDatabaseHas(
            'users',
            ['id' => $user->id, 'phone_number_verified_at' => null]
        );
    }

    /** @test */
    public function it_should_not_verify_phone_number_verification_code_when_the_user_already_verified_phone_number_before()
    {
        $now = Carbon::now();
        Carbon::setTestNow($now);
        $code = random_int(100000, 999999);
        $user = User::factory()->create([
            'phone_number_verified_at' => now(),
            'verification_code' => $code,
            'verification_code_valid_until' => $now->copy()->addMinute(),
        ]);

        $response = $this->actingAs($user)
            ->json('POST', $this->uri . '/verification-code/verify', ['code' => $code]);

        $response->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJsonFragment(['message' => 'Telefon numaranız daha önceden doğrulanmış.']);
    }
}
