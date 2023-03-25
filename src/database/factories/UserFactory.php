<?php

namespace Database\Factories;

use App\Models\City;
use App\Models\Country;
use App\Models\University;
use App\Models\UniversityDepartment;
use App\Models\UniversityFaculty;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'name' => $this->faker->firstname(),
            'surname' => $this->faker->lastname(),
            'email' => $this->faker->optional()->email(),
            'phone_number' => $this->faker->unique()->e164PhoneNumber(),
            'phone_number_verified_at' => now(),
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
            'remember_token' => Str::random(10),
            'gender' => $this->faker->optional()->randomElement(['male', 'female']),
            'country_id' => $this->faker->boolean ? null : Country::inRandomOrder()->value('id'),
            'city_id' => $this->faker->boolean ? null : City::inRandomOrder()->value('id'),
            'university_id' => $this->faker->boolean ? null : University::inRandomOrder()->value('id'),
            'university_faculty_id' => $this->faker->boolean ? null : UniversityFaculty::inRandomOrder()->value('id'),
            'university_department_id' => $this->faker->boolean ? null : UniversityDepartment::inRandomOrder()->value('id'),
        ];
    }

    /**
     * Indicate that the model's phone number should be unverified.
     *
     * @return static
     */
    public function completed()
    {
        return $this->state(fn (array $attributes) => [
            'gender' => $this->faker->randomElement(['male', 'female']),
            'country_id' => Country::inRandomOrder()->value('id'),
            'city_id' => City::inRandomOrder()->value('id'),
            'university_id' => University::inRandomOrder()->value('id'),
            'university_faculty_id' => UniversityFaculty::inRandomOrder()->value('id'),
            'university_department_id' => UniversityDepartment::inRandomOrder()->value('id'),
        ]);
    }

    /**
     * Indicate that the model's phone number should be unverified.
     *
     * @return static
     */
    public function unverified()
    {
        return $this->state(fn (array $attributes) => [
            'phone_number_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the model's phone number should be unverified.
     *
     * @return static
     */
    public function hasGender()
    {
        return $this->state(fn (array $attributes) => [
            'gender' => $this->faker->randomElement(['male', 'female']),
        ]);
    }
}
