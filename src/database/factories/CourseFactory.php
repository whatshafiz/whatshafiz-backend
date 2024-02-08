<?php

namespace Database\Factories;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Course>
 */
class CourseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'type' => $this->faker->randomElement(['whatshafiz', 'whatsenglish', 'whatsarapp']),
            'name' => $this->faker->numerify('WhatsHafız-######'),
            'whatsapp_channel_join_url' => $this->faker->optional(0.7)->url,
            'is_active' => $this->faker->boolean,
            'can_be_applied' => $this->faker->boolean,
            'can_be_applied_until' => Carbon::now()->addDays(rand(1, 14)),
            'start_at' => Carbon::now()->addDays(rand(1, 14)),
        ];
    }

    /**
     * @return static
     */
    public function whatshafiz()
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'whatshafiz',
            'proficiency_exam_start_time' => Carbon::now()->addDays(rand(1, 14)),
            'students_matchings_started_at' => $this->faker->boolean ? Carbon::now()->subDays(rand(1, 14)) : null,
        ]);
    }

    /**
     * @return static
     */
    public function whatsarapp()
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'whatsarapp',
        ]);
    }

    /**
     * @return static
     */
    public function whatsenglish()
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'whatsenglish',
        ]);
    }

    /**
     * @return static
     */
    public function available()
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
            'can_be_applied' => true,
            'can_be_applied_until' => Carbon::now()->addDays(rand(15, 100)),
        ]);
    }

    /**
     * @return static
     */
    public function unavailable()
    {
        $byDate = $this->faker->boolean;

        return $this->state(fn (array $attributes) => [
            'can_be_applied' => !$byDate,
            'can_be_applied_until' => $byDate ?
                Carbon::now()->addDays(rand(15, 100)) :
                Carbon::now()->subDays(rand(1, 100)),
        ]);
    }
}
