<?php

namespace Modules\HelpdeskSocial\Services;

use Illuminate\Support\Facades\Log;
use Modules\HelpdeskSocial\Contracts\IntentClassifierInterface;
use Modules\HelpdeskSocial\Events\IntentClassified;
use Modules\HelpdeskSocial\Models\SocialComment;
use Modules\HelpdeskSocial\Models\SocialIntent;

class IntentClassificationService
{
    public function __construct(
        private readonly IntentClassifierInterface $classifier,
    ) {}

    /**
     * Classify a social comment and persist the result.
     */
    public function classify(SocialComment $comment): SocialIntent
    {
        try {
            $result = $this->classifier->classify($comment);

            $intent = SocialIntent::updateOrCreate(
                [
                    'classifiable_type' => SocialComment::class,
                    'classifiable_id' => $comment->id,
                ],
                [
                    'platform' => $comment->platform,
                    'intent' => $result['intent'],
                    'confidence' => $result['confidence'],
                    'classifier' => $result['classifier'],
                    'urgency' => $result['urgency'],
                    'keywords_matched' => $result['keywords_matched'] ?? null,
                    'entities' => $result['entities'] ?? null,
                    'raw_response' => $result['raw_response'] ?? null,
                    'classified_at' => now(),
                ]
            );

            $comment->update([
                'intent' => $result['intent'],
                'intent_confidence' => $result['confidence'],
                'urgency' => $result['urgency'],
                'keywords' => $result['keywords_matched'] ?? null,
            ]);

            // Broadcast intent classification in real-time
            IntentClassified::dispatch($comment, $intent);

            return $intent;
        } catch (\Throwable $e) {
            Log::error('IntentClassificationService: classification failed', [
                'comment_id' => $comment->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
