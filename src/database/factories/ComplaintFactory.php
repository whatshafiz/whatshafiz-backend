<?php

namespace Database\Factories;

use App\Models\Complain;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Complain>
 */
class ComplaintFactory extends Factory
{
    /**
     * Define the Complain model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $isReviewed = $this->faker->boolean;

        return [
            'created_by' => User::factory()->create()->id,
            'reviewed_by' => $isReviewed ? User::factory()->create()->id : null,
            'reviewed_at' => $isReviewed ? $this->faker->datetime : null,
            'is_resolved' => $this->faker->boolean,
            'result' => $this->faker->text(100),
            'subject' => $this->faker->text(100),
            'description' => $this->faker->text(255),
            'related_user_id' => $this->faker->boolean ? User::factory()->create()->id : null,
        ];
    }
}
