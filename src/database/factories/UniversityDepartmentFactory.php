<?php

namespace Database\Factories;

use App\Models\UniversityFaculty;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UniversityDepartment>
 */
class UniversityDepartmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $faculty = UniversityFaculty::inRandomOrder()->first() ?? UniversityFaculty::factory()->create();

        return [
            'university_id' => $faculty->university_id,
            'university_faculty_id' => $faculty->id,
            'name' => $this->faker->jobTitle . ' Bölümü',
        ];
    }
}
