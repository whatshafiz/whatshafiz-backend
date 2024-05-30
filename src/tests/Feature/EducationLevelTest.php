<?php

namespace Tests\Feature;

use App\Models\EducationLevel;
use App\Models\User;
use Tests\BaseFeatureTest;

class EducationLevelTest extends BaseFeatureTest
{
    protected string $uri;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->uri = self::BASE_URI . '/education-levels';
    }

    /** @test */
    public function it_should_get_education_levels_list_when_logged_in()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->json('GET', $this->uri);

        $response->assertOk();

        foreach (EducationLevel::get() as $educationLevel) {
            $response->assertJsonFragment($educationLevel->toArray());
        }
    }
}
