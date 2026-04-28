<?php

namespace Modules\Campaign\Http\ViewComposers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

class NavigationComposer
{
    /**
     * Bind data to the view.
     */
    public function compose(View $view): void
    {
        $navigationConfig = config('campaign.navigation', []);

        $campaignSidebarItems = $this->buildNavigationItems($navigationConfig);

        $view->with('campaignNavigation', $campaignSidebarItems);
    }

    /**
     * Build navigation items with permission checks.
     */
    protected function buildNavigationItems(array $config): array
    {
        if (empty($config['sidebar']['section'])) {
            return [];
        }

        $section = $config['sidebar']['section'];

        // Check section permission if defined
        if (! empty($section['permission']) && ! $this->hasPermission($section['permission'])) {
            return [];
        }

        $items = [];
        foreach ($section['items'] ?? [] as $item) {
            // Check if route exists
            if (! Route::has($item['route'])) {
                continue;
            }

            // Check item permission if defined
            if (! empty($item['permission']) && ! $this->hasPermission($item['permission'])) {
                continue;
            }

            $items[] = [
                'label' => $item['label'],
                'route' => $item['route'],
                'url' => route($item['route']),
                'active' => request()->routeIs($item['route'].'*'),
            ];
        }

        return [
            'title' => $section['title'],
            'items' => $items,
            'insert_after' => $config['sidebar']['insert_after'] ?? null,
        ];
    }

    /**
     * Check if user has permission.
     */
    protected function hasPermission(string $permission): bool
    {
        // Super-helpdesks bypass
        if (auth()->check() && auth()->user()->hasRole('super-helpdesks')) {
            return true;
        }

        // Check permission via Gate
        return Gate::allows($permission);
    }
}
