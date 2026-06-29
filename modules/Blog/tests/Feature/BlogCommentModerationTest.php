<?php

namespace Modules\Blog\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Blog\Enums\CommentStatus;
use Modules\Blog\Models\BlogPost;
use Modules\Blog\Models\BlogPostComment;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class BlogCommentModerationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Permission::firstOrCreate(
            ['name' => 'blog.comment.moderate', 'guard_name' => 'web']
        );
    }

    private function createAuthenticatedUser(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo('blog.comment.moderate');

        return $user;
    }

    private function createPendingComment(): BlogPostComment
    {
        $post = BlogPost::factory()->published()->create();

        return BlogPostComment::factory()
            ->for($post, 'post')
            ->pending()
            ->create();
    }

    // ========== AUTHENTICATION ==========

    public function test_unauthenticated_user_cannot_approve_comment(): void
    {
        $comment = $this->createPendingComment();

        $response = $this->postJson(route('blog.comments.approve', $comment));

        $response->assertUnauthorized();
    }

    public function test_unauthenticated_user_cannot_mark_comment_as_spam(): void
    {
        $comment = $this->createPendingComment();

        $response = $this->postJson(route('blog.comments.spam', $comment));

        $response->assertUnauthorized();
    }

    public function test_unauthenticated_user_cannot_delete_comment(): void
    {
        $comment = $this->createPendingComment();

        $response = $this->delete(route('blog.comments.destroy', $comment));

        $response->assertRedirect(route('auth.login'));
    }

    // ========== APPROVE ==========

    public function test_authenticated_user_can_approve_pending_comment(): void
    {
        $user = $this->createAuthenticatedUser();
        $comment = $this->createPendingComment();

        $response = $this->actingAs($user)
            ->postJson(route('blog.comments.approve', $comment));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('status', CommentStatus::Approved->value);

        $this->assertDatabaseHas('blog_post_comments', [
            'id' => $comment->id,
            'status' => CommentStatus::Approved->value,
        ]);
    }

    // ========== SPAM ==========

    public function test_authenticated_user_can_mark_comment_as_spam(): void
    {
        $user = $this->createAuthenticatedUser();
        $comment = $this->createPendingComment();

        $response = $this->actingAs($user)
            ->postJson(route('blog.comments.spam', $comment));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('status', CommentStatus::Spam->value);

        $this->assertDatabaseHas('blog_post_comments', [
            'id' => $comment->id,
            'status' => CommentStatus::Spam->value,
        ]);
    }

    // ========== DELETE ==========

    public function test_authenticated_user_can_delete_comment(): void
    {
        $user = $this->createAuthenticatedUser();
        $comment = $this->createPendingComment();

        $response = $this->actingAs($user)
            ->delete(route('blog.comments.destroy', $comment));

        $response->assertRedirect();

        $this->assertSoftDeleted('blog_post_comments', ['id' => $comment->id]);
    }

    // ========== ADMIN COMMENT INDEX ==========

    public function test_authenticated_user_can_list_all_comments(): void
    {
        $user = $this->createAuthenticatedUser();
        BlogPostComment::factory()
            ->for(BlogPost::factory()->published()->create(), 'post')
            ->count(3)
            ->create();

        $response = $this->actingAs($user)->get(route('blog.comments.index'));

        $response->assertOk();
    }

    public function test_unauthenticated_user_cannot_access_comment_index(): void
    {
        $response = $this->get(route('blog.comments.index'));

        $response->assertRedirect(route('auth.login'));
    }
}
