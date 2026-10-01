<?php

namespace Modules\HelpdeskChatFlow\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\HelpdeskChatFlow\Services\ChatFlowEngine;
use Modules\HelpdeskChatFlow\Services\ChatFlowInboundTriggerService;

/**
 * Runs the chat-flow work for a single inbound customer message off the request
 * thread. Dispatched by ConversationItemObserver so the channel webhook returns
 * immediately instead of blocking while a node calls OpenAI (ai_response ~30s,
 * ai_agent ~40s, intent classification ~15s), the ERP/PS order lookup or an
 * arbitrary http_request.
 *
 * Two modes:
 *  - MODE_PROCESS: an active session exists → feed the reply (text + attachments)
 *    to the waiting node via the engine.
 *  - MODE_TRIGGER: no active session → start the conversation_start flow (only for
 *    the conversation's first inbound customer message) or, on any message, a
 *    keyword / intent / no_agent flow (ChatFlowInboundTriggerService).
 *
 * The mode supplied at dispatch is only a hint: it is re-resolved at execution
 * time so the job self-corrects if the session state changed in between (e.g. a
 * previously queued trigger job started the session while this message waited).
 * WithoutOverlapping keyed by conversation keeps messages of the same
 * conversation strictly ordered (FIFO) across workers.
 */
class ExecuteChatFlowNodeJob implements ShouldQueue
{
    use Queueable;

    public const MODE_PROCESS = 'process';

    public const MODE_TRIGGER = 'trigger';

    public int $tries = 3;

    public int $timeout = 120;

    public int $backoff = 10;

    public function __construct(
        private readonly int $conversationId,
        private readonly int $itemId,
        private readonly string $mode = self::MODE_PROCESS,
    ) {
        $this->onQueue('chatflow');
    }

    /**
     * Serialize execution per conversation so two inbound messages of the same
     * conversation never run in parallel, preserving FIFO order between workers.
     * The lock outlives a normal run (expireAfter > timeout) so it is only ever
     * released by completion or a dead worker, never mid-execution.
     *
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('chatflow-conversation:'.$this->conversationId))
                ->releaseAfter(30)
                ->expireAfter(180),
        ];
    }

    public function handle(ChatFlowEngine $engine, ChatFlowInboundTriggerService $inboundTriggers): void
    {
        $conversation = Conversation::on('helpdesk')->find($this->conversationId);

        if (! $conversation) {
            return;
        }

        $item = ConversationItem::on('helpdesk')->find($this->itemId);

        if (! $item) {
            return;
        }

        $session = $engine->getActiveSession($conversation);

        // An active session always wins, regardless of the dispatched mode: a
        // trigger job may find the session already started by an earlier queued
        // message and should then process this reply instead of dropping it.
        if ($session) {
            if (! $session->isActive()) {
                return;
            }

            $engine->processMessage($session, $item->body ?? '', $item->attachment_urls ?? []);

            return;
        }

        // No active session: only the trigger mode may start a flow. A process-mode
        // job whose session has since ended exits cleanly rather than re-triggering.
        if ($this->mode !== self::MODE_TRIGGER) {
            return;
        }

        // conversation_start solo aplica al primer mensaje del cliente (mirrors the
        // original observer gate). Solo mensajes con autor (el cliente): las
        // respuestas automáticas (saludo, fuera de horario…) tampoco tienen user_id
        // y, contadas aquí, impedían que el bot arrancase si el saludo salía antes.
        $hasPriorCustomerMessage = ConversationItem::on('helpdesk')
            ->where('conversation_id', $this->conversationId)
            ->where('type', 'message')
            ->where('is_internal', false)
            ->whereNull('user_id')
            ->whereNotNull('author_id')
            ->where('id', '<', $this->itemId)
            ->whereJsonDoesntContain('metadata->sent_by_chatflow', true)
            ->exists();

        // El mensaje que dispara el flujo queda en el contexto (first_message y
        // last_input) para que un nodo IA lo conteste sin volver a preguntarlo.
        $message = trim((string) ($item->body ?? ''));

        if (! $hasPriorCustomerMessage) {
            $started = $engine->triggerFor($conversation, 'conversation_start', $message !== ''
                ? ['first_message' => $message, 'last_input' => $message]
                : []);

            if ($started) {
                return;
            }
        }

        // Sin sesión: en cualquier mensaje (no solo el primero) pueden arrancar
        // los flujos por keyword > intent > no_agent, con anti-bucle y sin pisar
        // a un agente humano. Ver ChatFlowInboundTriggerService.
        $inboundTriggers->triggerFromMessage($conversation, $message);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ExecuteChatFlowNodeJob failed', [
            'conversation_id' => $this->conversationId,
            'item_id' => $this->itemId,
            'mode' => $this->mode,
            'error' => $exception->getMessage(),
        ]);
    }
}
