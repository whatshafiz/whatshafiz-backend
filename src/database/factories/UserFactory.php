<?php

namespace Database\Factories;

use App\Models\City;
use App\Models\UniversityDepartment;
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
        $universityDepartment = $this->faker->boolean ? null : UniversityDepartment::inRandomOrder()->first();
        $city = $this->faker->boolean ? null : City::inRandomOrder()->first();
        $gender = $this->faker->optional()->randomElement(['male', 'female']);

        return [
            'name' => $this->faker->firstname($gender),
            'surname' => $this->faker->lastname(),
            'email' => $this->faker->optional()->email(),
            'phone_number' => $this->faker->unique()->e164PhoneNumber(),
            'phone_number_verified_at' => now(),
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
            'remember_token' => Str::random(10),
            'gender' => $gender,
            'country_id' => $city ? $city->country_id : null,
            'city_id' => $city ? $city->id : null,
            'education_level' => $this->faker->randomElement(['İlkokul', 'Lise', 'Lisans', 'Ön Lisans']) . ' Mezunu',
            'university_id' => $universityDepartment ? $universityDepartment->university_id : null,
            'university_faculty_id' => $universityDepartment ? $universityDepartment->university_faculty_id : null,
            'university_department_id' => $universityDepartment ? $universityDepartment->id : null,
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
            'email' => $this->faker->email(),
            'gender' => $this->faker->randomElement(['male', 'female']),
            'country_id' => ($city = City::inRandomOrder()->where('id', '<', '10')->first()) ? $city->country_id : null,
            'city_id' => $city->id ?? null,
            'education_level' => $this->faker->randomElement(['İlkokul', 'Lise', 'Lisans', 'Ön Lisans']) . ' Mezunu',
            'university_id' => (
                    $universityDepartment = UniversityDepartment::inRandomOrder()->where('id', '<', '1000')->first()
                ) ?
                $universityDepartment->university_id :
                null,
            'university_faculty_id' => $universityDepartment->university_faculty_id ?? null,
            'university_department_id' => $universityDepartment->id ?? null,
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
    public function male()
    {
        return $this->state(fn (array $attributes) => [
            'name' => $this->faker->name('male'),
            'gender' => 'male',
        ]);
    }

    /**
     * Indicate that the model's phone number should be unverified.
     *
     * @return static
     */
    public function female()
    {
        return $this->state(fn (array $attributes) => [
            'name' => $this->faker->name('female'),
            'gender' => 'female',
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
