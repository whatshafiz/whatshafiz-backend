<?php

namespace Tests\Feature\Jobs;

use App\Jobs\CourseWhatsappGroupsOrganizer;
use App\Models\Course;
use App\Models\TeacherStudent;
use App\Models\UserCourse;
use App\Models\WhatsappGroup;
use Tests\BaseFeatureTest;

class CourseWhatsappGroupsOrganizerTest extends BaseFeatureTest
{
    /** @test */
    public function it_should_assign_teachers_and_users_to_whatsapp_groups()
    {
        $course = Course::factory()->whatshafiz()->create();
        $whatsappGroups = WhatsappGroup::factory()
            ->count(1, 2)
            ->create(['type' => 'whatshafiz', 'course_id' => $course->id, 'gender' => 'male']);
        $whatsappGroups = WhatsappGroup::factory()
            ->count(1, 2)
            ->create(['type' => 'whatshafiz', 'course_id' => $course->id, 'gender' => 'female']);
        $userCourseForTeachers = UserCourse::factory()
            ->withNewUser()
            ->count(rand(2, 5))
            ->create(['type' => 'whatshafiz', 'course_id' => $course->id, 'is_teacher' => true]);
        $userCourseForStudents = [];

        foreach ($userCourseForTeachers as $userCourseForTeacher) {
            $userCourseForStudentsRelated = UserCourse::factory()
                ->withNewUser()
                ->count(rand(1, 3))
                ->create(['type' => 'whatshafiz', 'course_id' => $course->id, 'is_teacher' => false]);

            foreach ($userCourseForStudentsRelated as $userCourseForStudent) {
                $userCourseForStudents[] = $userCourseForStudent;

                TeacherStudent::factory()
                    ->create([
                        'course_id' => $course->id,
                        'teacher_id' => $userCourseForTeacher->user_id,
                        'student_id' => $userCourseForStudent->user_id,
                        'proficiency_exam_passed' => true,
                    ]);
            }
        }

        $instance = resolve(CourseWhatsappGroupsOrganizer::class, ['course' => $course]);
        app()->call([$instance, 'handle']);

        foreach ($userCourseForTeachers as $userCourseForTeacher) {
            $this->assertDatabaseHas(
                'whatsapp_group_users',
                ['user_id' => $userCourseForTeacher->user_id, 'role_type' => 'hafizkal']
            );
        }

        foreach ($userCourseForStudents as $userCourseForStudent) {
            $this->assertDatabaseHas(
                'whatsapp_group_users',
                ['user_id' => $userCourseForStudent->user_id, 'role_type' => 'hafizol']
            );
        }
    }
}
