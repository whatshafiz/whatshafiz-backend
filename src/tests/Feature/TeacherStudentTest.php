<?php

namespace Tests\Feature;

use App\Models\TeacherStudent;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;
use Tests\BaseFeatureTest;

class TeacherStudentTest extends BaseFeatureTest
{
    protected string $uri;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->uri = self::BASE_URI . '/teacher-students';
    }

    /** @test */
    public function it_should_not_get_teacher_student_when_does_not_have_permission()
    {
        $teacherStudent = TeacherStudent::factory()->create();
        $user = User::factory()->create();

        $response = $this->actingAs($user)->json('GET', $this->uri . '/' . $teacherStudent->id);

        $response->assertForbidden();
    }

    /** @test */
    public function it_should_get_teacher_student_details_when_has_permission()
    {
        $teacherStudent = TeacherStudent::factory()->create();
        $user = User::factory()->create();
        $user->givePermissionTo('teacherStudents.view');

        $response = $this->actingAs($user)->json('GET', $this->uri . '/' . $teacherStudent->id);

        $response->assertOk()
            ->assertJsonFragment($teacherStudent->toArray());
    }

    /** @test */
    public function it_should_not_get_teacher_students_list_when_does_not_have_permission()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->json('GET', $this->uri);

        $response->assertForbidden();
    }

    /** @test */
    public function it_should_get_teacher_students_list_when_has_permission()
    {
        $teacherStudents = TeacherStudent::factory()->count(2, 5)->create();
        $user = User::factory()->create();
        $user->givePermissionTo('teacherStudents.list');

        $response = $this->actingAs($user)->json('GET', $this->uri);

        $response->assertOk();

        foreach ($teacherStudents as $teacherStudent) {
            $response->assertJsonFragment($teacherStudent->toArray());
        }
    }

    /** @test */
    public function it_should_get_own_teacher_students_list_when_has_permission()
    {
        $teacherStudents = TeacherStudent::factory()->count(2, 5)->create();
        $user = User::factory()->create();
        $user->teacherStudents()->attach($teacherStudents);

        $response = $this->actingAs($user)->json('GET', self::BASE_URI . '/my/teacherStudents');

        $response->assertOk();

        foreach ($teacherStudents as $teacherStudent) {
            $response->assertJsonFragment($teacherStudent->toArray());
        }
    }

    /** @test */
    public function it_should_get_teacher_students_list_when_has_permission_by_filtering_and_as_paginated()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('teacherStudents.list');

        $teacherStudents = TeacherStudent::factory()->count(5)->create();
        $searchTeacherStudent = $teacherStudents->random();
        $user->teacherStudents()->attach($searchTeacherStudent);
        $searchQuery = [
            'user_id' => $user->id,
            'filter' => [['value' => $searchTeacherStudent->name]],
        ];

        $response = $this->actingAs($user)->json('GET', $this->uri . '/paginate', $searchQuery);

        $response->assertOk()
            ->assertJsonFragment($searchTeacherStudent->toArray());
    }

    /** @test */
    public function it_should_get_available_teacher_students_list_from_cache_when_available_teacher_students_cached_before()
    {
        TeacherStudent::query()->update(['can_be_applied' => false]);
        $availableTeacherStudents = TeacherStudent::factory()->available()->count(2, 5)->create();

        Cache::shouldReceive('has')->with(TeacherStudent::AVAILABLE_COURSES_CACHE_KEY)->once()->andReturn(true);
        Cache::shouldReceive('get')->with(TeacherStudent::AVAILABLE_COURSES_CACHE_KEY)->once()->andReturn($availableTeacherStudents);

        $response = $this->json('GET', $this->uri . '/available');

        $response->assertOk();

        foreach ($availableTeacherStudents as $availableTeacherStudent) {
            $response->assertJsonFragment(
                Arr::only(
                    $availableTeacherStudent->toArray(),
                    ['id', 'type', 'name', 'can_be_applied', 'can_be_applied_until', 'start_at']
                )
            );
        }
    }

    /** @test */
    public function it_should_get_available_teacher_students_list_from_database_and_put_it_to_cache_when_available_teacher_students_did_not_cached_before()
    {
        TeacherStudent::query()->update(['can_be_applied' => false]);
        $availableTeacherStudents = TeacherStudent::factory()->available()->count(2, 5)->create();

        Cache::shouldReceive('has')->with(TeacherStudent::AVAILABLE_COURSES_CACHE_KEY)->once()->andReturn(false);
        Cache::shouldReceive('get')->with(TeacherStudent::AVAILABLE_COURSES_CACHE_KEY)->never();
        Cache::shouldReceive('put')->once();

        $response = $this->json('GET', $this->uri . '/available');

        $response->assertOk();

        foreach ($availableTeacherStudents as $availableTeacherStudent) {
            $response->assertJsonFragment(
                Arr::only(
                    $availableTeacherStudent->toArray(),
                    ['id', 'type', 'name', 'can_be_applied', 'can_be_applied_until', 'start_at']
                )
            );
        }
    }

    /** @test */
    public function it_should_not_create_teacher_student_when_does_not_have_permission()
    {
        $user = User::factory()->create();
        $teacherStudentData = TeacherStudent::factory()->raw();

        $response = $this->actingAs($user)->json('POST', $this->uri, $teacherStudentData);

        $response->assertForbidden();
    }

    /** @test */
    public function it_should_create_teacher_student_when_has_permission()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('teacherStudents.create');
        TeacherStudent::query()->update(['can_be_applied' => false]);

        $teacherStudentData = TeacherStudent::factory()->raw([
            'start_at' =>  $this->faker->datetime->format('Y-m-d\TH:i'),
            'can_be_applied_until' =>  $this->faker->datetime->format('Y-m-d\TH:i'),
            'proficiency_exam_start_time' =>  $this->faker->datetime->format('Y-m-d\TH:i'),
        ]);

        $response = $this->actingAs($user)->json('POST', $this->uri, $teacherStudentData);

        $teacherStudentData['start_at'] = Carbon::parse($teacherStudentData['start_at'])->format('d-m-Y H:i');
        $teacherStudentData['can_be_applied_until'] = Carbon::parse($teacherStudentData['can_be_applied_until'])->format('d-m-Y H:i');
        $teacherStudentData['proficiency_exam_start_time'] = Carbon::parse($teacherStudentData['proficiency_exam_start_time'])
            ->format('d-m-Y H:i');

        $response->assertCreated()
            ->assertJsonFragment($teacherStudentData);

        $teacherStudentData['start_at'] = Carbon::parse($teacherStudentData['start_at'])->format('Y-m-d H:i:s');
        $teacherStudentData['can_be_applied_until'] = Carbon::parse($teacherStudentData['can_be_applied_until'])->format('Y-m-d H:i:s');
        $teacherStudentData['proficiency_exam_start_time'] = Carbon::parse($teacherStudentData['proficiency_exam_start_time'])
            ->format('Y-m-d H:i:s');

        $this->assertDatabaseHas('teacherStudents', $teacherStudentData);
    }

    /** @test */
    public function it_should_not_create_teacher_student_as_can_be_applied_when_there_is_already_can_be_applied_teacher_student_for_type()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('teacherStudents.create');

        $teacherStudentData = TeacherStudent::factory()->raw(['can_be_applied' => true]);
        TeacherStudent::factory()->create(['can_be_applied' => true, 'type' => $teacherStudentData['type']]);

        $response = $this->actingAs($user)->json('POST', $this->uri, $teacherStudentData);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors('can_be_applied');
    }

    /** @test */
    public function it_should_not_update_teacher_student_when_does_not_have_permission()
    {
        $teacherStudent = TeacherStudent::factory()->create();
        $user = User::factory()->create();
        $teacherStudentData = TeacherStudent::factory()->raw();

        $response = $this->actingAs($user)->json('PUT', $this->uri . '/' . $teacherStudent->id, $teacherStudentData);

        $response->assertForbidden();
    }

    /** @test */
    public function it_should_update_teacher_student_when_has_permission()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('teacherStudents.update');
        TeacherStudent::query()->update(['can_be_applied' => false]);
        $teacherStudent = TeacherStudent::factory()->create();

        $teacherStudentData = TeacherStudent::factory()->raw([
            'start_at' =>  $this->faker->datetime->format('Y-m-d\TH:i'),
            'can_be_applied_until' =>  $this->faker->datetime->format('Y-m-d\TH:i'),
            'proficiency_exam_start_time' =>  $this->faker->datetime->format('Y-m-d\TH:i'),
        ]);

        $response = $this->actingAs($user)->json('PUT', $this->uri . '/' . $teacherStudent->id, $teacherStudentData);

        $response->assertSuccessful();

        $teacherStudentData['start_at'] = Carbon::parse($teacherStudentData['start_at'])->format('Y-m-d H:i:s');
        $teacherStudentData['can_be_applied_until'] = Carbon::parse($teacherStudentData['can_be_applied_until'])->format('Y-m-d H:i:s');
        $teacherStudentData['proficiency_exam_start_time'] = Carbon::parse($teacherStudentData['proficiency_exam_start_time'])
            ->format('Y-m-d H:i:s');

        $this->assertDatabaseHas('teacherStudents', array_merge(['id' => $teacherStudent->id], $teacherStudentData));
    }

    /** @test */
    public function it_should_not_update_teacher_student_as_can_be_applied_when_there_is_already_can_be_applied_teacher_student_for_type()
    {
        $teacherStudent = TeacherStudent::factory()->create(['can_be_applied' => false]);
        TeacherStudent::factory()->create(['can_be_applied' => true, 'type' => $teacherStudent->type]);
        $user = User::factory()->create();
        $user->givePermissionTo('teacherStudents.update');

        $teacherStudentData = TeacherStudent::factory()->raw(['can_be_applied' => true, 'type' => $teacherStudent->type]);

        $response = $this->actingAs($user)->json('PUT', $this->uri . '/' . $teacherStudent->id, $teacherStudentData);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors('can_be_applied');
    }

    /** @test */
    public function it_should_not_delete_teacher_student_when_does_not_have_permission()
    {
        $teacherStudent = TeacherStudent::factory()->create();
        $user = User::factory()->create();

        $response = $this->actingAs($user)->json('DELETE', $this->uri . '/' . $teacherStudent->id);

        $response->assertForbidden();
    }

    /** @test */
    public function it_should_delete_teacher_student_when_has_permission()
    {
        $teacherStudent = TeacherStudent::factory()->create();
        $user = User::factory()->create();
        $user->givePermissionTo('teacherStudents.delete');

        $response = $this->actingAs($user)->json('DELETE', $this->uri . '/' . $teacherStudent->id);

        $response->assertSuccessful();

        $this->assertSoftDeleted('teacherStudents', ['id' => $teacherStudent->id]);
    }
}
