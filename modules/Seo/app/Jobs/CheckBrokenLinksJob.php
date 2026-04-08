<?php

namespace Modules\Seo\Jobs;

use App\Services\CircuitBreaker;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Seo\Models\SeoMeta;

class CheckBrokenLinksJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 3600;

    public function __construct()
    {
        $this->onQueue('seo-audit');
    }

    public function middleware(): array
    {
        return [(new WithoutOverlapping('check-broken-links'))->dontRelease()];
    }

    public function handle(): void
    {
        $progressKey = 'seo.broken_links.progress';
        $resultsKey = 'seo.broken_links.results';

        $metas = SeoMeta::query()
            ->whereNotNull('canonical_url')
            ->where('canonical_url', '!=', '')
            ->select(['id', 'title', 'canonical_url'])
            ->get();

        $total = $metas->count();
        $broken = [];
        $checked = 0;
        $circuit = new CircuitBreaker('seo-links', 5, 60);

        Cache::put($progressKey, ['status' => 'running', 'checked' => 0, 'total' => $total], 7200);

        foreach ($metas as $meta) {
            if (! $circuit->isAvailable()) {
                $broken[] = [
                    'meta_id' => $meta->id,
                    'title' => $meta->title ?? '(sin título)',
                    'url' => $meta->canonical_url,
                    'status' => 0,
                    'error' => 'Circuit breaker open — external link checks temporarily suspended',
                ];
                $checked++;

                continue;
            }

            try {
                $response = Http::withoutRedirecting()->timeout(8)->head($meta->canonical_url);
                $status = $response->status();

                $circuit->recordSuccess();

                if ($status >= 400) {
                    $broken[] = [
                        'meta_id' => $meta->id,
                        'title' => $meta->title ?? '(sin título)',
                        'url' => $meta->canonical_url,
                        'status' => $status,
                    ];
                }
            } catch (\Throwable $e) {
                $circuit->recordFailure();

                $broken[] = [
                    'meta_id' => $meta->id,
                    'title' => $meta->title ?? '(sin título)',
                    'url' => $meta->canonical_url,
                    'status' => 0,
                    'error' => $e->getMessage(),
                ];
            }

            $checked++;

            if ($checked % 10 === 0) {
                Cache::put($progressKey, ['status' => 'running', 'checked' => $checked, 'total' => $total], 7200);
            }
        }

        Cache::put($progressKey, ['status' => 'completed', 'checked' => $checked, 'total' => $total], 3600);
        Cache::put($resultsKey, $broken, 86400);

        Log::info("CheckBrokenLinksJob completed: {$checked} checked, ".count($broken).' broken.');
    }
}
