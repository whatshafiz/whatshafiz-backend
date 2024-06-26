<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Role;
use App\Models\UniversityDepartment;
use App\Models\User;
use App\Models\UserCourse;
use App\Models\WhatsappGroup;
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
        $loginUser->givePermissionTo('users.view');
        $loginUser->givePermissionTo('users.list');

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

        $universityDepartment = UniversityDepartment::inRandomOrder()->first();
        $userForFilter = User::factory()->completed()->create();
        $whatsappGroup = WhatsappGroup::factory()->create();
        $course = $whatsappGroup->course;
        $userForFilter->whatsappGroups()
            ->attach(
                $whatsappGroup,
                ['course_type_id' => $whatsappGroup->course_type_id, 'course_id' => $whatsappGroup->course_id]
            );
        $userForFilter->courses()->attach($course, ['course_type_id' => $course->course_type_id]);

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
            'is_banned' => $userForFilter->is_banned ?? false,
            'whatsapp_group_id' => $whatsappGroup->id,
            'course_id' => $course->id,
            'filter' => [['value' => $userForFilter->email]],
        ];

        $response = $this->actingAs($loginUser)->json('GET', $this->uri, $filters);

        $response->assertOk()
            ->assertJsonFragment($userForFilter->toArray());
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

    /** @test */
    public function it_should_attach_a_role_when_user_has_admin_role()
    {
        $loginUser = User::factory()->create();
        $loginUser->assignRole('Admin');

        $registeredUser = User::factory()->create();
        $newRole = Role::inRandomOrder()->first();

        $response = $this->actingAs($loginUser)
            ->json(
                'POST',
                $this->uri . '/' . $registeredUser->id . '/roles',
                ['role_id' => $newRole->id]
            );

        $this->assertDatabaseHas(
            'model_has_roles',
            ['role_id' => $newRole->id, 'model_id' => $registeredUser->id, 'model_type' => User::class]
        );
    }

    /** @test */
    public function it_should_detach_a_role_when_user_has_admin_role()
    {
        $loginUser = User::factory()->create();
        $loginUser->assignRole('Admin');

        $registeredUser = User::factory()->create();
        $existingRole = Role::inRandomOrder()->first();
        $registeredUser->assignRole($existingRole->name);

        $this->assertDatabaseHas(
            'model_has_roles',
            ['role_id' => $existingRole->id, 'model_id' => $registeredUser->id, 'model_type' => User::class]
        );

        $response = $this->actingAs($loginUser)
            ->json(
                'DELETE',
                $this->uri . '/' . $registeredUser->id . '/roles/' . $existingRole->id
            );

        $this->assertDatabaseMissing(
            'model_has_roles',
            ['role_id' => $existingRole->id, 'model_id' => $registeredUser->id, 'model_type' => User::class]
        );
    }

    /** @test */
    public function it_should_attach_a_course_when_user_has_permission()
    {
        $loginUser = User::factory()->create();
        $loginUser->givePermissionTo('users.update');

        $registeredUser = User::factory()->create();
        $newCourse = Course::inRandomOrder()->first();

        $response = $this->actingAs($loginUser)
            ->json(
                'POST',
                $this->uri . '/' . $registeredUser->id . '/courses',
                ['course_id' => $newCourse->id]
            );

        $this->assertDatabaseHas(
            'user_course',
            [
                'course_id' => $newCourse->id,
                'course_type_id' => $newCourse->course_type_id,
                'user_id' => $registeredUser->id,
            ]
        );
    }

    /** @test */
    public function it_should_detach_a_course_when_user_has_permission()
    {
        $loginUser = User::factory()->create();
        $loginUser->givePermissionTo('users.update');

        $userCourse = UserCourse::factory()->create();

        $response = $this->actingAs($loginUser)
            ->json('DELETE', $this->uri . '/' . $userCourse->user_id . '/courses/' . $userCourse->course_id);

        $this->assertDatabaseMissing(
            'user_course',
            $userCourse->only('course_id', 'course_type_id', 'user_id')
        );
    }

    /** @test */
    public function it_should_attach_a_whatsapp_group_when_user_has_permission()
    {
        $loginUser = User::factory()->create();
        $loginUser->givePermissionTo('users.update');

        $registeredUser = User::factory()->create();
        $whatsappGroup = WhatsappGroup::factory()->create();

        $response = $this->actingAs($loginUser)
            ->json(
                'POST',
                $this->uri . '/' . $registeredUser->id . '/whatsapp-groups',
                ['whatsapp_group_id' => $whatsappGroup->id]
            );

        $this->assertDatabaseHas(
            'user_course',
            [
                'course_id' => $whatsappGroup->course_id,
                'course_type_id' => $whatsappGroup->course_type_id,
                'user_id' => $registeredUser->id,
                'whatsapp_group_id' => $whatsappGroup->id,
            ]
        );
    }

    /** @test */
    public function it_should_detach_a_whatsapp_group_when_user_has_permission()
    {
        $loginUser = User::factory()->create();
        $loginUser->givePermissionTo('users.update');

        $whatsappGroup = WhatsappGroup::factory()->create();
        $userCourse = UserCourse::factory()->create(['whatsapp_group_id' => $whatsappGroup->id]);

        $response = $this->actingAs($loginUser)
            ->json(
                'DELETE',
                $this->uri . '/' . $userCourse->user_id . '/whatsapp-groups/' . $userCourse->whatsapp_group_id
            );

        $this->assertDatabaseMissing(
            'user_course',
            $userCourse->only('course_id', 'course_type_id', 'user_id', 'whatsapp_group_id')
        );
    }
}
