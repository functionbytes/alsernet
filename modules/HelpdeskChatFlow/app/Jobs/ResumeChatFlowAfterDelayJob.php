<?php

namespace Modules\HelpdeskChatFlow\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Modules\HelpdeskChatFlow\Models\ChatFlowSession;
use Modules\HelpdeskChatFlow\Services\ChatFlowEngine;

/**
 * Resumes a session paused on a `delay` node once its wait time has elapsed.
 *
 * Dispatched (with ->delay()) by ChatFlowEngine::scheduleDelay() instead of
 * the node passing straight through to its child, which used to make `delay`
 * a no-op (0-second wait).
 */
class ResumeChatFlowAfterDelayJob implements ShouldQueue
{
    use Queueable;

    /**
     * Sin tope de $tries: cada release() de WithoutOverlapping (conversación
     * ocupada) contaba como intento y el job se descartaba al agotarlos. La
     * ventana de reintento se acota por tiempo y los fallos reales por
     * maxExceptions.
     */
    public int $maxExceptions = 3;

    /**
     * Cubre el presupuesto de un nodo ai_agent: MAX_STEPS (6) x 40 s por
     * llamada = 240 s en ChatFlowAgentService, más margen.
     */
    public int $timeout = 300;

    public int $backoff = 10;

    public function __construct(
        private readonly int $sessionId,
        private readonly string $nodeId,
        private readonly int $conversationId,
    ) {
        $this->onQueue('chatflow');
    }

    public function retryUntil(): \DateTimeInterface
    {
        return now()->addMinutes(15);
    }

    /**
     * Same lock as ExecuteChatFlowNodeJob (keyed by conversation) so a delayed
     * resume never races with an inbound customer message being processed at
     * the same time.
     *
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('chatflow-conversation:'.$this->conversationId))
                ->releaseAfter(30)
                ->expireAfter(360),
        ];
    }

    public function handle(ChatFlowEngine $engine): void
    {
        $session = ChatFlowSession::find($this->sessionId);

        if (! $session) {
            return;
        }

        $engine->resumeAfterDelay($session, $this->nodeId);
    }
}
