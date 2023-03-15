<?php

namespace Database\Factories;

use App\Models\QuranQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;

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
        $question = QuranQuestion::inRandomOrder()->first() ?? QuranQuestion::factory()->create();
        $answer = $this->faker->numberBetween(1, 5);
        return [
            'question_id' => $question->id,
            'user_id' => User::inRandomOrder()->value('id') ?? User::factory()->create()->id,
            'answer' => $this->faker->numberBetween(1, 5),
            'is_correct' => (boolean) $answer === $question->correct_option,
        ];
    }
}
