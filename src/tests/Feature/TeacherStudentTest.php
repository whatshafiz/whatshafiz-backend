<?php

namespace Tests\Feature;

use App\Jobs\TeacherStudentTeacherStudentsMatcher;
use App\Jobs\TeacherStudentWhatsappGroupsOrganizer;
use App\Models\TeacherStudent;
use App\Models\User;
use App\Models\UserTeacherStudent;
use App\Models\WhatsappGroup;
use App\Models\WhatsappGroupUser;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
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

        $this->uri = self::BASE_URI;
    }

    /** @test */
    public function it_should_get_users_teacher_details()
    {
        $teacher1 = User::inRandomOrder()->first();
        $teacher2 = User::inRandomOrder()->first();
        $user = User::factory()->create();
        TeacherStudent::factory()->create(['teacher_id' => $teacher1->id, 'student_id' => $user->id]);
        TeacherStudent::factory()->create(['teacher_id' => $teacher2->id, 'student_id' => $user->id]);

        $response = $this->actingAs($user)->json('GET', $this->uri . '/my/teachers');

        $response->assertOk()
            ->assertJsonFragment($teacher1->toArray())
            ->assertJsonFragment($teacher2->toArray());
    }

    /** @test */
    public function it_should_get_users_own_students_list()
    {
        $user = User::factory()->create();
        $studentIds = TeacherStudent::factory()->count(rand(2, 5))->create(['teacher_id' => $user->id])->pluck('student_id');

        $response = $this->actingAs($user)->json('GET', $this->uri . '/my/students');

        $response->assertOk();

        foreach (User::whereIn('id', $studentIds)->get() as $student) {
            $response->assertJsonFragment($student->toArray());
        }
    }

    /** @test */
    public function it_should_update_users_own_student_status_data()
    {
        $user = User::factory()->create();
        $studentMatchingId = TeacherStudent::factory()->create(['teacher_id' => $user->id])->id;
        $newStatusData = TeacherStudent::factory()
            ->make()
            ->only('proficiency_exam_passed', 'proficiency_exam_failed_description');

        $response = $this->actingAs($user)
            ->json('PUT', $this->uri . '/my/students/' . $studentMatchingId, $newStatusData);

        $response->assertSuccessful();

        $this->assertDatabaseHas('teacher_students', array_merge(['id' => $studentMatchingId], $newStatusData));
    }
}
