<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\QuranQuestion>
 */
class QuranQuestionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'name' => $this->faker->name,
            'page_number' => $this->faker->randomNumber(),
            'question' => $this->faker->text,
            'option_1' => $this->faker->text,
            'option_2' => $this->faker->text,
            'option_3' => $this->faker->text,
            'option_4' => $this->faker->text,
            'option_5' => $this->faker->text,
            'correct_option' => $this->faker->numberBetween(1, 5)
        ];
    }
}
