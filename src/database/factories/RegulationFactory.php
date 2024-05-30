<?php

namespace Database\Factories;

use App\Models\CourseType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Regulation>
 */
class RegulationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $name = $this->faker->lexify('Whats?????');

        return [
            'course_type_id' => CourseType::inRandomOrder()->value('id'),
            'name' => $name,
            'slug' => Str::slug($name),
            'summary' => $this->faker->sentences(3, true),
            'text' => $this->faker->sentences(10, true),
        ];
    }
}
