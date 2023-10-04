<?php

namespace Tests\Feature\Jobs;

use App\Jobs\CourseTeacherStudentsMatcher;
use App\Models\Course;
use App\Models\TeacherStudent;
use App\Models\User;
use App\Models\UserCourse;
use Illuminate\Support\Facades\Queue;
use Tests\BaseFeatureTest;

class CourseTeacherStudentsMatcherTest extends BaseFeatureTest
{
    /** @test */
    public function it_should_match_teachers_and_students()
    {
        Queue::fake();
        $course = Course::factory()->whatshafiz()->create();
        $userCourseForTeachers = UserCourse::factory()
            ->withNewUser()
            ->count(rand(18, 75))
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
                        'proficiency_exam_passed' => $this->faker->randomElement([1, null]),
                    ]);
            }
        }

        UserCourse::factory()
            ->withNewUser()
            ->count(rand(75, 150))
            ->create(['type' => 'whatshafiz', 'course_id' => $course->id, 'is_teacher' => false]);

        $instance = resolve(CourseTeacherStudentsMatcher::class, ['course' => $course]);
        app()->call([$instance, 'handle']);

        foreach ($userCourseForTeachers as $userCourseForTeacher) {
            $this->assertDatabaseHas(
                'teacher_students',
                ['course_id' => $course->id, 'teacher_id' => $userCourseForTeacher->user_id]
            );
        }

        foreach ($userCourseForStudents as $userCourseForStudent) {
            $this->assertDatabaseHas(
                'teacher_students',
                ['course_id' => $course->id, 'student_id' => $userCourseForStudent->user_id]
            );
        }
    }

    /** @test */
    public function it_should_match_most_related_user_for_teacher()
    {
        Queue::fake();
        $course = Course::factory()->whatshafiz()->create();
        $userCourseForTeacher = UserCourse::factory()
            ->withNewUser()
            ->create(['type' => 'whatshafiz', 'course_id' => $course->id, 'is_teacher' => true]);
        $teacher = $userCourseForTeacher->user;

        $standartStudents = UserCourse::factory()
            ->withNewUser()
            ->count(rand(1, 3))
            ->create(['type' => 'whatshafiz', 'course_id' => $course->id, 'is_teacher' => false]);

        $mostRelatedStudent = User::factory()
            ->create([
                'gender' => $teacher->gender,
                'country_id' => $teacher->country_id,
                'city_id' => $teacher->city_id,
                'education_level' => $teacher->education_level,
                'university_id' => $teacher->university_id,
            ]);

        UserCourse::factory()
            ->create([
                'type' => 'whatshafiz',
                'course_id' => $course->id,
                'user_id' => $mostRelatedStudent->id,
                'is_teacher' => false,
            ]);

        $instance = resolve(CourseTeacherStudentsMatcher::class, ['course' => $course]);
        app()->call([$instance, 'handle']);

        $this->assertDatabaseHas(
            'teacher_students',
            ['course_id' => $course->id, 'teacher_id' => $teacher->id, 'student_id' => $mostRelatedStudent->id]
        );
    }

    /** @test */
    public function it_should_match_less_related_user_for_teacher_when_there_is_no_most_related()
    {
        Queue::fake();
        $course = Course::factory()->whatshafiz()->create();
        $userCourseForTeacher = UserCourse::factory()
            ->withNewUser()
            ->create(['type' => 'whatshafiz', 'course_id' => $course->id, 'is_teacher' => true]);
        $teacher = $userCourseForTeacher->user;
        UserCourse::factory()
            ->withNewUser()
            ->count(rand(1, 3))
            ->create(['type' => 'whatshafiz', 'course_id' => $course->id, 'is_teacher' => false]);
        $lessRelatedStudent = User::factory()
            ->create([
                'gender' => $teacher->gender,
                'country_id' => $teacher->country_id,
                'education_level' => $teacher->education_level,
            ]);
        UserCourse::factory()
            ->create([
                'type' => 'whatshafiz',
                'course_id' => $course->id,
                'user_id' => $lessRelatedStudent->id,
                'is_teacher' => false,
            ]);

        $instance = resolve(CourseTeacherStudentsMatcher::class, ['course' => $course]);
        app()->call([$instance, 'handle']);

        $this->assertDatabaseHas(
            'teacher_students',
            ['course_id' => $course->id, 'teacher_id' => $teacher->id, 'student_id' => $lessRelatedStudent->id]
        );
    }

    /** @test */
    public function it_should_match_all_students_for_teacher()
    {
        $course = Course::factory()->whatshafiz()->create();
        $userCourseForTeacher = UserCourse::factory()
            ->withNewUser()
            ->create(['type' => 'whatshafiz', 'course_id' => $course->id, 'is_teacher' => true]);
        $teacher = $userCourseForTeacher->user;

        $students = User::factory()->count(rand(2, 5))->create(['gender' => $teacher->gender]);

        foreach ($students as $student) {
            UserCourse::factory()
                ->create([
                    'type' => 'whatshafiz',
                    'course_id' => $course->id,
                    'user_id' => $student->id,
                    'is_teacher' => false,
                ]);
        }

        $instance = resolve(CourseTeacherStudentsMatcher::class, ['course' => $course]);
        app()->call([$instance, 'handle']);

        foreach ($students as $student) {
            $this->assertDatabaseHas(
                'teacher_students',
                ['course_id' => $course->id, 'teacher_id' => $teacher->id, 'student_id' => $student->id]
            );
        }
    }
}
