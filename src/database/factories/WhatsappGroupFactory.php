<?php

namespace Database\Factories;

use App\Models\Period;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WhatsappGroup>
 */
class WhatsappGroupFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'period_id' => Period::inRandomOrder()->value('id') ?? Period::factory()->create()->id,
            'type' => $this->faker->randomElement(['whatshafiz', 'whatsenglish', 'whatsarapp']),
            'name' => $this->faker->numerify('WhatsGroup-##-##-##'),
            'is_active' => $this->faker->boolean,
            'join_url' => $this->faker->url,
        ];
    }
}
