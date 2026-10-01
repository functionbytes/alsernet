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
     * @param  int|null  $flowId  Id of the flow being tested; lets procedure calls detect cycles.
     */
    public static function for(array $nodes, array $context, SimulatedConversation $conversation, ?int $flowId = null): self
    {
        $flow = new ChatFlow;
        $flow->id = $flowId;
        $flow->nodes = $nodes;

        $session = new self;
        $session->forceFill(['status' => 'active', 'context' => $context, 'chat_flow_id' => $flowId]);
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
