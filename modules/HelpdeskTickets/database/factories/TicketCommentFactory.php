<?php

namespace Modules\HelpdeskTickets\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HelpdeskTickets\Models\TicketComment;

/**
 * NOTE: TicketComment::boot() enforces that either user_id OR author_id is set,
 * not both and not neither.  The default definition sets user_id; use the
 * fromCustomer() state to produce a customer-authored comment.
 */
class TicketCommentFactory extends Factory
{
    protected $model = TicketComment::class;

    public function definition(): array
    {
        $body = fake()->paragraph();

        return [
            'ticket_id' => null, // set via relationship
            'user_id' => 1,    // assumes at least one user exists; override as needed
            'author_id' => null,
            'body' => $body,
            'html_body' => '<p>'.$body.'</p>',
            'is_internal' => false,
            'attachment_urls' => null,
            'edited_by' => null,
            'edited_at' => null,
            'edit_reason' => null,
            'mentioned_user_ids' => null,
        ];
    }

    public function internal(): static
    {
        return $this->state(['is_internal' => true]);
    }

    public function fromCustomer(int $authorId): static
    {
        return $this->state([
            'user_id' => null,
            'author_id' => $authorId,
        ]);
    }
}
