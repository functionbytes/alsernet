<?php

namespace Modules\HelpdeskChatFlow\Services\Input;

use Modules\HelpdeskChatFlow\Events\ChatFlowCsatRecorded;
use Modules\HelpdeskChatFlow\Models\ChatFlowSession;
use Modules\HelpdeskChatFlow\Services\ChatFlowLocalizer;

/**
 * Customer replies on a `csat` node: stores the score in the session, on the
 * conversation metadata and announces it (ChatFlowCsatRecorded).
 */
class CsatInputHandler
{
    use RepliesOnNode;

    public function __construct(
        private readonly ChatFlowLocalizer $localizer,
    ) {}

    /**
     * Capture a CSAT answer. Returns true when the score is low enough to hand
     * the conversation to a human (service recovery): the caller escalates and
     * stops the flow; otherwise the optional thanks message is sent here.
     *
     * @param  array<string, mixed>  $node
     */
    public function capture(ChatFlowSession $session, array $node, string $message): bool
    {
        $data = $node['data'] ?? [];
        $variableName = $data['variable_name'] ?? 'csat_score';
        $score = is_numeric(trim($message)) ? (int) trim($message) : $message;

        $session->setContextValues([$variableName => $score, 'csat_score' => $score]);

        // Persist the score on the conversation and announce it so agents, CRM and
        // reporting can react — not just the analytics widget.
        if (is_numeric($score)) {
            $this->recordCsat($session, (int) $score);
        }

        // Service recovery: a low score can hand the conversation to a human.
        if (is_numeric($score) && $this->isLowCsat($data, (int) $score)) {
            return true;
        }

        if (! empty($data['thanks_message'])) {
            $text = $this->localize($session, $this->interpolate($data['thanks_message'], $session));
            $this->sendBotMessage($session, $node, $text);
        }

        return false;
    }

    /**
     * Persist the CSAT score on the conversation metadata and broadcast the event.
     */
    private function recordCsat(ChatFlowSession $session, int $score): void
    {
        $conversation = $session->conversation;

        if ($conversation) {
            $conversation->update([
                'metadata' => array_merge($conversation->metadata ?? [], ['csat_score' => $score]),
            ]);
        }

        ChatFlowCsatRecorded::dispatch($session, $score);
    }

    /**
     * @param  array<string, mixed>  $data  CSAT node data
     */
    private function isLowCsat(array $data, int $score): bool
    {
        if (($data['csat_low_action'] ?? 'none') !== 'escalate') {
            return false;
        }

        $threshold = (int) ($data['csat_low_threshold'] ?? 0);

        return $threshold > 0 && $score <= $threshold;
    }

    private function interpolate(string $text, ChatFlowSession $session): string
    {
        return preg_replace_callback('/\{\{(\w+)\}\}/', fn ($m) => $session->getContextValue($m[1], $m[0]), $text);
    }
}
