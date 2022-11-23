<?php

namespace Database\Factories;

use App\Models\University;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UniversityFaculty>
 */
class UniversityFacultyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'university_id' => University::inRandomOrder()->value('id') ?? University::factory()->create()->id,
            'name' => $this->faker->jobTitle . ' ' . $this->faker->randomElement(['Fakültesi', 'Yüksekokulu']),
        ];
    }
}
