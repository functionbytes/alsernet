<?php

namespace Modules\Helpdesk\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Helpdesk\Events\CsatRatingAnswered;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\CsatRating;

/**
 * CSAT (Customer Satisfaction) survey service.
 *
 * On conversation close we send the customer a 1-5 rating prompt with a
 * unique magic-link. The customer's answer is recorded against the
 * conversation and the agent who handled it.
 */
class CsatService
{
    public function __construct(
        private readonly OutboundMessageService $outbound,
    ) {}

    /**
     * Create a survey record and send the prompt to the customer's channel.
     * Idempotent: if there's already an unanswered survey for this conversation, reuse it.
     */
    public function dispatchForConversation(Conversation $conversation): ?CsatRating
    {
        if (! $this->outbound->supports($conversation) && $conversation->channel !== 'web') {
            return null;
        }

        $existing = CsatRating::query()
            ->where('conversation_id', $conversation->id)
            ->whereNull('answered_at')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->first();

        if ($existing) {
            return $existing;
        }

        $token = Str::random(32);

        $rating = CsatRating::create([
            'conversation_id' => $conversation->id,
            'customer_id' => $conversation->customer_id,
            'agent_id' => $conversation->assignee_id,
            'survey_token' => $token,
            'sent_at' => now(),
            'expires_at' => now()->addDays(7),
        ]);

        $url = route('helpdesk.csat.show', ['token' => $token]);
        $message = "¡Gracias por contactarnos! ¿Cómo te atendimos? Califica de 1 a 5 aquí: {$url}";

        try {
            if ($this->outbound->supports($conversation)) {
                $this->outbound->sendReply($conversation, $message);
            }
        } catch (\Throwable $e) {
            Log::warning('CsatService: failed to send survey', [
                'conversation_id' => $conversation->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $rating;
    }

    public function answer(string $token, int $rating, ?string $comment = null): ?CsatRating
    {
        $survey = CsatRating::query()->where('survey_token', $token)->first();

        if (! $survey || $survey->answered_at !== null) {
            return null;
        }

        if ($survey->expires_at && $survey->expires_at->isPast()) {
            return null;
        }

        $survey->update([
            'rating' => max(1, min(5, $rating)),
            'comment' => $comment,
            'answered_at' => now(),
        ]);

        CsatRatingAnswered::dispatch($survey->fresh());

        return $survey;
    }
}
