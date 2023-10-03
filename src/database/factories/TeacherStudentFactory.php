<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\User;
use App\Models\UserCourse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TeacherStudent>
 */
class TeacherStudentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $examPassed = $this->faker->optional()->boolean;
        $courseId = Course::where('type', 'whatshafiz')->inRandomOrder()->first('id')->id;
        $teacherId = User::inRandomOrder()->first('id')->id;
        $studentId = User::inRandomOrder()->first('id')->id;
        // UserCourse::factory()->create(['course_id' => $courseId, 'user_id' => $teacherId, 'is_teacher' => true]);
        // UserCourse::factory()->create(['course_id' => $courseId, 'user_id' => $studentId, 'is_teacher' => false]);

        return [
            'teacher_id' => $teacherId,
            'student_id' => $studentId,
            'course_id' => $courseId,
            'is_active' => $examPassed && $this->faker->boolean(90),
            'proficiency_exam_passed' => $examPassed,
            'proficiency_exam_failed_description' => !$examPassed ? $this->faker->sentence : null,
        ];
    }
}
