<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use App\Models\UserCourse;
use Carbon\Carbon;
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

    /** @test */
    public function it_should_not_save_user_course_application_when_course_can_not_be_applied()
    {
        $user = User::factory()->create();
        Course::query()->update(['can_be_applied' => false]);
        $data = UserCourse::factory()->make()->only('type', 'is_teacher');

        $response = $this->actingAs($user)->json('POST', $this->uri . '/courses', $data);

        $response->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJsonFragment(['message' => "Şuan {$data['type']} için başvuruya açık dönem bulunmuyor."]);
    }

    /** @test */
    public function it_should_not_save_user_course_application_when_user_already_applied_before()
    {
        $user = User::factory()->create();
        Course::query()->update(['can_be_applied' => false]);
        $availableCourse = Course::factory()->available()->create();
        UserCourse::factory()->create([
            'type' => $availableCourse->type,
            'user_id' => $user->id,
            'course_id' => $availableCourse->id
        ]);

        $response = $this->actingAs($user)
            ->json(
                'POST',
                $this->uri . '/courses',
                ['type' => $availableCourse->type, 'is_teacher' => $this->faker->boolean]
            );

        $response->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJsonFragment(['message' => 'Daha önceden başvuru yapmışsınız.']);
    }

    /** @test */
    public function it_should_save_user_course_application()
    {
        $now = Carbon::now();
        Carbon::setTestNow($now);
        $user = User::factory()->create();
        Course::query()->update(['can_be_applied' => false]);
        $availableCourse = Course::factory()->available()->create();
        $isTeacher = $this->faker->boolean;

        $response = $this->actingAs($user)
            ->json(
                'POST',
                $this->uri . '/courses',
                ['type' => $availableCourse->type, 'is_teacher' => $isTeacher]
            );

        $response->assertOk()
            ->assertJsonFragment(['message' => 'Kaydınız başaralı şekilde oluşturuldu.']);

        $this->assertDatabaseHas(
            'user_course',
            [
                'user_id' => $user->id,
                'course_id' => $availableCourse->id,
                'type' => $availableCourse->type,
                'is_teacher' => $isTeacher,
                'applied_at' => $now->format('Y-m-d H:i:s'),
                'removed_at' => null,
            ]
        );
    }
}
