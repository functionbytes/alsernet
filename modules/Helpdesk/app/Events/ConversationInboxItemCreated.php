<?php

namespace Modules\Helpdesk\Events;

use App\Events\Concerns\BroadcastsOnServedQueue;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Modules\Helpdesk\Models\ConversationItem;

/**
 * SEC-01: companion LIGHTWEIGHT event for the shared inbox sidebar.
 *
 * Before this fix, the sidebar listened on a single global 'helpdesk.inbox'
 * channel that received the FULL payload of ConversationMessageCreated
 * (body, html_body, attachment_urls, metadata — including internal notes),
 * gated only by the coarse 'helpdesk.conversations.view' permission. Any
 * agent with that permission could read every customer conversation across
 * every inbox in real time, plus internal-only notes never meant to leave
 * the authorized per-conversation channel.
 *
 * This event carries only what the sidebar list actually renders (id, a
 * truncated non-internal preview, light counters) and broadcasts on a
 * per-inbox channel authorized against AgentInboxCapacity — see
 * routes/channels.php ('helpdesk.inbox.{inboxId}'). The full payload keeps
 * going out ONLY on ConversationMessageCreated's per-conversation channel,
 * authorized via ConversationPolicy::view.
 *
 * Dispatched automatically by the BroadcastInboxSummary listener whenever
 * ConversationMessageCreated fires (see Helpdesk's EventServiceProvider) —
 * no need to touch the ~15 call sites that dispatch that event.
 */
class ConversationInboxItemCreated implements ShouldBroadcastNow
{
    use BroadcastsOnServedQueue, Dispatchable, InteractsWithSockets, SerializesModels;

    private const PREVIEW_LENGTH = 140;

    public function __construct(
        public ConversationItem $item,
        public bool $isNewConversation = false,
    ) {}

    /**
     * Never broadcast internal notes to the shared inbox channel — they are
     * only for the per-conversation thread, which real agents with inbox
     * access already see through appendActivityPillToThread/appendBubbleToThread.
     */
    public function broadcastOn(): array
    {
        if ($this->item->is_internal) {
            return [];
        }

        $this->item->loadMissing('conversation.assignee');
        $conversation = $this->item->conversation;
        $inboxId = $conversation?->inbox_id;

        if (! $inboxId) {
            return [];
        }

        $channels = [
            new PrivateChannel('helpdesk.inbox.'.$inboxId),
        ];

        // 'helpdesk-agent-restricted' (perfiles, 21-sep-2026) ya no se
        // suscribe al canal de bandeja de arriba (ve conversaciones de otros
        // agentes ahí) — conversations-list.js lo suscribe en su lugar a su
        // canal personal 'user.{id}', así que si la conversación es suya se
        // le manda también por ahí. El canal de bandeja se mantiene igual
        // para agent/supervisor/admin, que sí deben ver todo.
        $assignee = $conversation->assignee;
        if ($assignee && $assignee->hasPermissionTo('helpdesk.conversations.view-assigned-only')
            && ! $assignee->hasPermissionTo('helpdesk.conversations.view-all')
            && ! $assignee->hasPermissionTo('helpdesk.manage')
        ) {
            $channels[] = new PrivateChannel('user.'.$assignee->id);
        }

        return $channels;
    }

    /**
     * Light payload: id + truncated preview + light counters only. No
     * html_body, no attachment_urls, no metadata, no internal notes.
     */
    public function broadcastWith(): array
    {
        $this->item->loadMissing(['conversation.customer']);

        $conversation = $this->item->conversation;
        $customer = $conversation?->customer;

        return [
            'message' => [
                'id' => $this->item->id,
                'conversation_id' => $this->item->conversation_id,
                'author_id' => $this->item->author_id,
                'user_id' => $this->item->user_id,
                'type' => $this->item->type,
                // Preview truncado — nunca el cuerpo completo ni html_body.
                'body' => Str::limit((string) $this->item->body, self::PREVIEW_LENGTH, ''),
                'is_incoming' => empty($this->item->user_id) && ! empty($this->item->author_id),
                'created_at' => $this->item->created_at?->toIso8601String(),
            ],
            'conversation' => [
                'id' => $conversation?->id,
                'channel' => $conversation?->channel,
                'subject' => $conversation?->subject,
                'priority' => $conversation?->priority,
                // Usado en cliente por 'helpdesk-agent-restricted' para
                // descartar eventos de conversaciones que no son suyas — el
                // canal es por-bandeja, no per-agente (ver routes/channels.php).
                'assignee_id' => $conversation?->assignee_id,
                'last_message_at' => $conversation?->last_message_at?->toIso8601String() ?? $this->item->created_at?->toIso8601String(),
                // "Contadores" ligeros del sidebar: total de mensajes de la
                // conversación (no personalizado por usuario — el badge de no
                // leídos se sigue calculando en cliente como hoy).
                'messages_count' => $conversation?->messages()->count(),
                'customer' => $customer ? [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'avatar_url' => $customer->avatar_url,
                ] : null,
            ],
            'is_new_conversation' => $this->isNewConversation,
        ];
    }

    public function broadcastAs(): string
    {
        return 'item.created';
    }
}
