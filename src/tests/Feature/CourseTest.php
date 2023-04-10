<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
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
    public function it_should_get_courses_list_when_has_permission_by_filtering_type()
    {
        $allTypes = collect(['whatshafiz', 'whatsenglish', 'whatsarapp']);
        $filterType = $allTypes->random();
        $filteredCourses = Course::factory()->count(2, 5)->create(['type' => $filterType]);
        $user = User::factory()->create();
        $user->givePermissionTo('courses.list');

        $response = $this->actingAs($user)->json('GET', $this->uri, ['type' => $filterType]);

        $response->assertOk();

        foreach ($filteredCourses as $filteredCourse) {
            $response->assertJsonFragment($filteredCourse->toArray());
        }
    }

    /** @test */
    public function it_should_get_courses_list_when_has_permission_by_searching_name()
    {
        $searchKey = Str::random(rand(7, 15));

        $filteredCourses = collect();
        $filteredCourses->push(Course::factory()->create(['name' => $searchKey]));
        $filteredCourses->push(Course::factory()->create(['name' => Str::random(rand(1, 5)) . $searchKey]));
        $filteredCourses->push(Course::factory()->create(['name' => $searchKey . Str::random(rand(1, 5))]));
        $filteredCourses->push(
            Course::factory()->create(['name' => Str::random(rand(1, 5)) . $searchKey . Str::random(rand(1, 5))])
        );
        $user = User::factory()->create();
        $user->givePermissionTo('courses.list');

        $response = $this->actingAs($user)->json('GET', $this->uri, ['name' => $searchKey]);

        $response->assertOk();

        foreach ($filteredCourses as $filteredCourse) {
            $response->assertJsonFragment($filteredCourse->toArray());
        }
    }

    /** @test */
    public function it_should_get_courses_list_when_has_permission_by_filtering_activity_status()
    {
        $isActive = $this->faker->boolean;
        $filteredCourses = Course::factory()->count(2, 5)->create(['is_active' => $isActive]);
        $user = User::factory()->create();
        $user->givePermissionTo('courses.list');

        $response = $this->actingAs($user)->json('GET', $this->uri, ['is_active' => $isActive]);

        $response->assertOk();

        foreach ($filteredCourses as $filteredCourse) {
            $response->assertJsonFragment($filteredCourse->toArray());
        }
    }

    /** @test */
    public function it_should_get_courses_list_when_has_permission_by_filtering_application_status()
    {
        $canBeApplied = $this->faker->boolean;
        $filteredCourses = $canBeApplied ?
            Course::factory()->count(2, 5)->available()->create() :
            Course::factory()->count(2, 5)->unavailable()->create();
        $user = User::factory()->create();
        $user->givePermissionTo('courses.list');

        $response = $this->actingAs($user)->json('GET', $this->uri, ['can_be_applied' => $canBeApplied]);

        $response->assertOk();

        foreach ($filteredCourses as $filteredCourse) {
            $response->assertJsonFragment($filteredCourse->toArray());
        }
    }

    /** @test */
    public function it_should_get_available_courses_list()
    {
        Course::query()->update(['can_be_applied' => false]);
        $availableCourses = Course::factory()->available()->count(2, 5)->create();

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

        $courseData = Course::factory()->raw();

        $response = $this->actingAs($user)->json('POST', $this->uri, $courseData);

        $response->assertCreated()
            ->assertJsonFragment($courseData);

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

        $courseData = Course::factory()->raw();

        $response = $this->actingAs($user)->json('PUT', $this->uri . '/' . $course->id, $courseData);

        $response->assertSuccessful();

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
