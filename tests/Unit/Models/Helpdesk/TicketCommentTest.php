<?php

namespace Tests\Unit\Models\Helpdesk;

use App\Models\Helpdesk\Ticket;
use App\Models\Helpdesk\TicketComment;
use App\Models\User;
use Tests\TestCase;

class TicketCommentTest extends TestCase
{
    /**
     * Test that a comment requires either user_id or author_id.
     */
    public function test_comment_requires_either_user_or_author(): void
    {
        $ticket = Ticket::factory()->create();

        // Should fail: no user_id or author_id
        $this->expectException(\Illuminate\Database\QueryException::class);
        TicketComment::create([
            'ticket_id' => $ticket->id,
            'body' => 'Test comment',
        ]);
    }

    /**
     * Test isFromAgent() method.
     */
    public function test_is_from_agent(): void
    {
        $ticket = Ticket::factory()->create();
        $user = User::factory()->create();

        $comment = TicketComment::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'body' => 'Agent comment',
        ]);

        $this->assertTrue($comment->isFromAgent());
        $this->assertFalse($comment->isFromCustomer());
    }

    /**
     * Test isFromCustomer() method.
     */
    public function test_is_from_customer(): void
    {
        $ticket = Ticket::factory()->create();

        $comment = TicketComment::create([
            'ticket_id' => $ticket->id,
            'author_id' => $ticket->customer_id,
            'body' => 'Customer comment',
        ]);

        $this->assertTrue($comment->isFromCustomer());
        $this->assertFalse($comment->isFromAgent());
    }

    /**
     * Test isInternal() method.
     */
    public function test_is_internal(): void
    {
        $ticket = Ticket::factory()->create();
        $user = User::factory()->create();

        $internalComment = TicketComment::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'body' => 'Internal note',
            'is_internal' => true,
        ]);

        $externalComment = TicketComment::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'body' => 'External reply',
            'is_internal' => false,
        ]);

        $this->assertTrue($internalComment->isInternal());
        $this->assertTrue($externalComment->isExternal());
    }

    /**
     * Test hasAttachments() method.
     */
    public function test_has_attachments(): void
    {
        $ticket = Ticket::factory()->create();
        $user = User::factory()->create();

        $commentWithAttachments = TicketComment::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'body' => 'Comment with files',
            'attachment_urls' => ['https://example.com/file.pdf'],
        ]);

        $commentWithoutAttachments = TicketComment::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'body' => 'Comment without files',
        ]);

        $this->assertTrue($commentWithAttachments->hasAttachments());
        $this->assertFalse($commentWithoutAttachments->hasAttachments());
    }

    /**
     * Test hasBeenEdited() method.
     */
    public function test_has_been_edited(): void
    {
        $ticket = Ticket::factory()->create();
        $user = User::factory()->create();

        $comment = TicketComment::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'body' => 'Original text',
        ]);

        $this->assertFalse($comment->hasBeenEdited());

        $comment->markAsEdited();

        $this->assertTrue($comment->hasBeenEdited());
    }

    /**
     * Test mentioned_user_ids JSON array.
     */
    public function test_mentioned_user_ids(): void
    {
        $ticket = Ticket::factory()->create();
        $user = User::factory()->create();
        $mentionedUser = User::factory()->create();

        $comment = TicketComment::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'body' => 'Comment mentioning @'.$mentionedUser->id,
            'mentioned_user_ids' => [$mentionedUser->id],
        ]);

        $this->assertIsArray($comment->mentioned_user_ids);
        $this->assertContains($mentionedUser->id, $comment->mentioned_user_ids);
    }
}
