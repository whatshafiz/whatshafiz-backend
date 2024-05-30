<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseType;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\UserCourse;
use Carbon\Carbon;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
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
            ->completed()
            ->make()
            ->only([
                'name',
                'surname',
                'email',
                'gender',
                'country_id',
                'city_id',
                'education_level',
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
        $course = Course::query()->update(['can_be_applied' => false]);
        $data = UserCourse::factory()->make()->only('course_id', 'is_teacher');

        $response = $this->actingAs($user)->json('POST', $this->uri . '/courses', $data);

        $response->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJsonFragment(['message' => 'Şuan bu kurs için başvuru kabul edilmiyor.']);
    }

    /** @test */
    public function it_should_not_save_user_course_application_when_user_already_applied_before_to_same_course()
    {
        $user = User::factory()->create();
        Course::query()->update(['can_be_applied' => false]);
        $availableCourse = Course::factory()->available()->create(['is_active' => true]);
        UserCourse::factory()
            ->create([
                'user_id' => $user->id,
                'course_id' => $availableCourse->id,
                'course_type_id' => $availableCourse->course_type_id,
            ]);

        $response = $this->actingAs($user)
            ->json(
                'POST',
                $this->uri . '/courses',
                ['course_id' => $availableCourse->id, 'is_teacher' => $this->faker->boolean]
            );

        $response->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJsonFragment(['message' => 'Daha önceden başvuru yapmışsınız.']);
    }

    /** @test */
    public function it_should_not_save_user_course_application_when_user_already_applied_before_to_same_type_course()
    {
        $user = User::factory()->create();
        Course::query()->update(['can_be_applied' => false]);
        $availableCourse = Course::factory()->available()->create(['is_active' => true]);
        $userExistingCourse = Course::factory()
            ->unavailable()
            ->create(['course_type_id' => $availableCourse->course_type_id, 'is_active' => true]);
        UserCourse::factory()
            ->create([
                'user_id' => $user->id,
                'course_id' => $userExistingCourse->id,
                'course_type_id' => $availableCourse->course_type_id,
            ]);

        $response = $this->actingAs($user)
            ->json(
                'POST',
                $this->uri . '/courses',
                ['course_id' => $availableCourse->id, 'is_teacher' => $this->faker->boolean]
            );

        $response->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJsonFragment(['message' => 'Başvuru yaptığınız kurs tipinde zaten kaydınız var.']);
    }

    /** @test */
    public function it_should_save_user_course_application_when_course_type_is_whatshafiz()
    {
        $now = Carbon::now();
        Carbon::setTestNow($now);
        $user = User::factory()->create();
        Course::query()->update(['can_be_applied' => false]);
        $availableCourse = Course::factory()
            ->available()
            ->create(['course_type_id' => CourseType::where('slug', 'whatshafiz')->value('id'), 'is_active' => true]);
        $isTeacher = $this->faker->boolean;

        $response = $this->actingAs($user)
            ->json(
                'POST',
                $this->uri . '/courses',
                ['course_id' => $availableCourse->id, 'is_teacher' => $isTeacher]
            );

        $response->assertOk()
            ->assertJsonFragment([
                'whatsapp_channel_join_url' => $availableCourse->whatsapp_channel_join_url,
            ]);

        $this->assertDatabaseHas(
            'user_course',
            [
                'user_id' => $user->id,
                'course_id' => $availableCourse->id,
                'course_type_id' => $availableCourse->course_type_id,
                'is_teacher' => $isTeacher,
                'applied_at' => $now->format('Y-m-d H:i:s'),
                'removed_at' => null,
            ]
        );
        $this->assertTrue($user->hasRole($isTeacher ? 'HafızKal' : 'HafızOl'));
    }

    /** @test */
    public function it_should_save_user_course_application_and_send_whatsapp_channel_join_url_via_whatsapp_when_course_type_is_whatsenglish_or_whatsarapp()
    {
        $now = Carbon::now();
        Carbon::setTestNow($now);
        $this->app->detectEnvironment(function () { return 'production'; });
        $user = User::factory()->hasGender()->create();
        Course::query()->update(['can_be_applied' => false]);
        $availableCourse = Course::factory()
            ->available()
            ->create([
                'course_type_id' => CourseType::whereIn('slug', ['whatsenglish', 'whatsarapp'])
                    ->inRandomOrder()
                    ->value('id'),
                'whatsapp_channel_join_url' => $this->faker->url,
                'is_active' => true,
            ]);

        Queue::shouldReceive('connection')->once()->with('messenger-sqs')->andReturnSelf();
        Queue::shouldReceive('pushRaw')
            ->once()
            ->with(json_encode([
                'phone' => $user->phone_number,
                'text' => 'Aşağıdaki linki kullanarak *' . $availableCourse->name .
                    '* kursu için whatsapp duyuru kanalına katılın ve buradan duyuruları takip edin. ↘️ ' .
                    $availableCourse->whatsapp_channel_join_url
            ]));

        $response = $this->actingAs($user)
            ->json(
                'POST',
                $this->uri . '/courses',
                ['course_id' => $availableCourse->id]
            );

        $response->assertOk()
            ->assertJsonFragment([
                'message' => '<br> <strong>Kaydınız başarılı şekilde oluşturuldu.</strong> <br><br> ' .
                    '<br><br>' .
                    'Lütfen aşağıdaki <strong>Whatsapp Duyuru Kanalına Katıl</strong> butonunu kullanarak whatsapp duyuru kanalına katılın. <br><br>' .
                    'Kurs ile ilgili tüm duyurular bu whatsapp kanalı üzerinden yapılacaktır. Lütfen duyuruları takip edin. <br><br><br>' .
                    '<i>Bu buton ile katılım sağlayamazsanız, kanala katılmak için gerekli link size whatsapp üzerinden de gönderilecek.</i> <br><br>' .
                    '<i>Lütfen gelen mesajı <strong>SPAM DEĞİL</strong> veya <strong>TAMAM</strong> olarak işaretleyin.</i> <br><br>' .
                    '<i>Eğer gelen linke tıklayamıyorsanız mesaj gelen numarayı Kişilere Ekleyin</i> <br>',
                'whatsapp_channel_join_url' => $availableCourse->whatsapp_channel_join_url,
            ]);

        $this->assertDatabaseHas(
            'user_course',
            [
                'user_id' => $user->id,
                'course_id' => $availableCourse->id,
                'course_type_id' => $availableCourse->course_type_id,
                'applied_at' => $now->format('Y-m-d H:i:s'),
                'removed_at' => null,
            ]
        );
        $this->assertTrue($user->hasRole(Str::ucfirst($availableCourse->courseType->slug)));
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
