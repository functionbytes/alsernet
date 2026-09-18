<?php

namespace Modules\HelpdeskTickets\Services\Compliance;

use Modules\Helpdesk\Contracts\GdprExportContributor;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskTickets\Models\Ticket;

/**
 * GDPR right-of-access section for HelpdeskTickets: every ticket of the
 * customer plus the message thread. Internal agent notes (is_internal) are
 * excluded — they are the company's data about its own staff workflow, not the
 * customer's personal data — but every customer-visible message is included.
 */
class TicketGdprExportContributor implements GdprExportContributor
{
    public function sectionKey(): string
    {
        return 'tickets';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function export(Customer $customer): array
    {
        return Ticket::query()
            ->withTrashed()
            ->where('customer_id', $customer->id)
            ->with([
                'items' => fn ($q) => $q->where('is_internal', false),
                'status:id,name',
            ])
            ->orderBy('created_at')
            ->get()
            ->map(fn (Ticket $ticket): array => [
                'id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
                'subject' => $ticket->subject,
                'description' => $ticket->description,
                'status' => $ticket->status?->name,
                'priority' => $ticket->priority,
                'source' => $ticket->source,
                'custom_fields' => $ticket->custom_fields,
                'rating' => $ticket->rating,
                'rating_comment' => $ticket->rating_comment,
                'created_at' => $ticket->created_at?->toIso8601String(),
                'closed_at' => $ticket->closed_at?->toIso8601String(),
                'messages' => $ticket->items->map(fn ($item): array => [
                    'id' => $item->id,
                    'type' => $item->type,
                    'body' => $item->body,
                    'from_customer' => $item->author_id !== null && $item->user_id === null,
                    'attachments' => $item->attachment_urls ?? [],
                    'created_at' => $item->created_at?->toIso8601String(),
                ])->values()->all(),
            ])
            ->values()
            ->all();
    }
}
