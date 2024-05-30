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
        WhatsappGroup::factory()
            ->count(3, 5)
            ->create([
                'course_type_id' => CourseType::where('slug', 'whatshafiz')->value('id'),
                'course_id' => $course->id,
                'gender' => 'male',
            ]);
        WhatsappGroup::factory()
            ->count(3, 5)
            ->create([
                'course_type_id' => CourseType::where('slug', 'whatshafiz')->value('id'),
                'course_id' => $course->id,
                'gender' => 'female',
            ]);
        $userCourseTeachers = UserCourse::factory()
            ->withNewUser()
            ->count(rand(5, 15))
            ->create([
                'course_type_id' => CourseType::where('slug', 'whatshafiz')->value('id'),
                'course_id' => $course->id,
                'is_teacher' => true,
                'whatsapp_group_id' => null,
            ]);

        foreach ($userCourseTeachers as $userCourseTeacher) {
            $userCourseTeacherStudents = UserCourse::factory()
                ->withNewUser($userCourseTeacher->user->gender)
                ->count(rand(7, 11))
                ->create([
                    'course_type_id' => CourseType::where('slug', 'whatshafiz')->value('id'),
                    'course_id' => $course->id,
                    'is_teacher' => false,
                    'whatsapp_group_id' => null,
                ]);

            foreach ($userCourseTeacherStudents as $userCourseTeacherStudent) {
                TeacherStudent::factory()
                    ->create([
                        'course_id' => $course->id,
                        'teacher_id' => $userCourseTeacher->user_id,
                        'student_id' => $userCourseTeacherStudent->user_id,
                    ]);
            }
        }

        $instance = resolve(WhatshafizCourseWhatsappGroupsOrganizer::class, ['course' => $course]);
        app()->call([$instance, 'handle']);

        foreach ($userCourseTeachers as $userCourseTeacher) {
            $teacherId = $userCourseTeacher->user_id;
            $whatsappGroupId = UserCourse::where('user_id', $teacherId)
                ->where('course_id', $course->id)
                ->value('whatsapp_group_id');

            $teacherStudents = TeacherStudent::where('course_id', $course->id)
                ->where('teacher_id', $teacherId)
                ->where('is_active', true)
                ->where('proficiency_exam_passed', true)
                ->get();

            foreach ($teacherStudents as $teacherStudent) {
                $this->assertDatabaseHas(
                    'user_course',
                    [
                        'course_type_id' => $course->course_type_id,
                        'course_id' => $course->id,
                        'user_id' => $teacherStudent->student_id,
                        'whatsapp_group_id' => $whatsappGroupId,
                        'is_teacher' => false,
                    ]
                );
            }

            $teacherDeclinedStudents = TeacherStudent::where('course_id', $course->id)
                ->where('teacher_id', $teacherId)
                ->where('proficiency_exam_passed', false)
                ->get();

            foreach ($teacherDeclinedStudents as $teacherDeclinedStudent) {
                $this->assertDatabaseHas(
                    'user_course',
                    [
                        'course_id' => $course->id,
                        'user_id' => $teacherDeclinedStudent->student_id,
                        'whatsapp_group_id' => null,
                    ]
                );
            }
        }
    }
}
