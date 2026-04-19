<?php

namespace Modules\Template\Observers;

use Modules\Template\Models\MenuItem;
use Modules\Template\Services\MenuService;

class MenuItemObserver
{
    public function __construct(
        private readonly MenuService $menuService
    ) {}

    /**
     * Handle the MenuItem "created" event.
     */
    public function created(MenuItem $menuItem): void
    {
        $this->invalidateCache($menuItem);
    }

    /**
     * Handle the MenuItem "updated" event.
     */
    public function updated(MenuItem $menuItem): void
    {
        $this->invalidateCache($menuItem);
    }

    /**
     * Handle the MenuItem "deleted" event.
     */
    public function deleted(MenuItem $menuItem): void
    {
        $this->invalidateCache($menuItem);
    }

    /**
     * Invalidate the menu cache for the item's location.
     */
    private function invalidateCache(MenuItem $menuItem): void
    {
        $location = $menuItem->menu?->location;

        $this->menuService->clearMenuCache($location ?: null);
    }
}
