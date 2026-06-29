<?php

namespace Modules\Document\Http\ViewComposers;

use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class NavigationComposer
{
    /**
     * Bind data to the view.
     */
    public function compose(View $view): void
    {
        $navigationConfig = config('documents.navigation', []);

        $documentsSidebarItems = $this->buildNavigationItems($navigationConfig);

        $view->with('documentsNavigation', $documentsSidebarItems);
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
        // Super-admin bypass
        if (auth()->check() && auth()->user()->hasRole('super-admin')) {
            return true;
        }

        // Check permission via Gate
        return Gate::allows($permission);
    }
}
