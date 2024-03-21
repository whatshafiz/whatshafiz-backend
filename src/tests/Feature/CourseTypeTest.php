<?php

namespace Tests\Feature;

use App\Models\CourseType;
use App\Models\User;
use Illuminate\Support\Str;
use Tests\BaseFeatureTest;

class CourseTypeTest extends BaseFeatureTest
{
    protected string $uri;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->uri = self::BASE_URI . '/course-types';
    }

    /** @test */
    public function it_should_not_get_course_types_list_when_does_not_have_permission()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->json('GET', $this->uri);

        $response->assertForbidden();
    }

    /** @test */
    public function it_should_get_course_types_list_when_has_permission()
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        $courseTypes = CourseType::factory()->count(rand(1, 10))->create();

        $response = $this->actingAs($user)->json('GET', $this->uri);

        $response->assertOk();

        foreach ($courseTypes as $courseType) {
            $response->assertJsonFragment($courseType->toArray());
        }
    }

    /** @test */
    public function user_can_list_course_types_when_has_admin_role_as_paginated_by_filtering()
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        $searchCourseType = CourseType::inRandomOrder()->first();

        $searchQuery = [
            'filter' => [['value' => $searchCourseType->name]],
        ];

        $response = $this->actingAs($user)->json('GET', $this->uri . '/paginate', $searchQuery);

        $response->assertOk()
            ->assertJsonFragment($searchCourseType->toArray());
    }

    /** @test */
    public function it_should_not_get_course_type_details_when_has_permission()
    {
        $user = User::factory()->create();

        $courseType = CourseType::factory()->create();

        $response = $this->actingAs($user)->json('GET', $this->uri . '/' . $courseType->id);

        $response->assertForbidden();
    }

    /** @test */
    public function it_should_get_course_type_details_when_has_permission()
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        $courseType = CourseType::factory()->create();

        $response = $this->actingAs($user)->json('GET', $this->uri . '/' . $courseType->id);

        $response->assertOk()
            ->assertJsonFragment($courseType->toArray());
    }

    /** @test */
    public function it_should_not_create_course_type_when_does_not_have_permission()
    {
        $user = User::factory()->create();

        $courseTypeData = CourseType::factory()->raw();

        $response = $this->actingAs($user)->json('POST', $this->uri, $courseTypeData);

        $response->assertForbidden();
    }

    /** @test */
    public function it_should_create_course_type_when_has_permission()
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        $courseTypeData = CourseType::factory()->raw();

        $response = $this->actingAs($user)->json('POST', $this->uri, $courseTypeData);

        $response->assertSuccessful();

        $courseTypeData['genders'] = $this->castToJson($courseTypeData['genders']);
        $courseTypeData['education_levels'] = $this->castToJson($courseTypeData['education_levels']);

        $this->assertDatabaseHas('course_types', $courseTypeData);
    }

    /** @test */
    public function it_should_not_update_course_type_when_does_not_have_permission()
    {
        $user = User::factory()->create();

        $courseType = CourseType::factory()->create();
        $courseTypeNewData = CourseType::factory()->raw();

        $response = $this->actingAs($user)->json('PUT', $this->uri . '/' . $courseType->id, $courseTypeNewData);

        $response->assertForbidden();
    }

    /** @test */
    public function it_should_update_course_type_when_has_permission()
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        $courseType = CourseType::factory()->create();
        $courseTypeNewData = CourseType::factory()->raw();

        $response = $this->actingAs($user)->json('PUT', $this->uri . '/' . $courseType->id, $courseTypeNewData);

        $response->assertOk();

        $courseTypeNewData['genders'] = $this->castToJson($courseTypeNewData['genders']);
        $courseTypeNewData['education_levels'] = $this->castToJson($courseTypeNewData['education_levels']);

        $this->assertDatabaseHas('course_types', array_merge(['id' => $courseType->id], $courseTypeNewData));
    }

    /** @test */
    public function it_should_not_delete_course_type_when_does_not_have_permission()
    {
        $user = User::factory()->create();

        $courseType = CourseType::factory()->create();

        $response = $this->actingAs($user)->json('DELETE', $this->uri . '/' . $courseType->id);

        $response->assertForbidden();
    }

    /** @test */
    public function it_should_delete_course_type_when_has_permission()
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        $courseType = CourseType::factory()->create();

        $response = $this->actingAs($user)->json('DELETE', $this->uri . '/' . $courseType->id);

        $response->assertSuccessful();

        $this->assertSoftDeleted('course_types', ['id' => $courseType->id]);
    }
}
