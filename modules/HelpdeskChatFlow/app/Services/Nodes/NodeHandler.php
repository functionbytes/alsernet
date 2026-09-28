<?php

namespace Modules\HelpdeskChatFlow\Services\Nodes;

use Modules\Helpdesk\Models\Conversation;
use Modules\HelpdeskChatFlow\Models\ChatFlowSession;

/**
 * Executes one or more node types of a running flow.
 *
 * Handlers are registered in the container under NodeHandlerRegistry::TAG, so
 * another module can add node types without touching the engine:
 *
 *     $this->app->tag([MyNodeHandler::class], NodeHandlerRegistry::TAG);
 */
interface NodeHandler
{
    /**
     * @return array<int, string> Node types this handler executes.
     */
    public function types(): array;

    /**
     * Executes the node and returns the next node ID, or null to pause/end.
     *
     * @param  array<string, mixed>  $node
     */
    public function handle(array $node, ChatFlowSession $session, Conversation $conversation): ?string;
}
