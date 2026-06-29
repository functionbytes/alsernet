<?php

namespace Modules\Shortcode\Telescope;

use Illuminate\Contracts\Foundation\Application;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\Watchers\Watcher;
use Modules\Shortcode\Events\ShortcodeCompiled;
use Modules\Shortcode\Facades\Shortcode;

/**
 * Telescope watcher para eventos de compilación de shortcodes.
 *
 * Cada `ShortcodeCompiled` se registra como entry de Telescope con:
 *  - nombres de shortcodes detectados
 *  - número de pases
 *  - cache hit / miss
 *  - tamaños de entrada y salida
 *
 * Habilitar en config/telescope.php:
 *
 *     'watchers' => [
 *         // ... otros watchers
 *         \Modules\Shortcode\Telescope\ShortcodeWatcher::class => true,
 *     ],
 */
class ShortcodeWatcher extends Watcher
{
    public function register(Application $app): void
    {
        $app['events']->listen(ShortcodeCompiled::class, [$this, 'recordCompile']);
    }

    public function recordCompile(ShortcodeCompiled $event): void
    {
        if (! Telescope::isRecording()) {
            return;
        }

        $names = array_unique(array_column(Shortcode::find($event->original), 'name'));

        Telescope::recordEvent(IncomingEntry::make([
            'type' => 'shortcode',
            'name' => 'ShortcodeCompiled',
            'shortcodes' => $names,
            'passes' => $event->passes,
            'from_cache' => $event->fromCache,
            'input_size' => strlen($event->original),
            'output_size' => strlen($event->compiled),
            'outcome' => $event->fromCache ? 'cache-hit' : 'compiled',
        ])->tags(array_map(fn ($n) => 'shortcode:'.$n, $names)));
    }
}
