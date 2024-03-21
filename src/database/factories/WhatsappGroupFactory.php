<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\CourseType;
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
            'course_id' => Course::inRandomOrder()->value('id') ?? Course::factory()->create()->id,
            'course_type_id' => CourseType::inRandomOrder()->value('id'),
            'gender' => $this->faker->randomElement(['male', 'female']),
            'name' => $this->faker->numerify('WhatsGroup-##-##-##'),
            'is_active' => $this->faker->boolean,
            'join_url' => $this->faker->url,
        ];
    }
}
