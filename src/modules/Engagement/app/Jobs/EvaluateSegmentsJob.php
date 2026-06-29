<?php

namespace Modules\Engagement\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Modules\Engagement\Models\Segment;
use Modules\Engagement\Models\VisitorScore;
use Modules\Engagement\Services\SegmentEvaluator;

class EvaluateSegmentsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 2;

    public function __construct(
        private readonly int $inboxId,
    ) {}

    public function handle(SegmentEvaluator $evaluator): void
    {
        $segments = Segment::query()
            ->forInbox($this->inboxId)
            ->active()
            ->get();

        if ($segments->isEmpty()) {
            return;
        }

        $scores = VisitorScore::query()
            ->where('inbox_id', $this->inboxId)
            ->where('updated_at', '>=', now()->subHours(24))
            ->get(['session_token', 'inbox_id']);

        foreach ($scores as $score) {
            foreach ($segments as $segment) {
                if ($evaluator->matches($segment, $score->session_token, $this->inboxId)) {
                    // Upsert pivot record
                    DB::connection('helpdesk')->table('engagement_segment_customers')->upsert(
                        [
                            'segment_id' => $segment->id,
                            'customer_id' => $score->customer_id ?? 0,
                            'matched_at' => now(),
                        ],
                        ['segment_id', 'customer_id'],
                        ['matched_at']
                    );
                }
            }
        }
    }
}
