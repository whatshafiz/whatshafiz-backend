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
        return [
            'created_by' => User::factory()->create()->id,
            'reviewed_by' => null,
            'reviewed_at' => null,
            'is_fixed' => $this->faker->boolean,
            'result' => $this->faker->text(100),
            'subject' => $this->faker->text(100),
            'description' => $this->faker->text(100),
            'related_user_id' => User::factory()->create()->id,
        ];
    }
}
