<?php

namespace Modules\Shortcode\Listeners;

use Modules\Shortcode\Events\ShortcodeCompiled;
use Modules\Shortcode\Facades\Shortcode;
use Modules\Shortcode\Services\ShortcodeUsageStats;

class RecordShortcodeUsage
{
    public function __construct(private readonly ShortcodeUsageStats $stats) {}

    public function handle(ShortcodeCompiled $event): void
    {
        if (! (bool) config('shortcode.track_usage', true)) {
            return;
        }

        // Skip cache hits (ya contados cuando se compiló).
        if ($event->fromCache) {
            return;
        }

        $names = array_column(Shortcode::find($event->original), 'name');
        if (empty($names)) {
            return;
        }

        $this->stats->incrementMany($names);
    }
}
