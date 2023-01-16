<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use App\Models\UserCourse;
use App\Models\WhatsappGroup;
use App\Models\WhatsappGroupUser;
use Carbon\Carbon;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
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
    public function it_should_not_save_user_course_application_when_user_already_applied_before_to_same_course()
    {
        $user = User::factory()->create();
        Course::query()->update(['can_be_applied' => false]);
        $availableCourse = Course::factory()->available()->create(['is_active' => true]);
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
    public function it_should_not_save_user_course_application_when_user_already_applied_before_to_same_type_course()
    {
        $user = User::factory()->create();
        Course::query()->update(['can_be_applied' => false]);
        $availableCourse = Course::factory()->available()->create();
        $userExistingCourse = Course::factory()
            ->available()
            ->create(['type' => $availableCourse->type, 'is_active' => true]);
        UserCourse::factory()->create([
            'type' => $availableCourse->type,
            'user_id' => $user->id,
            'course_id' => $userExistingCourse->id
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
    public function it_should_save_user_course_application_when_course_type_is_whatshafiz()
    {
        $now = Carbon::now();
        Carbon::setTestNow($now);
        $user = User::factory()->create();
        Course::query()->update(['can_be_applied' => false]);
        $availableCourse = Course::factory()->available()->create(['type' => 'whatshafiz', 'is_active' => true]);
        $isTeacher = $this->faker->boolean;

        $response = $this->actingAs($user)
            ->json(
                'POST',
                $this->uri . '/courses',
                ['type' => $availableCourse->type, 'is_teacher' => $isTeacher]
            );

        $response->assertOk()
            ->assertJsonFragment(['message' => 'Kaydınız başarılı şekilde oluşturuldu.']);

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
        $this->assertTrue($user->hasRole($isTeacher ? 'HafızKal' : 'HafızOl'));
    }

    /** @test */
    public function it_should_save_user_course_application_and_send_whatsapp_group_join_url_via_whatsapp_when_course_type_is_whatsenglish_or_whatsarapp()
    {
        $now = Carbon::now();
        Carbon::setTestNow($now);
        $user = User::factory()->hasGender()->create();
        Course::query()->update(['can_be_applied' => false]);
        $availableCourse = Course::factory()
            ->available()
            ->create(['type' => $this->faker->randomElement(['whatsenglish', 'whatsarapp']), 'is_active' => true]);
        $isTeacher = $this->faker->boolean;
        WhatsappGroup::factory()
            ->count(rand(1, 5))
            ->create(['type' => $availableCourse->type, 'course_id' => $availableCourse->id, 'gender' => $user->gender])
            ->each(function ($whatsappGroup) {
                WhatsappGroupUser::factory()->count(3, 5)->create(['whatsapp_group_id' => $whatsappGroup->id]);
            });
        $joinUrl = $this->faker->url;
        $whatsappGroupsHasMinimumUser = WhatsappGroup::factory()
            ->count(rand(3, 5))
            ->create([
                'type' => $availableCourse->type,
                'course_id' => $availableCourse->id,
                'gender' => $user->gender,
                'join_url' => $joinUrl,
                'is_active' => true,
            ])
            ->each(function ($whatsappGroup) {
                WhatsappGroupUser::factory()->count(1, 2)->create(['whatsapp_group_id' => $whatsappGroup->id]);
            });
        Queue::shouldReceive('connection')->once()->with('messenger-sqs')->andReturnSelf();
        Queue::shouldReceive('pushRaw')
            ->once()
            ->with(json_encode([
                'phone' => $user->phone_number,
                'text' => 'Aşağıdaki linki kullanarak *' . $availableCourse->type .
                    '* kursu için atandığınız whatsapp grubuna katılın. ↘️ ' . $joinUrl
            ]));

        $response = $this->actingAs($user)
            ->json(
                'POST',
                $this->uri . '/courses',
                ['type' => $availableCourse->type, 'is_teacher' => $isTeacher]
            );

        $response->assertOk()
            ->assertJsonFragment([
                'message' => 'Kaydınız başarılı şekilde oluşturuldu. ' .
                    'Whatsapp grubuna katılmak için gerekli link size whatsapp üzerinden gönderilecek.'
            ]);

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
        $this->assertDatabaseHas(
            'whatsapp_group_users',
            [
                'user_id' => $user->id,
                'role_type' => null,
                'joined_at' => $now->format('Y-m-d H:i:s'),
            ]
        );
        $this->assertTrue(
            WhatsappGroupUser::whereIn('whatsapp_group_id', $whatsappGroupsHasMinimumUser->pluck('id')->toArray())
                ->where('user_id', $user->id)
                ->where('role_type', null)
                ->exists()
        );
        $this->assertTrue($user->hasRole(Str::ucfirst($availableCourse->type)));
    }

    /** @test */
    public function it_should_return_user_courses()
    {
        $user = User::factory()->create();

        $userCourses = UserCourse::factory()
            ->count(rand(1, 3))
            ->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->json('GET', $this->uri . '/courses');

        $response->assertOk();

        foreach ($userCourses as $userCourse) {
            $response->assertJsonFragment($userCourse->course->toArray());
        }
    }
}
