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
            'page_number' => $this->faker->numberBetween(1, 600),
            'question' => $this->faker->sentences(rand(3, 15), true),
            'option_1' => $this->faker->sentence,
            'option_2' => $this->faker->sentence,
            'option_3' => $this->faker->sentence,
            'option_4' => $this->faker->sentence,
            'option_5' => $this->faker->sentence,
            'correct_option' => $this->faker->numberBetween(1, 5)
        ];
    }
}
