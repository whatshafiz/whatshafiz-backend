<?php

namespace Tests\Feature\Jobs;

use App\Jobs\WhatshafizCourseWhatsappGroupsOrganizer;
use App\Models\Course;
use App\Models\CourseType;
use App\Models\TeacherStudent;
use App\Models\UserCourse;
use App\Models\WhatsappGroup;
use Tests\BaseFeatureTest;

class WhatshafizCourseWhatsappGroupsOrganizerTest extends BaseFeatureTest
{
    /** @test */
    public function it_should_assign_teachers_and_users_to_whatsapp_groups()
    {
        $course = Course::factory()->whatshafiz()->create();
        $whatsappGroups = WhatsappGroup::factory()
            ->count(1, 2)
            ->create([
                'course_type_id' => CourseType::where('slug', 'whatshafiz')->value('id'),
                'course_id' => $course->id,
                'gender' => 'male',
            ]);
        $whatsappGroups = WhatsappGroup::factory()
            ->count(1, 2)
            ->create([
                'course_type_id' => CourseType::where('slug', 'whatshafiz')->value('id'),
                'course_id' => $course->id,
                'gender' => 'female',
            ]);
        $userCourseForTeachers = UserCourse::factory()
            ->withNewUser()
            ->count(rand(2, 5))
            ->create([
                'course_type_id' => CourseType::where('slug', 'whatshafiz')->value('id'),
                'course_id' => $course->id,
                'is_teacher' => true,
            ]);
        $userCourseForStudents = [];

        foreach ($userCourseForTeachers as $userCourseForTeacher) {
            $userCourseForStudentsRelated = UserCourse::factory()
                ->withNewUser()
                ->count(rand(1, 3))
                ->create([
                    'course_type_id' => CourseType::where('slug', 'whatshafiz')->value('id'),
                    'course_id' => $course->id,
                    'is_teacher' => false,
                ]);

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

        $instance = resolve(WhatshafizCourseWhatsappGroupsOrganizer::class, ['course' => $course]);
        app()->call([$instance, 'handle']);

        foreach ($userCourseForTeachers as $userCourseForTeacher) {
            $this->assertDatabaseHas(
                'user_course',
                ['user_id' => $userCourseForTeacher->user_id, 'is_teacher' => true]
            );
        }

        foreach ($userCourseForStudents as $userCourseForStudent) {
            $this->assertDatabaseHas(
                'user_course',
                ['user_id' => $userCourseForStudent->user_id, 'is_teacher' => false]
            );
        }
    }
}
