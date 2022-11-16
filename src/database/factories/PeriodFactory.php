<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Period>
 */
class PeriodFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'type' => $this->faker->randomElement(['hafizol', 'hafizkal']),
            'name' => $this->faker->numerify('WhatsHafız-######'),
            'is_active' => $this->faker->boolean,
            'can_be_applied' => $this->faker->boolean,
            'can_be_applied_until' => $this->faker->optional()->datetime?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * @return static
     */
    public function hafizol()
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'hafizol',
        ]);
    }

    /**
     * @return static
     */
    public function hafizkal()
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'hafizkal',
        ]);
    }
}
