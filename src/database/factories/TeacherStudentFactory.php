<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\User;
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

        return [
            'teacher_id' => User::inRandomOrder()->first('id')->id,
            'student_id' => User::inRandomOrder()->first('id')->id,
            'course_id' => Course::where('type', 'whatshafiz')->inRandomOrder()->first('id')->id,
            'is_active' => $examPassed && $this->faker->boolean(90),
            'proficiency_exam_passed' => $examPassed,
            'proficiency_exam_failed_description' => !$examPassed ? $this->faker->sentence : null,
        ];
    }
}
