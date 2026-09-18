<?php

namespace Modules\HelpdeskChatFlow\Services;

use Modules\Helpdesk\Models\ConversationItem;
use Modules\HelpdeskChatFlow\Models\ChatFlow;
use Modules\HelpdeskChatFlow\Models\ChatFlowSession;

/**
 * Replays the customer inputs from a real, recorded session against the CURRENT
 * version of the flow (via the stateless simulator), so you can confirm that an
 * edit doesn't break a conversation that previously worked.
 */
class ChatFlowReplayService
{
    public function __construct(
        private readonly ChatFlowTestSimulator $simulator,
    ) {}

    /**
     * @return array{
     *     inputs: array<int, string>,
     *     timeline: array<int, array<string, mixed>>,
     *     final_status: string,
     *     original_status: string
     * }
     */
    public function replay(ChatFlowSession $session): array
    {
        $result = $this->replayInputs($session->chatFlow, $this->customerInputs($session->conversation_id));
        $result['original_status'] = $session->status;

        return $result;
    }

    /**
     * Replays a list of customer inputs against a flow's current nodes.
     *
     * @param  array<int, string>  $inputs
     * @return array{inputs: array<int, string>, timeline: array<int, array<string, mixed>>, final_status: string, original_status: string}
     */
    public function replayInputs(ChatFlow $flow, array $inputs): array
    {
        $start = $this->simulator->start($flow->nodes ?? []);
        $timeline = [['role' => 'bot', 'messages' => $start['messages'] ?? []]];
        $status = $start['status'] ?? 'active';
        $key = $start['session_key'] ?? null;

        if ($key) {
            foreach ($inputs as $input) {
                if ($status !== 'active') {
                    break;
                }
                $timeline[] = ['role' => 'customer', 'text' => $input];
                $reply = $this->simulator->reply($key, $input);
                $timeline[] = ['role' => 'bot', 'messages' => $reply['messages'] ?? []];
                $status = $reply['status'] ?? $status;
            }
        }

        return [
            'inputs' => $inputs,
            'timeline' => $timeline,
            'final_status' => $status,
            'original_status' => $status,
        ];
    }

    /**
     * Customer messages (inbound, not from the bot or an agent), in order.
     *
     * @return array<int, string>
     */
    private function customerInputs(int $conversationId): array
    {
        return ConversationItem::on('helpdesk')
            ->where('conversation_id', $conversationId)
            ->where('type', 'message')
            ->where('is_internal', false)
            ->whereNull('user_id')
            ->whereJsonDoesntContain('metadata->sent_by_chatflow', true)
            ->orderBy('id')
            ->pluck('body')
            ->map(fn ($b) => (string) $b)
            ->filter(fn ($b) => trim($b) !== '')
            ->values()
            ->all();
    }
}
