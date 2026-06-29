<?php

namespace Modules\Template\Console;

use Illuminate\Console\Command;
use Modules\Template\Services\MenuService;

class ClearMenuCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'menu:clear-cache {location?}';

    /**
     * The console command description.
     */
    protected $description = 'Clear the menu cache for a specific location or all locations';

    /**
     * Execute the console command.
     */
    public function handle(MenuService $menuService): int
    {
        $location = $this->argument('location');

        if ($location) {
            $menuService->clearMenuCache($location);
            $this->info("Menu cache cleared for location: {$location}");
        } else {
            $menuService->clearMenuCache();
            $this->info('All menu caches cleared.');
        }

        return Command::SUCCESS;
    }
}
