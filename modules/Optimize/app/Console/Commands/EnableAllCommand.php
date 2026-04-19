<?php

namespace Modules\Optimize\Console\Commands;

use Illuminate\Console\Command;
use Modules\Core\Models\Setting;

/**
 * Enables every Optimize middleware in one go so the PageSpeed-focused
 * setup (minify, defer, lazy, fonts, preload, dimensions, cache headers)
 * is active without manually toggling each switch in the settings panel.
 */
class EnableAllCommand extends Command
{
    protected $signature = 'optimize:enable-all
                            {--ttl=3600 : Cache-Control max-age in seconds}';

    protected $description = 'Activar todos los middleware de optimización (HTML minify, lazy, defer, font-display, preload, image dimensions, cache headers)';

    /** @var array<int, string> */
    private const ALL_KEYS = [
        'optimize.enabled',
        'optimize.remove_comments',
        'optimize.collapse_whitespace',
        'optimize.elide_attributes',
        'optimize.remove_quotes',
        'optimize.minify_inline_styles',
        'optimize.minify_inline_scripts',
        'optimize.defer_javascript',
        'optimize.add_loading_lazy',
        'optimize.inline_css',
        'optimize.insert_dns_prefetch',
        'optimize.inject_font_display',
        'optimize.inject_critical_preload',
        'optimize.add_image_dimensions',
        'optimize.cache_control_headers',
        'optimize.rewrite_min_assets',
    ];

    public function handle(): int
    {
        foreach (self::ALL_KEYS as $key) {
            Setting::set($key, '1');
        }
        Setting::set('optimize.cache_control_ttl', (string) max(60, (int) $this->option('ttl')));
        Setting::save();

        $this->info('✔ '.count(self::ALL_KEYS).' optimizaciones activadas.');
        $this->line('   TTL de Cache-Control: '.$this->option('ttl').'s');
        $this->line('   Ejecuta `php artisan optimize:clear` si algo no se aplica.');

        return self::SUCCESS;
    }
}
