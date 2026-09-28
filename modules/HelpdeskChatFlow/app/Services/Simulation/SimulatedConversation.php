<?php

namespace Modules\HelpdeskChatFlow\Services\Simulation;

use Modules\Helpdesk\Models\Conversation;

/**
 * Never-persisted conversation for the flow simulator: the real node handlers
 * post into it and the simulator reads back what the customer would see.
 */
class SimulatedConversation extends Conversation implements CapturesBotMessages
{
    /** @var array<int, array<string, mixed>> */
    private array $captured = [];

    public function captureBotMessage(array $item): void
    {
        $this->captured[] = $item;
    }

    /**
     * Returns and clears the messages captured since the last call.
     *
     * @return array<int, array<string, mixed>>
     */
    public function takeCaptured(): array
    {
        [$captured, $this->captured] = [$this->captured, []];

        return $captured;
    }

    public function save(array $options = []): bool
    {
        return true; // simulation: nothing is ever written
    }
}
