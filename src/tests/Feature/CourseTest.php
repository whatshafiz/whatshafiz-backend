<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
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

        $response = $this->actingAs($user)->json('GET', $this->uri . '/' . $course->id);

        $response->assertOk()
            ->assertJsonFragment($course->toArray());
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
        $courses = Course::factory()->count(2, 5)->create();
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
        $courses = Course::factory()->count(2, 5)->create();
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
        $availableCourses = Course::factory()->available()->count(2, 5)->create();

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
        $availableCourses = Course::factory()->available()->count(2, 5)->create();

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
            'can_be_applied_until' =>  $this->faker->datetime->format('Y-m-d\TH:i'),
            'start_at' =>  $this->faker->datetime->format('Y-m-d\TH:i'),
        ]);

        $response = $this->actingAs($user)->json('POST', $this->uri, $courseData);

        $courseData['can_be_applied_until'] = Carbon::parse($courseData['can_be_applied_until'])->format('d-m-Y H:i');
        $courseData['start_at'] = Carbon::parse($courseData['start_at'])->format('d-m-Y H:i');

        $response->assertCreated()
            ->assertJsonFragment($courseData);

        $courseData['can_be_applied_until'] = Carbon::parse($courseData['can_be_applied_until'])->format('Y-m-d H:i:s');
        $courseData['start_at'] = Carbon::parse($courseData['start_at'])->format('Y-m-d H:i:s');

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
            'can_be_applied_until' =>  $this->faker->datetime->format('Y-m-d\TH:i'),
            'start_at' =>  $this->faker->datetime->format('Y-m-d\TH:i'),
        ]);

        $response = $this->actingAs($user)->json('PUT', $this->uri . '/' . $course->id, $courseData);

        $response->assertSuccessful();

        $courseData['can_be_applied_until'] = Carbon::parse($courseData['can_be_applied_until'])->format('Y-m-d H:i:s');
        $courseData['start_at'] = Carbon::parse($courseData['start_at'])->format('Y-m-d H:i:s');

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
}
