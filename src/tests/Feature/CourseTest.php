<?php

namespace Tests\Feature;

use App\Jobs\CourseTeacherStudentsMatcher;
use App\Jobs\CourseWhatsappGroupsOrganizer;
use App\Models\Course;
use App\Models\TeacherStudent;
use App\Models\User;
use App\Models\UserCourse;
use App\Models\WhatsappGroup;
use App\Models\WhatsappGroupUser;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Symfony\Component\HttpFoundation\Response;
use Tests\BaseFeatureTest;

class CourseTest extends BaseFeatureTest
{
    protected string $uri;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->uri = self::BASE_URI . '/courses';
    }

    /** @test */
    public function it_should_not_get_course_when_does_not_have_permission()
    {
        $course = Course::factory()->create();
        $user = User::factory()->create();

        $response = $this->actingAs($user)->json('GET', $this->uri . '/' . $course->id);

        $response->assertForbidden();
    }

    /** @test */
    public function it_should_get_course_details_when_has_permission()
    {
        $course = Course::factory()->create();
        $user = User::factory()->create();
        $user->givePermissionTo('courses.view');

        $whatsappGroupCount = rand(1, 3);
        $whatsappGroupUsersCount = 0;
        WhatsappGroup::factory()
            ->count($whatsappGroupCount)
            ->create(['course_id' => $course->id])
            ->each(function($whatsappGroup) use (&$whatsappGroupUsersCount) {
                $count = rand(1, 5);
                WhatsappGroupUser::factory()->count($count)->create(['whatsapp_group_id' => $whatsappGroup->id]);
                $whatsappGroupUsersCount += $count;
            });
        $usersCount = rand(11, 23);
        $courseUsers = [];
        User::factory()
            ->count($usersCount)
            ->create()
            ->each(function ($user) use ($course, &$courseUsers) {
                $courseUsers[] = UserCourse::factory()
                    ->create(['type' => 'whatshafiz', 'course_id' => $course->id, 'user_id' => $user->id])
                    ->toArray();
            });

        $courseUsers = collect($courseUsers);
        $hafizkalUsersCount = $courseUsers->where('is_teacher', true)->count();
        $hafizolUsersCount = $courseUsers->where('is_teacher', false)->count();

        $matchedHafizkalUsersIds = $courseUsers->shuffle()
            ->where('is_teacher', true)
            ->take(rand(2, $hafizkalUsersCount))
            ->pluck('user_id');
        $matchedHafizolUsersIds = $courseUsers->shuffle()
            ->where('is_teacher', false)
            ->take(rand(count($matchedHafizkalUsersIds), $hafizkalUsersCount))
            ->pluck('user_id');
        $matchedHafizkalUsersCount = count($matchedHafizkalUsersIds);
        $matchedHafizolUsersCount = count($matchedHafizolUsersIds);
        $matchedUsersCount = $matchedHafizkalUsersCount + $matchedHafizolUsersCount;

        foreach ($matchedHafizkalUsersIds as $matchedHafizkalUserId) {
            foreach ($matchedHafizolUsersIds as $matchedHafizolUserId) {
                TeacherStudent::factory()
                    ->create([
                        'course_id' => $course->id,
                        'teacher_id' => $matchedHafizkalUserId,
                        'student_id' => $matchedHafizolUserId,
                    ]);
            }
        }

        $response = $this->actingAs($user)->json('GET', $this->uri . '/' . $course->id);

        $response->assertOk();

        $usersCount = $course->users()->count();

        $response->assertJsonFragment(
            array_merge(
                $course->toArray(),
                [
                    'total_users_count' => $usersCount,
                    'whatsapp_groups_count' => $whatsappGroupCount,
                    'whatsapp_groups_users_count' => $whatsappGroupUsersCount,
                    'hafizkal_users_count' => $course->users()->where('is_teacher', true)->count(),
                    'hafizol_users_count' => $course->users()->where('is_teacher', false)->count(),
                    'matched_hafizkal_users_count' => $matchedHafizkalUsersCount,
                    'matched_hafizol_users_count' => $matchedHafizolUsersCount,
                    'matched_users_count' => $matchedUsersCount,
                    'unmatched_users_count' => $usersCount - $matchedUsersCount,
                ]
            )
        );
    }

    /** @test */
    public function it_should_not_get_courses_list_when_does_not_have_permission()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->json('GET', $this->uri);

        $response->assertForbidden();
    }

    /** @test */
    public function it_should_get_courses_list_when_has_permission()
    {
        $courses = Course::factory()->count(rand(2, 5))->create();
        $user = User::factory()->create();
        $user->givePermissionTo('courses.list');

        $response = $this->actingAs($user)->json('GET', $this->uri);

        $response->assertOk();

        foreach ($courses as $course) {
            $response->assertJsonFragment($course->toArray());
        }
    }

    /** @test */
    public function it_should_get_own_courses_list_when_has_permission()
    {
        $courses = Course::factory()->count(rand(2, 5))->create();
        $user = User::factory()->create();
        $user->courses()->attach($courses);

        $response = $this->actingAs($user)->json('GET', self::BASE_URI . '/my/courses');

        $response->assertOk();

        foreach ($courses as $course) {
            $response->assertJsonFragment($course->toArray());
        }
    }

    /** @test */
    public function it_should_get_courses_list_when_has_permission_by_filtering_and_as_paginated()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('courses.list');

        $courses = Course::factory()->count(5)->create();
        $searchCourse = $courses->random();
        $user->courses()->attach($searchCourse);
        $searchQuery = [
            'user_id' => $user->id,
            'filter' => [['value' => $searchCourse->name]],
        ];

        $response = $this->actingAs($user)->json('GET', $this->uri . '/paginate', $searchQuery);

        $response->assertOk()
            ->assertJsonFragment($searchCourse->toArray());
    }

    /** @test */
    public function it_should_get_available_courses_list_from_cache_when_available_courses_cached_before()
    {
        Course::query()->update(['can_be_applied' => false]);
        $availableCourses = Course::factory()->available()->count(rand(2, 5))->create();

        Cache::shouldReceive('has')->with(Course::AVAILABLE_COURSES_CACHE_KEY)->once()->andReturn(true);
        Cache::shouldReceive('get')->with(Course::AVAILABLE_COURSES_CACHE_KEY)->once()->andReturn($availableCourses);

        $response = $this->json('GET', $this->uri . '/available');

        $response->assertOk();

        foreach ($availableCourses as $availableCourse) {
            $response->assertJsonFragment(
                Arr::only(
                    $availableCourse->toArray(),
                    ['id', 'type', 'name', 'can_be_applied', 'can_be_applied_until', 'start_at']
                )
            );
        }
    }

    /** @test */
    public function it_should_get_available_courses_list_from_database_and_put_it_to_cache_when_available_courses_did_not_cached_before()
    {
        Course::query()->update(['can_be_applied' => false]);
        $availableCourses = Course::factory()->available()->count(rand(2, 5))->create();

        Cache::shouldReceive('has')->with(Course::AVAILABLE_COURSES_CACHE_KEY)->once()->andReturn(false);
        Cache::shouldReceive('get')->with(Course::AVAILABLE_COURSES_CACHE_KEY)->never();
        Cache::shouldReceive('put')->once();

        $response = $this->json('GET', $this->uri . '/available');

        $response->assertOk();

        foreach ($availableCourses as $availableCourse) {
            $response->assertJsonFragment(
                Arr::only(
                    $availableCourse->toArray(),
                    ['id', 'type', 'name', 'can_be_applied', 'can_be_applied_until', 'start_at']
                )
            );
        }
    }

    /** @test */
    public function it_should_not_create_course_when_does_not_have_permission()
    {
        $user = User::factory()->create();
        $courseData = Course::factory()->raw();

        $response = $this->actingAs($user)->json('POST', $this->uri, $courseData);

        $response->assertForbidden();
    }

    /** @test */
    public function it_should_create_course_when_has_permission()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('courses.create');
        Course::query()->update(['can_be_applied' => false]);

        $courseData = Course::factory()->raw([
            'start_at' =>  $this->faker->datetime->format('Y-m-d\TH:i'),
            'can_be_applied_until' =>  $this->faker->datetime->format('Y-m-d\TH:i'),
            'proficiency_exam_start_time' =>  $this->faker->datetime->format('Y-m-d\TH:i'),
        ]);

        $response = $this->actingAs($user)->json('POST', $this->uri, $courseData);

        $courseData['start_at'] = Carbon::parse($courseData['start_at'])->format('d-m-Y H:i');
        $courseData['can_be_applied_until'] = Carbon::parse($courseData['can_be_applied_until'])->format('d-m-Y H:i');
        $courseData['proficiency_exam_start_time'] = Carbon::parse($courseData['proficiency_exam_start_time'])
            ->format('d-m-Y H:i');

        $response->assertCreated()
            ->assertJsonFragment($courseData);

        $courseData['start_at'] = Carbon::parse($courseData['start_at'])->format('Y-m-d H:i:s');
        $courseData['can_be_applied_until'] = Carbon::parse($courseData['can_be_applied_until'])->format('Y-m-d H:i:s');
        $courseData['proficiency_exam_start_time'] = Carbon::parse($courseData['proficiency_exam_start_time'])
            ->format('Y-m-d H:i:s');

        $this->assertDatabaseHas('courses', $courseData);
    }

    /** @test */
    public function it_should_not_create_course_as_can_be_applied_when_there_is_already_can_be_applied_course_for_type()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('courses.create');

        $courseData = Course::factory()->raw(['can_be_applied' => true]);
        Course::factory()->create(['can_be_applied' => true, 'type' => $courseData['type']]);

        $response = $this->actingAs($user)->json('POST', $this->uri, $courseData);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors('can_be_applied');
    }

    /** @test */
    public function it_should_not_update_course_when_does_not_have_permission()
    {
        $course = Course::factory()->create();
        $user = User::factory()->create();
        $courseData = Course::factory()->raw();

        $response = $this->actingAs($user)->json('PUT', $this->uri . '/' . $course->id, $courseData);

        $response->assertForbidden();
    }

    /** @test */
    public function it_should_update_course_when_has_permission()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('courses.update');
        Course::query()->update(['can_be_applied' => false]);
        $course = Course::factory()->create();

        $courseData = Course::factory()->raw([
            'start_at' =>  $this->faker->datetime->format('Y-m-d\TH:i'),
            'can_be_applied_until' =>  $this->faker->datetime->format('Y-m-d\TH:i'),
            'proficiency_exam_start_time' =>  $this->faker->datetime->format('Y-m-d\TH:i'),
        ]);

        $response = $this->actingAs($user)->json('PUT', $this->uri . '/' . $course->id, $courseData);

        $response->assertSuccessful();

        $courseData['start_at'] = Carbon::parse($courseData['start_at'])->format('Y-m-d H:i:s');
        $courseData['can_be_applied_until'] = Carbon::parse($courseData['can_be_applied_until'])->format('Y-m-d H:i:s');
        $courseData['proficiency_exam_start_time'] = Carbon::parse($courseData['proficiency_exam_start_time'])
            ->format('Y-m-d H:i:s');

        $this->assertDatabaseHas('courses', array_merge(['id' => $course->id], $courseData));
    }

    /** @test */
    public function it_should_not_update_course_as_can_be_applied_when_there_is_already_can_be_applied_course_for_type()
    {
        $course = Course::factory()->create(['can_be_applied' => false]);
        Course::factory()->create(['can_be_applied' => true, 'type' => $course->type]);
        $user = User::factory()->create();
        $user->givePermissionTo('courses.update');

        $courseData = Course::factory()->raw(['can_be_applied' => true, 'type' => $course->type]);

        $response = $this->actingAs($user)->json('PUT', $this->uri . '/' . $course->id, $courseData);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors('can_be_applied');
    }

    /** @test */
    public function it_should_not_delete_course_when_does_not_have_permission()
    {
        $course = Course::factory()->create();
        $user = User::factory()->create();

        $response = $this->actingAs($user)->json('DELETE', $this->uri . '/' . $course->id);

        $response->assertForbidden();
    }

    /** @test */
    public function it_should_delete_course_when_has_permission()
    {
        $course = Course::factory()->create();
        $user = User::factory()->create();
        $user->givePermissionTo('courses.delete');

        $response = $this->actingAs($user)->json('DELETE', $this->uri . '/' . $course->id);

        $response->assertSuccessful();

        $this->assertSoftDeleted('courses', ['id' => $course->id]);
    }

    /** @test */
    public function it_should_not_start_course_teacher_students_matchings_when_does_not_have_permission()
    {
        $course = Course::factory()->create();
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->json('POST', $this->uri . '/' . $course->id . '/teacher-students-matchings');

        $response->assertForbidden();
    }

    /** @test */
    public function it_should_start_course_teacher_students_matchings_when_has_permission()
    {
        $course = Course::factory()->create();
        $user = User::factory()->create();
        $user->givePermissionTo('courses.update');

        Queue::fake();

        $response = $this->actingAs($user)
            ->json('POST', $this->uri . '/' . $course->id . '/teacher-students-matchings');

        $response->assertSuccessful();

        Queue::assertPushed(CourseTeacherStudentsMatcher::class);
    }

    /** @test */
    public function it_should_get_course_teacher_students_matching_list_when_has_permission_by_filtering_and_as_paginated()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('courses.view');
        $course = Course::factory()->whatshafiz()->create();

        $teacherStudents = TeacherStudent::factory()->count(9)->create(['course_id' => $course->id]);
        $teacherIds = $teacherStudents->pluck('teacher_id')->unique();

        $response = $this->actingAs($user)->json('GET', $this->uri . '/' . $course->id . '/teacher-students-matchings');

        $response->assertOk();

        foreach ($teacherIds as $teacherId) {
            $response->assertJsonFragment([
                'teacher_id' => $teacherId,
                'students_count' => $teacherStudents->where('teacher_id', $teacherId)->count(),
                'passed_students_count' => (string)$teacherStudents->where('teacher_id', $teacherId)
                    ->whereStrict('proficiency_exam_passed', true)
                    ->count(),
                'failed_students_count' => (string)$teacherStudents->where('teacher_id', $teacherId)
                    ->whereStrict('proficiency_exam_passed', false)
                    ->count(),
                'awaiting_students_count' => (string)$teacherStudents->where('teacher_id', $teacherId)
                    ->whereStrict('proficiency_exam_passed', null)
                    ->count(),
            ]);
        }
    }

    /** @test */
    public function it_should_start_organization_of_whatsapp_groups_when_has_permission()
    {
        $course = Course::factory()->create();
        $user = User::factory()->create();
        $user->givePermissionTo('courses.update');

        Queue::fake();

        $response = $this->actingAs($user)->json('POST', $this->uri . '/' . $course->id . '/whatsapp-groups');

        $response->assertSuccessful();

        Queue::assertPushed(CourseWhatsappGroupsOrganizer::class);
    }
}
