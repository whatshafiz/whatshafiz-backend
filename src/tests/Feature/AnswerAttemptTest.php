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
        $this->myUri = self::BASE_URI . '/my-answer-attempts';
    }

    /** @test */
    public function it_should_not_get_answer_attempts_list_when_does_not_have_permission()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->json('GET', $this->uri);

        $response->assertForbidden();
    }

    /** @test */
    public function it_should_get_answer_attempts_list_when_has_permission()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('answerAttempts.list');

        $answerAttempts = AnswerAttempt::factory()->count(rand(1, 10))->create();

        $response = $this->actingAs($user)->json('GET', $this->uri);

        $response->assertOk();

        foreach ($answerAttempts as $answerAttempt) {
            $response->assertJsonFragment($answerAttempt->toArray());
        }
    }

    /** @test */
    public function it_should_filter_answer_attempts_list_when_has_permission()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('answerAttempts.list');

        $answerAttempt = AnswerAttempt::factory()->count(5)->create()->random();

        $searchQuery = [
            'user_id' => $answerAttempt->user_id,
            'quran_question_id' => $answerAttempt->quran_question_id,
            'selected_option_number' => $answerAttempt->selected_option_number,
            'is_correct_option' => $answerAttempt->is_correct_option,
        ];

        $response = $this->actingAs($user)->json('GET', $this->uri, $searchQuery);

        $response->assertOk();

        foreach (AnswerAttempt::where($searchQuery)->get() as $answerAttempt) {
            $response->assertJsonFragment($answerAttempt->toArray());
        }
    }

    /** @test */
    public function it_should_not_delete_answer_attempt_when_does_not_have_permission()
    {
        $user = User::factory()->create();

        $answerAttempt = AnswerAttempt::factory()->create();

        $response = $this->actingAs($user)->json('DELETE', $this->uri . '/' . $answerAttempt->id);

        $response->assertForbidden();
    }

    /** @test */
    public function it_should_delete_answer_attempt_when_does_have_permission()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('answerAttempts.delete');

        $answerAttempt = AnswerAttempt::factory()->create();

        $response = $this->actingAs($user)->json('DELETE', $this->uri . '/' . $answerAttempt->id);

        $response->assertSuccessful();

        $this->assertSoftDeleted('answer_attempts', ['id' => $answerAttempt->id]);
    }

    /** @test */
    public function it_should_return_users_answer_attempts_list()
    {
        $user = User::factory()->create();

        $answerAttempts = AnswerAttempt::factory()->count(rand(1, 7))->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->json('GET', $this->myUri);

        $response->assertOk();

        foreach ($answerAttempts as $answerAttempt) {
            $response->assertJsonFragment($answerAttempt->toArray());
        }
    }

    /** @test */
    public function it_should_return_users_answer_attempts_with_filters()
    {
        $user = User::factory()->create();

        $answerAttempt = AnswerAttempt::factory()->count(rand(1, 5))->create(['user_id' => $user->id])->random();

        $searchQuery = [
            'quran_question_id' => $answerAttempt->quran_question_id,
            'is_correct_option' => $answerAttempt->is_correct_option,
        ];

        $response = $this->actingAs($user)->json('GET', $this->myUri, $searchQuery);

        $response->assertOk();

        $searchQuery['user_id'] = $user->id;

        foreach (AnswerAttempt::where($searchQuery)->get() as $searchAnswerAttempt) {
            $response->assertJsonFragment($searchAnswerAttempt->toArray());
        }
    }
}
