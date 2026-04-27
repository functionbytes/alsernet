<?php

namespace Modules\Ecommerce\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class RefreshSitemapCommand extends Command
{
    protected $signature = 'ecommerce:refresh-sitemap';

    protected $description = 'Limpia el cache del sitemap forzando regeneracion en el proximo acceso';

    public function handle(): int
    {
        Cache::forget('ecommerce_sitemap_xml');
        $this->info('Cache de sitemap limpiada.');

        return self::SUCCESS;
    }
}
