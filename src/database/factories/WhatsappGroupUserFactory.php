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
            'course_type_id' => $whatsappGroup->course_type_id,
            'whatsapp_group_id' => $whatsappGroup->id,
            'course_id' => $whatsappGroup->course_id,
            'user_id' => User::factory()->create()->id,
            'joined_at' => $this->faker->datetime->format('Y-m-d H:i:s'),
            'is_teacher' => $this->faker->boolean,
            'is_moderator' => ($isModerator = $this->faker->boolean),
            'moderation_started_at' => $isModerator ? $this->faker->datetime->format('Y-m-d H:i:s') : null,
        ];
    }
}
