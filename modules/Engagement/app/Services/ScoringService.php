<?php

namespace Modules\Engagement\Services;

use Modules\Engagement\Events\ScoreThresholdCrossed;
use Modules\Engagement\Models\Event;
use Modules\Engagement\Models\VisitorContext;
use Modules\Engagement\Models\VisitorScore;
use Modules\Helpdesk\Models\Inbox;

class ScoringService
{
    public function recalculate(string $sessionToken, int $inboxId): VisitorScore
    {
        $events = Event::query()
            ->forSession($sessionToken)
            ->forInbox($inboxId)
            ->recent(24)
            ->get(['event_name', 'properties', 'occurred_at']);

        $context = VisitorContext::query()
            ->forSession($sessionToken)
            ->first();

        $score = $this->computeScore($events, $context);
        $segment = VisitorScore::segmentFromScore($score);

        $previous = VisitorScore::query()
            ->forSession($sessionToken)
            ->first();

        $previousSegment = $previous?->segment ?? 'cold';

        $visitorScore = VisitorScore::query()->updateOrCreate(
            ['session_token' => $sessionToken],
            [
                'inbox_id' => $inboxId,
                'score' => $score,
                'segment' => $segment,
                'last_event_at' => now(),
                'last_recalc_at' => now(),
            ],
        );

        if ($previousSegment !== $segment) {
            ScoreThresholdCrossed::dispatch($visitorScore, $previousSegment);
        }

        return $visitorScore;
    }

    public function computeScore(iterable $events, ?VisitorContext $context): int
    {
        $pages = 0;
        $timeOnSiteMinutes = 0;
        $productViews = 0;
        $addToCarts = 0;
        $checkoutStarts = 0;
        $purchases = 0;
        $cartValue = (float) ($context?->context['cartValue'] ?? 0);

        foreach ($events as $event) {
            switch ($event->event_name) {
                case 'page_view':
                    $pages++;
                    break;
                case 'session_start':
                    $timeOnSiteMinutes += 1;
                    break;
                case 'product_view':
                    $productViews++;
                    break;
                case 'add_to_cart':
                    $addToCarts++;
                    break;
                case 'checkout_start':
                    $checkoutStarts++;
                    break;
                case 'purchase':
                    $purchases++;
                    break;
            }
        }

        $score = $pages * 2
            + $timeOnSiteMinutes
            + $cartValue * 0.5
            + $productViews * 3
            + $addToCarts * 8
            + $checkoutStarts * 20
            + $purchases * 40;

        return (int) min(100, max(0, $score));
    }

    public function getOrCreate(string $sessionToken, Inbox $inbox): VisitorScore
    {
        return VisitorScore::query()->firstOrCreate(
            ['session_token' => $sessionToken],
            [
                'inbox_id' => $inbox->id,
                'score' => 0,
                'segment' => VisitorScore::SEGMENT_COLD,
                'last_event_at' => now(),
                'last_recalc_at' => now(),
            ],
        );
    }
}
