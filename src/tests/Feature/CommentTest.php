<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\CourseType;
use App\Models\User;
use Carbon\Carbon;
use Tests\BaseFeatureTest;

class CommentTest extends BaseFeatureTest
{
    protected string $uri;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->uri = self::BASE_URI . '/comments';
    }

    /** @test */
    public function user_can_not_list_comments_when_has_not_permission()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->json('GET', $this->uri);

        $response->assertForbidden();
    }

    /** @test */
    public function user_can_list_comments_when_has_permission()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('comments.list');

        $comments = Comment::factory()->count(5)->create();

        $response = $this->actingAs($user)->json('GET', $this->uri);

        $response->assertOk();

        foreach ($comments as $comment) {
            $response->assertJsonFragment($comment->load('commentedBy', 'approvedBy')->toArray());
        }
    }

    /** @test */
    public function user_can_list_approved_comments_as_paginated()
    {
        $courseType = CourseType::inRandomOrder()->first();
        $comments = Comment::factory()->approved()->count(5)->create(['course_type_id' => $courseType->id]);

        $response = $this->json('GET', $this->uri . '/approved/' . $courseType->slug);

        $response->assertOk();

        foreach ($comments as $comment) {
            $response->assertJsonFragment($comment->only(['id', 'title', 'comment']));
        }
    }

    /** @test */
    public function user_can_list_own_comments_as_paginated()
    {
        $user = User::factory()->create();

        $comments = Comment::factory()->count(5)->create(['commented_by_id' => $user->id]);

        $response = $this->actingAs($user)->json('GET', self::BASE_URI . '/my/comments');

        $response->assertOk();

        foreach ($comments as $comment) {
            $response->assertJsonFragment($comment->only(['id', 'title', 'comment']));
        }
    }

    /** @test */
    public function user_can_filter_comments_while_listing_and_has_permission()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('comments.list');

        $comments = Comment::factory()->count(5)->create();
        $searchComment = $comments->random();
        $searchQuery = [
            'course_type_id' => $searchComment->course_type_id,
            'commented_by_id' => $searchComment->commented_by_id,
            'approved_by_id' => $searchComment->approved_by_id ?? $user->id,
            'is_approved' => $searchComment->is_approved,
            'filter' => [['value' => $searchComment->title]],
        ];

        $response = $this->actingAs($user)->json('GET', $this->uri, $searchQuery);

        $response->assertOk();

        $searchQuery['title'] = $searchQuery['filter'][0]['value'];
        unset($searchQuery['filter']);

        foreach (Comment::where($searchQuery)->get() as $comment) {
            $response->assertJsonFragment($comment->toArray());
        }
    }

    /** @test */
    public function user_can_create_comment()
    {
        $commentData = Comment::factory()->make()->only('course_type_id', 'title', 'comment');

        $user = User::factory()->create();

        $response = $this->actingAs($user)->json('POST', $this->uri, $commentData);

        $commentData['commented_by_id'] = $user->id;

        $response->assertOk()
            ->assertJsonFragment($commentData);

        $this->assertDatabaseHas('comments', $commentData);
    }

    /** @test */
    public function user_can_not_view_comment_when_has_not_permission()
    {
        $comment = Comment::factory()->create();

        $user = User::factory()->create();

        $response = $this->actingAs($user)->json('GET', $this->uri . '/' . $comment->id);

        $response->assertForbidden();
    }

    /** @test */
    public function user_can_view_comment_when_user_has_permission()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('comments.list');

        $comment = Comment::factory()->create();

        $response = $this->actingAs($user)->json('GET', $this->uri . '/' . $comment->id);

        $response->assertOk()
            ->assertJsonFragment($comment->toArray());
    }

    /** @test */
    public function user_can_view_own_comment_even_does_not_have_permission()
    {
        $user = User::factory()->create();
        $comment = Comment::factory()->create(['commented_by_id' => $user->id]);

        $response = $this->actingAs($user)->json('GET', $this->uri . '/' . $comment->id);

        $response->assertOk()
            ->assertJsonFragment($comment->toArray());
    }

    /** @test */
    public function user_can_edit_comment_if_has_permission()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('comments.update');

        $comment = Comment::factory()->create();
        $newCommentData = Comment::factory()->make()->only('course_type_id', 'title', 'comment');

        $response = $this->actingAs($user)->json('PUT', $this->uri . '/' . $comment->id, $newCommentData);

        $response->assertOk()
            ->assertJsonFragment($newCommentData);

        $this->assertDatabaseHas('comments', array_merge(['id' => $comment->id], $newCommentData));
    }

    /** @test */
    public function user_can_edit_own_comment_even_does_not_have_permission()
    {
        $user = User::factory()->create();

        $comment = Comment::factory()->create(['commented_by_id' => $user->id, 'is_approved' => false]);
        $newCommentData = Comment::factory()
            ->make(['course_type_id' => $comment->course_type_id])
            ->only('course_type_id', 'title', 'comment');

        $response = $this->actingAs($user)->json('PUT', $this->uri . '/' . $comment->id, $newCommentData);

        $response->assertOk()
            ->assertJsonFragment($newCommentData);

        $this->assertDatabaseHas('comments', array_merge(['id' => $comment->id], $newCommentData));
    }

    /** @test */
    public function user_can_not_approve_any_comment_if_user_has_not_permission()
    {
        $user = User::factory()->create();

        $comment = Comment::factory()->create(['is_approved' => false]);

        $response = $this->actingAs($user)->json('PUT', $this->uri . '/' . $comment->id, ['is_approved' => true]);

        $response->assertForbidden();
    }

    /** @test */
    public function user_can_approve_comment_if_user_has_permission()
    {
        $now = Carbon::now();
        Carbon::setTestNow($now);
        $user = User::factory()->create();
        $user->givePermissionTo('comments.update');

        $comment = Comment::factory()->create(['is_approved' => false]);

        $response = $this->actingAs($user)
            ->json(
                'PUT',
                $this->uri . '/' . $comment->id,
                array_merge($comment->only('course_type_id', 'title', 'comment'), ['is_approved' => true])
            );

        $response->assertOk();

        $this->assertDatabaseHas(
            'comments',
            ['id' => $comment->id, 'is_approved' => true, 'approved_by_id' => $user->id, 'approved_at' => $now]
        );
    }

    /** @test */
    public function user_can_not_delete_comment_if_user_has_not_permission()
    {
        $user = User::factory()->create();

        $comment = Comment::factory()->create();

        $response = $this->actingAs($user)->json('DELETE', $this->uri . '/' . $comment->id);

        $response->assertForbidden();
    }

    /** @test */
    public function user_can_delete_comment_if_has_permission()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('comments.delete');

        $comment = Comment::factory()->create();

        $response = $this->actingAs($user)->json('DELETE', $this->uri . '/' . $comment->id);

        $response->assertSuccessful();

        $this->assertSoftDeleted('comments', ['id' => $comment->id]);
    }

    /** @test */
    public function user_can_delete_own_comment_even_does_not_have_permission()
    {
        $user = User::factory()->create();
        $comment = Comment::factory()->create(['commented_by_id' => $user->id]);

        $response = $this->actingAs($user)->json('DELETE', $this->uri . '/' . $comment->id);

        $response->assertSuccessful();

        $this->assertSoftDeleted('comments', ['id' => $comment->id]);
    }
}
