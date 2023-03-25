<?php

namespace Database\Factories;

use App\Models\QuranQuestion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AnswerAttempt>
 */
class AnswerAttemptFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $quranQuestion = QuranQuestion::inRandomOrder()->first() ?? QuranQuestion::factory()->create();
        $selectedOptionNumber = $this->faker->numberBetween(1, 5);

        return [
            'user_id' => User::inRandomOrder()->value('id') ?? User::factory()->create()->id,
            'quran_question_id' => $quranQuestion->id,
            'selected_option_number' => $selectedOptionNumber,
            'is_correct_option' => $selectedOptionNumber === $quranQuestion->correct_option,
        ];
    }
}
