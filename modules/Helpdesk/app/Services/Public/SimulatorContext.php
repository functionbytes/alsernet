<?php

namespace Modules\Helpdesk\Services\Public;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Request-scoped flag that marks the synchronous window during which the public
 * simulator is injecting an inbound message.
 *
 * Why it exists: when an inbound message is injected through the real pipeline,
 * automations listening on MessageReceived may fire synchronously and attempt an
 * outbound send BEFORE the conversation has been tagged with
 * metadata.is_simulator. This flag lets {@see SimulatorOutboundMessageService}
 * block those sends during that window too — closing the gap between "conversation
 * created" and "conversation tagged". The flag is always restored in a finally
 * block, so it never leaks to later requests in the same worker process.
 */
class SimulatorContext
{
    private static bool $active = false;

    public static function active(): bool
    {
        return self::$active;
    }

    /**
     * Run a callback with the simulator flag enabled, restoring the previous
     * value afterwards (nesting-safe). When $now is given, the whole synchronous
     * pipeline (including the chat flow's "business hours" node) sees that
     * simulated time via Carbon's test-now clock — restored in the finally block.
     *
     * @template T
     *
     * @param  callable():T  $callback
     * @return T
     */
    public static function run(callable $callback, ?CarbonInterface $now = null): mixed
    {
        $previous = self::$active;
        $previousNow = Carbon::hasTestNow() ? Carbon::getTestNow() : null;
        self::$active = true;

        if ($now !== null) {
            Carbon::setTestNow($now);
        }

        try {
            return $callback();
        } finally {
            self::$active = $previous;

            if ($now !== null) {
                Carbon::setTestNow($previousNow);
            }
        }
    }
}
