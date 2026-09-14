<?php

namespace Modules\HelpdeskAgents\Services;

use Modules\HelpdeskTickets\Models\Ticket;

/**
 * Builds the (cost-bounded) plain-text context sent to the LLM for
 * ticket-level AI features. Only the most recent N non-internal messages are
 * included and the final payload is hard-capped in bytes so a long thread can
 * never blow up token spend.
 */
class TicketAiContextBuilder
{
    public function __construct(private readonly PromptSanitizer $sanitizer) {}

    public function build(Ticket $ticket): string
    {
        $maxItems = (int) config('helpdeskagents.ticket_ai.context_max_items', 10);
        $maxBytes = (int) config('helpdeskagents.ticket_ai.context_max_bytes', 8192);
        $maxItemChars = (int) config('helpdeskagents.ticket_ai.context_max_item_chars', 1500);

        $items = $ticket->items()
            ->where('is_internal', false)
            ->latest('created_at')
            ->limit($maxItems)
            ->get()
            ->reverse();

        $thread = $items->map(function ($item) use ($maxItemChars) {
            $role = $item->user_id ? 'Agente' : 'Cliente';
            $body = trim(strip_tags($item->body ?? ''));

            if ($role === 'Cliente') {
                $body = $this->sanitizer->sanitize($body);
            }

            return "[{$role}]: ".mb_substr($body, 0, $maxItemChars);
        })->implode("\n");

        if ($thread === '') {
            $thread = '[Cliente]: '.$this->sanitizer->sanitize(
                mb_substr(trim(strip_tags($ticket->description ?? '')), 0, $maxItemChars)
            );
        }

        $header = implode("\n", array_filter([
            'Ticket #'.($ticket->ticket_number ?? $ticket->id),
            'Asunto: '.$this->sanitizer->sanitize((string) $ticket->subject),
            'Prioridad actual: '.($ticket->priority ?? 'normal'),
            $ticket->category?->name ? 'Categoría: '.$ticket->category->name : null,
        ]));

        $context = $header."\n\nMensajes:\n".$thread;

        // Hard byte cap, keeping the most recent content (the tail).
        if (strlen($context) > $maxBytes) {
            $context = mb_strcut($context, strlen($context) - $maxBytes, $maxBytes);
        }

        return $context;
    }
}
