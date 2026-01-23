<?php

namespace Tests\Feature\Helpdesk;

use App\Models\Helpdesk\Ticket;
use App\Models\Helpdesk\TicketComment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketCommentsControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Ticket $ticket;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->ticket = Ticket::factory()->create();
    }

    /**
     * Test viewing comments list for a ticket.
     */
    public function test_index_returns_ticket_comments(): void
    {
        TicketComment::factory()->create([
            'ticket_id' => $this->ticket->id,
            'user_id' => $this->user->id,
        ]);

        TicketComment::factory()->create([
            'ticket_id' => $this->ticket->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('manager.helpdesk.tickets.comments.index', $this->ticket));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'ticket_id', 'body', 'user_id', 'is_internal', 'created_at'],
                ],
            ]);
    }

    /**
     * Test creating a comment on a ticket.
     */
    public function test_store_creates_comment(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('manager.helpdesk.tickets.comments.store', $this->ticket), [
                'body' => 'Test comment',
                'is_internal' => false,
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['id', 'ticket_id', 'body', 'user_id']);

        $this->assertDatabaseHas('helpdesk_ticket_comments', [
            'ticket_id' => $this->ticket->id,
            'body' => 'Test comment',
            'is_internal' => false,
        ]);
    }

    /**
     * Test storing comment with HTML body.
     */
    public function test_store_with_html_body(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('manager.helpdesk.tickets.comments.store', $this->ticket), [
                'html_body' => '<p>Test <strong>comment</strong></p>',
                'is_internal' => false,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('html_body', '<p>Test <strong>comment</strong></p>');
    }

    /**
     * Test storing comment requires either body or html_body.
     */
    public function test_store_requires_body_or_html_body(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('manager.helpdesk.tickets.comments.store', $this->ticket), [
                'is_internal' => false,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('body');
    }

    /**
     * Test viewing a single comment.
     */
    public function test_show_returns_comment(): void
    {
        $comment = TicketComment::factory()->create([
            'ticket_id' => $this->ticket->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('manager.helpdesk.tickets.comments.show', [$this->ticket, $comment]));

        $response->assertStatus(200)
            ->assertJsonPath('id', $comment->id)
            ->assertJsonPath('body', $comment->body);
    }

    /**
     * Test updating a comment.
     */
    public function test_update_modifies_comment(): void
    {
        $comment = TicketComment::factory()->create([
            'ticket_id' => $this->ticket->id,
            'user_id' => $this->user->id,
            'body' => 'Original text',
        ]);

        $response = $this->actingAs($this->user)
            ->putJson(route('manager.helpdesk.tickets.comments.update', [$this->ticket, $comment]), [
                'body' => 'Updated text',
                'edit_reason' => 'Typo fix',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('body', 'Updated text');

        $this->assertDatabaseHas('helpdesk_ticket_comments', [
            'id' => $comment->id,
            'body' => 'Updated text',
            'edit_reason' => 'Typo fix',
        ]);
    }

    /**
     * Test deleting a comment (soft delete).
     */
    public function test_destroy_soft_deletes_comment(): void
    {
        $comment = TicketComment::factory()->create([
            'ticket_id' => $this->ticket->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson(route('manager.helpdesk.tickets.comments.destroy', [$this->ticket, $comment]));

        $response->assertStatus(200);

        $this->assertSoftDeleted($comment);
    }

    /**
     * Test restoring a deleted comment.
     */
    public function test_restore_restores_deleted_comment(): void
    {
        $comment = TicketComment::factory()->create([
            'ticket_id' => $this->ticket->id,
            'user_id' => $this->user->id,
        ]);

        $comment->delete();
        $this->assertSoftDeleted($comment);

        $response = $this->actingAs($this->user)
            ->postJson(route('manager.helpdesk.tickets.comments.restore', [$this->ticket, $comment]));

        $response->assertStatus(200);
        $this->assertNotSoftDeleted($comment);
    }

    /**
     * Test storing comment with attachments.
     */
    public function test_store_with_attachments(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('manager.helpdesk.tickets.comments.store', $this->ticket), [
                'body' => 'Comment with files',
                'attachment_urls' => [
                    'https://example.com/file1.pdf',
                    'https://example.com/file2.zip',
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('attachment_urls.0', 'https://example.com/file1.pdf');
    }

    /**
     * Test storing comment with mentioned users.
     */
    public function test_store_with_mentioned_users(): void
    {
        $mentionedUser = User::factory()->create();

        $response = $this->actingAs($this->user)
            ->postJson(route('manager.helpdesk.tickets.comments.store', $this->ticket), [
                'body' => 'Hello @'.$mentionedUser->id,
                'mentioned_user_ids' => [$mentionedUser->id],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('mentioned_user_ids.0', $mentionedUser->id);
    }
}
