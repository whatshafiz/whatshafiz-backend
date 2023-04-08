<?php

namespace Tests\Feature;

use App\Models\QuranQuestion;
use App\Models\User;
use Illuminate\Support\Str;
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
    public function it_should_get_quran_questions_list_when_has_permission()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('quranQuestions.list');

        $quranQuestions = QuranQuestion::factory()->count(rand(1, 10))->create();

        $response = $this->actingAs($user)->json('GET', $this->uri);

        $response->assertOk();

        foreach ($quranQuestions as $quranQuestion) {
            $quranQuestion['question'] = Str::limit($quranQuestion['question'], 33);
            $response->assertJsonFragment($quranQuestion->toArray());
        }
    }

    /** @test */
    public function it_should_filter_quran_questions_while_listing()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('quranQuestions.list');

        $quranQuestion = QuranQuestion::factory()->count(5)->create()->random();

        $searchQuery = [
            'page_number' => $quranQuestion->page_number,
            'question' => $quranQuestion->question,
            'option_1' => $quranQuestion->option_1,
            'option_2' => $quranQuestion->option_2,
            'option_3' => $quranQuestion->option_3,
            'option_4' => $quranQuestion->option_4,
            'option_5' => $quranQuestion->option_5,
            'correct_option' => $quranQuestion->correct_option,
        ];

        $response = $this->actingAs($user)->json('GET', $this->uri, $searchQuery);

        $response->assertOk();

        foreach (QuranQuestion::where($searchQuery)->get() as $question) {
            $question['question'] = Str::limit($question['question'], 33);
            $response->assertJsonFragment($question->toArray());
        }
    }

    /** @test */
    public function it_should_not_create_quran_question_when_does_not_have_permission()
    {
        $user = User::factory()->create();

        $quranQuestionData = QuranQuestion::factory()->raw();

        $response = $this->actingAs($user)->json('POST', $this->uri, $quranQuestionData);

        $response->assertForbidden();
    }

    /** @test */
    public function it_should_create_quran_question_when_has_permission()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('quranQuestions.create');

        $quranQuestionData = QuranQuestion::factory()->raw();

        $response = $this->actingAs($user)->json('POST', $this->uri, $quranQuestionData);

        $response->assertSuccessful();

        $this->assertDatabaseHas('quran_questions', $quranQuestionData);
    }

    /** @test */
    public function it_should_not_update_quran_question_when_does_not_have_permission()
    {
        $user = User::factory()->create();

        $quranQuestion = QuranQuestion::factory()->create();
        $quranQuestionNewData = QuranQuestion::factory()->raw();

        $response = $this->actingAs($user)->json('PUT', $this->uri . '/' . $quranQuestion->id, $quranQuestionNewData);

        $response->assertForbidden();
    }

    /** @test */
    public function it_should_update_quran_question_when_has_permission()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('quranQuestions.update');

        $quranQuestion = QuranQuestion::factory()->create();
        $quranQuestionNewData = QuranQuestion::factory()->raw();

        $response = $this->actingAs($user)->json('PUT', $this->uri . '/' . $quranQuestion->id, $quranQuestionNewData);

        $response->assertOk();

        $this->assertDatabaseHas('quran_questions', array_merge(['id' => $quranQuestion->id], $quranQuestionNewData));
    }

    /** @test */
    public function it_should_not_delete_quran_question_when_does_not_have_permission()
    {
        $user = User::factory()->create();

        $quranQuestion = QuranQuestion::factory()->create();

        $response = $this->actingAs($user)->json('DELETE', $this->uri . '/' . $quranQuestion->id);

        $response->assertForbidden();
    }

    /** @test */
    public function it_should_delete_quran_question_when_has_permission()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('quranQuestions.delete');

        $quranQuestion = QuranQuestion::factory()->create();

        $response = $this->actingAs($user)->json('DELETE', $this->uri . '/' . $quranQuestion->id);

        $response->assertSuccessful();

        $this->assertSoftDeleted('quran_questions', ['id' => $quranQuestion->id]);
    }
}
