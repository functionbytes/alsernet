<?php

namespace Modules\HelpdeskChatFlow\Services\Simulation;

use Modules\HelpdeskChatFlow\Models\ChatFlow;
use Modules\HelpdeskChatFlow\Models\ChatFlowSession;

/**
 * Never-persisted session for the flow simulator: context and status live on
 * the in-memory model, so the real node handlers can run against the editor's
 * draft without touching the database.
 */
class SimulatedChatFlowSession extends ChatFlowSession
{
    /**
     * @param  array<int, array<string, mixed>>  $nodes  Draft nodes being tested.
     * @param  array<string, mixed>  $context
     */
    public static function for(array $nodes, array $context, SimulatedConversation $conversation): self
    {
        $flow = new ChatFlow;
        $flow->nodes = $nodes;

        $session = new self;
        $session->forceFill(['status' => 'active', 'context' => $context]);
        $session->setRelation('chatFlow', $flow);
        $session->setRelation('conversation', $conversation);

        return $session;
    }

    public function update(array $attributes = [], array $options = []): bool
    {
        $this->forceFill($attributes);

        return true; // simulation: nothing is ever written
    }

    public function save(array $options = []): bool
    {
        return true;
    }
}
