<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\User;
use App\Models\WhatsappGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserCourse>
 */
class UserCourseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $course = Course::inRandomOrder()->first();

        return [
            'course_type_id' => $course->course_type_id,
            'user_id' => User::factory()->create()->id,
            'course_id' => $course->id,
            'is_teacher' => $this->faker->boolean,
            'applied_at' => $this->faker->datetime,
            'removed_at' => $this->faker->optional(0.2)->datetime,
            'whatsapp_group_id' => WhatsappGroup::inRandomOrder()->value('id'),
        ];
    }

    /**
     * @return static
     */
    public function withNewUser()
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => User::factory()->completed()->create()->id,
        ]);
    }
}
