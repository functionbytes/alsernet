<?php

namespace Modules\HelpdeskChatFlow\Services\Simulation;

/**
 * A conversation that keeps the bot's messages in memory instead of creating
 * ConversationItems (see PostsBotMessages). Used by the editor's test panel.
 */
interface CapturesBotMessages
{
    /**
     * @param  array<string, mixed>  $item  Same payload a ConversationItem would be created with.
     */
    public function captureBotMessage(array $item): void;
}
