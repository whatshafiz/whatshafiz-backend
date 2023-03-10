<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\User;
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
    public function user_can_not_list_comments_when_user_have_not_permission()
    {
        $comments = Comment::factory()->count(5)->create();
        $user = User::factory()->create();

        $response = $this->actingAs($user)->json('GET', $this->uri);

        $response->assertForbidden();
    }

    /** @test */
    public function user_can_list_comments_when_user_have_permission()
    {
        $comments = Comment::factory()->count(5)->create();
        $user = User::factory()->create();
        $user->givePermissionTo('comments.list');

        $response = $this->actingAs($user)->json('GET', $this->uri);

        $response->assertOk();

        foreach ($comments as $comment) {
            $response->assertJsonFragment($comment->toArray());
        }
    }

    /** @test */
    public function comment_filter_test()
    {
        $comments = Comment::factory()->count(5)->create();
        $user = User::factory()->create();
        $user->givePermissionTo('comments.list');
        $searchComment = $comments->random();
        $searchQuery = [
            'name' => $searchComment->name,
            'comment' => $searchComment->comment,
            'user_id' => $searchComment->user_id,
            'is_approved' => $searchComment->is_approved,
            'approved_by' => $searchComment->approved_by,
        ];

        $response = $this->actingAs($user)
            ->json(
                'GET',
                $this->uri,
                $searchQuery
            );

        $response->assertOk();
        $searchResultComments = $comments->where($searchQuery);

        foreach ($searchResultComments as $comment) {
            $response->assertJsonFragment($comment->toArray());
        }
    }

    /** @test */
    public function user_can_not_view_comment_when_user_have_not_permission()
    {
        $comment = Comment::factory()->create();

        $user = User::factory()->create();

        $response = $this->actingAs($user)->json('GET', $this->uri . '/' . $comment->id);

        $response->assertForbidden();
    }

    /** @test */
    public function user_can_view_comment_when_user_have_permission()
    {
        $comment = Comment::factory()->create();
        $user = User::factory()->create();
        $user->givePermissionTo('comments.view');

        $response = $this->actingAs($user)->json('GET', $this->uri . '/' . $comment->id);

        $response->assertOk();
        $response->assertJsonFragment($comment->toArray());
    }

    /** @test */
    public function user_can_view_comment_if_owner()
    {
        $comment = Comment::factory()->create();
        $user = User::factory()->create();
        $comment->user_id = $user->id;
        $comment->save();

        $response = $this->actingAs($user)->json('GET', $this->uri . '/' . $comment->id);

        $response->assertOk();
        $response->assertJsonFragment($comment->toArray());
    }

    /** @test */
    public function user_can_edit_if_has_permission()
    {
        $comment = Comment::factory()->create();
        $user = User::factory()->create();
        $user->givePermissionTo('comments.update');

        $response = $this->actingAs($user)->json('PUT', $this->uri . '/' . $comment->id, [
            'name' => 'New name',
            'comment' => 'New comment',
        ]);

        $response->assertOk();
        $response->assertJsonFragment([
            'name' => 'New name',
            'comment' => 'New comment',
        ]);
    }

    /** @test */
    public function user_can_edit_if_user_is_owner()
    {
        $comment = Comment::factory()->create();

        $response = $this->actingAs($comment->user)->json('PUT', $this->uri . '/' . $comment->id, [
            'name' => 'New name',
            'comment' => 'New comment',
        ]);

        $response->assertOk();
        $response->assertJsonFragment([
            'name' => 'New name',
            'comment' => 'New comment',
        ]);
    }

    /** @test */
    public function user_can_not_approve_if_user_has_not_permission()
    {
        $comment = Comment::factory()->create();
        $user = User::factory()->create();
        $comment->user_id = $user->id;
        $comment->save();
        $newStatus = !$comment->is_approved;

        $response = $this->actingAs($user)->json('PUT', $this->uri . '/' . $comment->id, [
            'is_approved' => $newStatus,
        ]);

        $response->assertOk();
        $response->assertJsonFragment([
            'is_approved' => $comment->is_approved,
        ]);
    }

    /** @test */
    public function user_can_approve_if_user_has_permission()
    {
        $comment = Comment::factory()->create();
        $user = User::factory()->create();
        $user->givePermissionTo('comments.update');
        $newStatus = !$comment->is_approved;

        $response = $this->actingAs($user)->json('PUT', $this->uri . '/' . $comment->id, [
            'is_approved' => $newStatus,
        ]);

        $response->assertOk();
        $response->assertJsonFragment([
            'is_approved' => $newStatus,
        ]);
    }

    /** @test */
    public function user_can_delete_comment_if_has_permission()
    {
        $comment = Comment::factory()->create();
        $user = User::factory()->create();
        $user->givePermissionTo('comments.delete');

        $response = $this->actingAs($user)->json('DELETE', $this->uri . '/' . $comment->id);

        $response->assertSuccessful();
    }

    /** @test */
    public function user_can_not_delete_if_not_owner()
    {
        $comment = Comment::factory()->create();
        $user = User::factory()->create();

        $response = $this->actingAs($user)->json('DELETE', $this->uri . '/' . $comment->id);

        $response->assertForbidden();
    }

    /** @test */
    public function owner_can_delete_comment()
    {
        $comment = Comment::factory()->create();
        $user = User::factory()->create();
        $comment->user_id = $user->id;
        $comment->save();

        $response = $this->actingAs($user)->json('DELETE', $this->uri . '/' . $comment->id);

        $response->assertSuccessful();
    }
}
