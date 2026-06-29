<?php

namespace Modules\HelpdeskSocial\Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\HelpdeskSocial\Models\SocialComment;
use Modules\HelpdeskSocial\Models\SocialCommentNote;
use Modules\HelpdeskSocial\Tests\TestCase;

class SocialNotesControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedPermissions();

        $this->user = User::factory()->create();
        $this->user->givePermissionTo([
            'helpdesksocial.view',
            'helpdesksocial.manage',
        ]);
    }

    private function seedPermissions(): void {}

    public function test_index_requires_auth(): void
    {
        $comment = SocialComment::factory()->create();

        $response = $this->getJson("/api/helpdesk/social/comments/{$comment->id}/notes");

        $response->assertUnauthorized();
    }

    public function test_index_returns_data(): void
    {
        $comment = SocialComment::factory()->create();
        SocialCommentNote::factory()->count(3)->create(['social_comment_id' => $comment->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/helpdesk/social/comments/{$comment->id}/notes");

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.per_page', 20);
    }

    public function test_index_forbidden_without_permission(): void
    {
        $otherUser = User::factory()->create();
        $this->actingAs($otherUser, 'sanctum');

        $comment = SocialComment::factory()->create();

        $response = $this->getJson("/api/helpdesk/social/comments/{$comment->id}/notes");

        $response->assertForbidden();
    }

    public function test_store_creates_resource(): void
    {
        $comment = SocialComment::factory()->create();

        $payload = [
            'social_comment_id' => $comment->id,
            'body' => 'This is a note',
            'type' => 'internal',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/helpdesk/social/comments/{$comment->id}/notes", $payload);

        $response->assertCreated()
            ->assertJsonPath('data.body', 'This is a note');

        $this->assertDatabaseHas('helpdesk_social_comment_notes', [
            'social_comment_id' => $comment->id,
            'body' => 'This is a note',
            'user_id' => $this->user->id,
        ]);
    }

    public function test_store_validation_fails(): void
    {
        $comment = SocialComment::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/helpdesk/social/comments/{$comment->id}/notes", []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['body', 'social_comment_id']);
    }

    public function test_store_forbidden_without_manage_permission(): void
    {
        $viewer = User::factory()->create();
        $viewer->givePermissionTo(['helpdesksocial.view']);

        $comment = SocialComment::factory()->create();

        $response = $this->actingAs($viewer, 'sanctum')
            ->postJson("/api/helpdesk/social/comments/{$comment->id}/notes", [
                'social_comment_id' => $comment->id,
                'body' => 'Test note',
            ]);

        $response->assertForbidden();
    }

    public function test_destroy_deletes_resource(): void
    {
        $note = SocialCommentNote::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/helpdesk/social/notes/{$note->id}");

        $response->assertOk()
            ->assertJsonPath('message', 'Nota eliminada correctamente');

        $this->assertDatabaseMissing('helpdesk_social_comment_notes', ['id' => $note->id]);
    }
}
