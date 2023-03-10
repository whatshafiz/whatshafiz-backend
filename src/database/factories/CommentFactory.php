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
            'name' => $this->faker->name,
            'comment' => $this->faker->text,
            'user_id' => User::factory()->create()->id,
            'is_approved' => $is_approved,
            'approved_by' => $is_approved ? User::factory()->create()->id : null,
        ];
    }
}
