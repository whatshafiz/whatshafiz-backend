<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Comment>
 */
class CommentFactory extends Factory
{
    /**
     * Define the Comment model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $is_approved = $this->faker->boolean;

        return [
            'type' => $this->faker->randomElement(['whatshafiz', 'whatsenglish', 'whatsarapp']),
            'title' => $this->faker->sentence,
            'comment' => $this->faker->text,
            'commented_by_id' => User::factory()->create()->id,
            'is_approved' => $is_approved,
            'approved_by_id' => $is_approved ? User::factory()->create()->id : null,
            'approved_at' => $is_approved ? $this->faker->datetime->format('Y-m-d H:i:s') : null,
        ];
    }
}