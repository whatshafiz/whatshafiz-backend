<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WhatsappMessengerNumber>
 */
class WhatsappMessengerNumberFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'phone_number' => $this->faker->phoneNumber,
            'instance_id' => $this->faker->bothify('i-?#??#?##?##?##'),
            'qrcode_url' => $this->faker->imageUrl(640, 480, 'animals', true),
            'screenshots_path' => $this->faker->url(),
            'is_active' => $this->faker->boolean(50),
            'last_activity_at' => $this->faker->optional()->datetime?->format('Y-m-d H:i:s'),
        ];
    }
}
