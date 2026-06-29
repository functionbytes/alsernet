<?php

namespace Modules\Engagement\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Modules\Engagement\Models\Event;
use Modules\Engagement\Models\EventArchive;

class ArchiveOldEventsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 600;

    public function __construct(
        private readonly int $days = 90,
    ) {}

    public function handle(): void
    {
        $cutoff = now()->subDays($this->days);

        DB::connection('helpdesk')->transaction(function () use ($cutoff) {
            $query = Event::query()
                ->where('occurred_at', '<', $cutoff)
                ->orderBy('id')
                ->limit(5000);

            $events = $query->get();

            if ($events->isEmpty()) {
                return;
            }

            $archives = $events->map(fn (Event $e) => [
                'session_token' => $e->session_token,
                'inbox_id' => $e->inbox_id,
                'customer_id' => $e->customer_id,
                'event_name' => $e->event_name,
                'platform' => $e->platform,
                'properties' => $e->properties,
                'occurred_at' => $e->occurred_at,
                'archived_at' => now(),
            ])->all();

            foreach (array_chunk($archives, 1000) as $chunk) {
                EventArchive::query()->insert($chunk);
            }

            Event::query()
                ->whereIn('id', $events->pluck('id'))
                ->delete();
        });
    }
}
