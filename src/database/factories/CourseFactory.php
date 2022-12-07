<?php

namespace Database\Factories;

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
            'is_active' => $this->faker->boolean,
            'can_be_applied' => $this->faker->boolean,
            'can_be_applied_until' => $this->faker->optional()->datetime?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * @return static
     */
    public function whatshafiz()
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'whatshafiz',
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
}
