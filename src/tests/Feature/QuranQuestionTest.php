<?php

namespace Tests\Feature;

use App\Models\QuranQuestion;
use App\Models\User;
use Tests\BaseFeatureTest;

class QuranQuestionTest extends BaseFeatureTest
{
    protected string $uri;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->uri = self::BASE_URI . '/quran-questions';
    }

    /** @test */
    public function it_should_not_get_quran_questions_list_when_does_not_have_permission()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->json('GET', $this->uri);

        $response->assertForbidden();
    }

    /** @test */
    public function it_should_get_quran_questions_list_when_does_have_permission()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('quranQuestions.list');

        $response = $this->actingAs($user)->json('GET', $this->uri);

        $response->assertOk();
    }

    /** @test */
    public function qura_questions_filters_should_work()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('quranQuestions.list');

        $quranQuestion = QuranQuestion::factory()->count(5)->create();

        $searchQuery = [
            'page_number' => $quranQuestion->first()->page_number,
            'question' => $quranQuestion->first()->question,
            'option_1' => $quranQuestion->first()->option_1,
            'option_2' => $quranQuestion->first()->option_2,
            'option_3' => $quranQuestion->first()->option_3,
            'option_4' => $quranQuestion->first()->option_4,
            'option_5' => $quranQuestion->first()->option_5,
            'correct_option' => $quranQuestion->first()->correct_option,
        ];

        $response = $this->actingAs($user)->json(
            'GET',
            $this->uri,
            $searchQuery
        );

        $response->assertOk();

        foreach (QuranQuestion::where($searchQuery)->get() as $question) {
            $response->assertJsonFragment($question->toArray());
        }
    }

    /** @test */
    public function it_should_not_create_quran_question_when_does_not_have_permission()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->json(
            'POST',
            $this->uri,
            [
                'question' => 'test',
                'option_1' => 'test',
                'option_2' => 'test',
                'option_3' => 'test',
                'option_4' => 'test',
                'option_5' => 'test',
                'correct_option' => 1,
            ]
        );

        $response->assertForbidden();
    }

    /** @test */
    public function it_should_create_quran_question_when_does_have_permission()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('quranQuestions.create');

        $response = $this->actingAs($user)->json(
            'POST',
            $this->uri,
            [
                'page_number' => 1,
                'question' => 'test',
                'option_1' => 'test',
                'option_2' => 'test',
                'option_3' => 'test',
                'option_4' => 'test',
                'option_5' => 'test',
                'correct_option' => 1,
            ]
        );

        $response->assertSuccessful();

        $this->assertDatabaseHas('quran_questions', [
            'page_number' => 1,
            'question' => 'test',
            'option_1' => 'test',
            'option_2' => 'test',
            'option_3' => 'test',
            'option_4' => 'test',
            'option_5' => 'test',
            'correct_option' => 1,
        ]);
    }

    /** @test */
    public function it_should_not_update_quran_question_when_does_not_have_permission()
    {
        $quranQuestion = QuranQuestion::factory()->create();

        $user = User::factory()->create();

        $response = $this->actingAs($user)->json(
            'PUT',
            $this->uri . '/' . $quranQuestion->id,
            [
                'question' => 'test'
            ]
        );

        $response->assertForbidden();
    }

    /** @test */
    public function it_should_update_quran_question_when_does_have_permission()
    {
        $quranQuestion = QuranQuestion::factory()->create();

        $user = User::factory()->create();
        $user->givePermissionTo('quranQuestions.update');

        $response = $this->actingAs($user)->json(
            'PUT',
            $this->uri . '/' . $quranQuestion->id,
            [
                'question' => 'test'
            ]
        );

        $response->assertOk();

        $this->assertDatabaseHas('quran_questions', ['id' => $quranQuestion->id, 'question' => 'test']);
    }

    /** @test */
    public function it_should_not_delete_quran_question_when_does_not_have_permission()
    {
        $quranQuestion = QuranQuestion::factory()->create();

        $user = User::factory()->create();

        $response = $this->actingAs($user)->json(
            'DELETE',
            $this->uri . '/' . $quranQuestion->id
        );

        $response->assertForbidden();
    }

    /** @test */
    public function it_should_delete_quran_question_when_does_have_permission()
    {
        $quranQuestion = QuranQuestion::factory()->create();

        $user = User::factory()->create();
        $user->givePermissionTo('quranQuestions.delete');

        $response = $this->actingAs($user)->json(
            'DELETE',
            $this->uri . '/' . $quranQuestion->id
        );

        $response->assertSuccessful();
    }

    /** @test */
    public function it_should_not_assign_quran_question_when_does_not_have_permission()
    {
        $quranQuestion = QuranQuestion::factory()->create();

        $user = User::factory()->create();
        $assignedUser = User::factory()->create();

        $response = $this->actingAs($user)->json(
            'POST',
            $this->uri . '-assign',
            [
                'user_id' => $assignedUser->id,
                'question_id' => $quranQuestion->id
            ]
        );

        $response->assertForbidden();
    }

    /** @test */
    public function it_should_assign_quran_question_when_does_have_permission()
    {
        $quranQuestion = QuranQuestion::factory()->create();

        $user = User::factory()->create();
        $user->givePermissionTo('quranQuestions.assign');
        $assignedUser = User::factory()->create();

        $response = $this->actingAs($user)->json(
            'POST',
            $this->uri . '-assign',
            [
                'user_id' => $assignedUser->id,
                'question_id' => $quranQuestion->id
            ]
        );

        $response->assertSuccessful();

        $this->assertDatabaseHas('answer_attempts', ['question_id' => $quranQuestion->id, 'user_id' => $assignedUser->id]);
    }
}
