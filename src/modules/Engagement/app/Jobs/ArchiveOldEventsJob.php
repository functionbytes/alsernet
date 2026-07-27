<?php

namespace Modules\Engagement\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Engagement\Models\Event;
use Modules\Engagement\Models\EventArchive;

class ArchiveOldEventsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    private const CHUNK_SIZE = 1000;

    private const RUN_LIMIT = 20000;

    public int $tries = 3;

    public int $timeout = 600;

    public function __construct(
        private readonly int $days = 90,
    ) {}

    public function handle(): void
    {
        $cutoff = now()->subDays($this->days);
        $archivedCount = 0;

        Event::query()
            ->where('occurred_at', '<', $cutoff)
            ->chunkById(self::CHUNK_SIZE, function ($events) use (&$archivedCount) {
                DB::connection('helpdesk')->transaction(function () use ($events) {
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

                    EventArchive::query()->insert($archives);

                    Event::query()->whereIn('id', $events->pluck('id'))->delete();
                });

                $archivedCount += $events->count();

                if ($archivedCount >= self::RUN_LIMIT) {
                    return false;
                }
            });

        // Backlog wasn't fully drained in this run; continue in a follow-up job.
        if ($archivedCount >= self::RUN_LIMIT) {
            self::dispatch($this->days);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ArchiveOldEventsJob failed', [
            'days' => $this->days,
            'error' => $exception->getMessage(),
        ]);
    }
}
