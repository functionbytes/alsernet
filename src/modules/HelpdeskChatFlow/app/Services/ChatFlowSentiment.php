<?php

namespace Modules\HelpdeskChatFlow\Services;

/**
 * Analyzes the sentiment of a customer message, reusing the helpdesk's
 * SentimentService when available. Used to escalate frustrated customers to a
 * human agent. Returns neutral when the service or AI is unavailable.
 */
class ChatFlowSentiment
{
    /**
     * @param  object|null  $service  Helpdesk SentimentService (optional)
     */
    public function __construct(
        private readonly ?object $service = null,
    ) {}

    /**
     * @return array{label: string, score: float}
     */
    public function analyze(string $text): array
    {
        if ($this->service === null || trim($text) === '') {
            return ['label' => 'neutral', 'score' => 0.0];
        }

        try {
            $result = $this->service->detect($text);

            return [
                'label' => $result['label'] ?? 'neutral',
                'score' => (float) ($result['score'] ?? 0.0),
            ];
        } catch (\Throwable) {
            return ['label' => 'neutral', 'score' => 0.0];
        }
    }

    /**
     * Whether the sentiment is negative enough to warrant escalation.
     */
    public function isFrustrated(array $sentiment, float $threshold = -0.4): bool
    {
        return ($sentiment['label'] ?? 'neutral') === 'negative'
            && (float) ($sentiment['score'] ?? 0.0) <= $threshold;
    }
}
