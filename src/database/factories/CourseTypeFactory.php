<?php

namespace Database\Factories;

use App\Models\CourseType;
use App\Models\EducationLevel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CourseType>
 */
class CourseTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $name = $this->faker->lexify('Whats-???????');

        return [
            'parent_id' => $this->faker->boolean ? null : CourseType::inRandomOrder()->value('id'),
            'name' => $name,
            'slug' => Str::slug($name),
            'is_active' => $this->faker->boolean,
            'has_admission_exam' => $this->faker->boolean,
            'min_age' => $this->faker->optional()->numberBetween(7, 30),
            'genders' => ['male', 'female'],
            'education_levels' => EducationLevel::inRandomOrder()->limit(rand(1, 5))->pluck('name')->toArray(),
        ];
    }
}
