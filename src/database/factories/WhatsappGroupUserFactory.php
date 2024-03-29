<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\WhatsappGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WhatsappGroupUser>
 */
class WhatsappGroupUserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $whatsappGroup = WhatsappGroup::inRandomOrder()->first() ?? WhatsappGroup::factory()->create();

        return [
            'whatsapp_group_id' => $whatsappGroup->id,
            'course_id' => $whatsappGroup->course_id,
            'user_id' => User::inRandomOrder()->value('id') ?? User::factory()->create()->id,
            'joined_at' => $this->faker->datetime->format('Y-m-d H:i:s'),
            'role_type' => $this->faker->randomElement(['hafizol', 'hafizkal']),
            'is_moderator' => ($isModerator = $this->faker->boolean),
            'moderation_started_at' => $isModerator ? $this->faker->datetime->format('Y-m-d H:i:s') : null,
        ];
    }
}
