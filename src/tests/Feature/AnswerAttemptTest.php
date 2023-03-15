<?php

namespace Tests\Feature;

use App\Models\AnswerAttempt;
use App\Models\User;
use Tests\BaseFeatureTest;

class AnswerAttemptTest extends BaseFeatureTest
{
    protected string $uri;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->uri = self::BASE_URI . '/answer-attempts';
    }

    /** @test */
    public function it_should_not_get_answer_attempts_list_when_does_not_have_permission()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->json('GET', $this->uri);

        $response->assertForbidden();
    }

    /** @test */
    public function it_should_get_answer_attempts_list_when_does_have_permission()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('answerattempts.list');

        $response = $this->actingAs($user)->json('GET', $this->uri);

        $response->assertOk();
    }

    /** @test */
    public function answer_attempts_filters_should_work()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('answerattempts.list');

        $answerAttempt = AnswerAttempt::factory()->count(5)->create();

        $searchQuery = [
            'user_id' => $answerAttempt->first()->user_id,
            'question_id' => $answerAttempt->first()->question_id,
            'answer' => $answerAttempt->first()->answer,
            'is_correct' => $answerAttempt->first()->is_correct,
        ];

        $response = $this->actingAs($user)->json(
            'GET',
            $this->uri,
            $searchQuery
        );

        $response->assertOk();

        foreach (AnswerAttempt::where($searchQuery)->get() as $answerAttempt) {
            $response->assertJsonFragment($answerAttempt->toArray());
        }
    }

    /** @test */
    public function it_should_not_update_answer_attempt_when_does_not_have_permission()
    {
        $user = User::factory()->create();

        $answerAttempt = AnswerAttempt::factory()->create();

        $response = $this->actingAs($user)->json(
            'PUT',
            $this->uri . '/' . $answerAttempt->id,
            $answerAttempt->toArray()
        );

        $response->assertForbidden();
    }

    /** @test */
    public function it_should_update_answer_attempt_when_does_have_permission()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('answerattempts.update');

        $answerAttempt = AnswerAttempt::factory()->create();

        $response = $this->actingAs($user)->json(
            'PUT',
            $this->uri . '/' . $answerAttempt->id,
            [
                'answer' => $answerAttempt->question->correct_option,
            ]
        );

        $response->assertOk();

        $this->assertDatabaseHas('answer_attempts', [
            'id' => $answerAttempt->id,
            'answer' => $answerAttempt->question->correct_option,
            'is_correct' => true,
        ]);
    }

    /** @test */
    public function update_answer_with_wrong_option()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('answerattempts.update');

        $answerAttempt = AnswerAttempt::factory()->create();

        $response = $this->actingAs($user)->json(
            'PUT',
            $this->uri . '/' . $answerAttempt->id,
            [
                'answer' => $answerAttempt->question->correct_option + 1,
            ]
        );

        $response->assertOk();

        $this->assertDatabaseHas('answer_attempts', [
            'id' => $answerAttempt->id,
            'answer' => $answerAttempt->question->correct_option + 1,
            'is_correct' => false,
        ]);
    }

    /** @test */
    public function it_should_not_delete_answer_attempt_when_does_not_have_permission()
    {
        $user = User::factory()->create();

        $answerAttempt = AnswerAttempt::factory()->create();

        $response = $this->actingAs($user)->json(
            'DELETE',
            $this->uri . '/' . $answerAttempt->id
        );

        $response->assertForbidden();
    }

    /** @test */
    public function it_should_delete_answer_attempt_when_does_have_permission()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('answerattempts.delete');

        $answerAttempt = AnswerAttempt::factory()->create();

        $response = $this->actingAs($user)->json(
            'DELETE',
            $this->uri . '/' . $answerAttempt->id
        );

        $response->assertSuccessful();

        $this->assertDatabaseMissing('answer_attempts', [
            'id' => $answerAttempt->id,
        ]);
    }

    /** @test */
    public function it_should_return_users_answer_attempts()
    {
        $user = User::factory()->create();

        $answerAttempts = AnswerAttempt::factory()->count(5)->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->json(
            'GET',
            $this->uri . '-my'
        );

        $response->assertOk();

        foreach ($answerAttempts as $answerAttempt) {
            $response->assertJsonFragment($answerAttempt->toArray());
        }
    }

    /** @test */
    public function it_should_return_users_answer_attempts_with_filters()
    {
        $user = User::factory()->create();

        $answerAttempts = AnswerAttempt::factory()
            ->count(5)
            ->sequence(fn ($sequence) => ['is_correct' => rand(0, 1)])
            ->create([
            'user_id' => $user->id,
        ]);

        $searchQuery = [
            'question_id' => $answerAttempts->first()->question_id,
            'is_correct' => $answerAttempts->first()->is_correct,
        ];

        $response = $this->actingAs($user)->json(
            'GET',
            $this->uri . '-my',
            $searchQuery
        );

        $response->assertOk();

        foreach (AnswerAttempt::where($searchQuery)->get() as $answerAttempt) {
            $response->assertJsonFragment($answerAttempt->toArray());
        }
    }

    /** @test */
    public function it_should_return_user_active_answer_attempts()
    {
        $user = User::factory()->create();

        $answerAttempts = AnswerAttempt::factory()->count(5)->create([
            'user_id' => $user->id,
            'is_correct' => 0,
        ]);

        $activeAnswerAttempt = AnswerAttempt::factory()->create([
            'user_id' => $user->id,
            'answer' => null,
            'is_correct' => null,
        ]);

        $response = $this->actingAs($user)->json(
            'GET',
            $this->uri . '-my-active'
        );

        $response->assertOk();
        $response->assertJsonFragment($activeAnswerAttempt->toArray());
    }
}
