<?php

namespace Modules\Helpdesk\Http\ViewComposers;

use Illuminate\View\View;

class NavigationComposer
{
    public function compose(View $view): void
    {
        $view->with('managerNav', config('helpdesk.navigation.manager', []));
        $view->with('agentNav', config('helpdesk.navigation.agent', []));

        // Legacy variable kept for views that still use it
        $navigationConfig = config('helpdesk.navigation', []);
        $view->with('helpdeskNavigation', $this->buildLegacyNavigationItems($navigationConfig));
    }

    protected function buildLegacyNavigationItems(array $config): array
    {
        if (empty($config) || ! auth()->check()) {
            return [];
        }

        $items = [];
        $user = auth()->user();

        if ($user->hasRole(['manager', 'super-admin'])) {
            foreach (['manager_settings', 'social_integrations'] as $key) {
                if (! empty($config[$key])) {
                    $section = $config[$key];
                    $items[] = [
                        'icon' => $section['icon'] ?? 'fas fa-cog',
                        'title' => $section['title'] ?? 'Settings',
                        'route' => $section['route'] ?? null,
                        'url' => $section['route'] ? route($section['route']) : null,
                        'active' => request()->routeIs($section['route'].'*'),
                    ];
                }
            }
        }

        if ($user->hasRole(['helpdesk-agent', 'super-admin']) && ! empty($config['agent_menu'])) {
            $section = $config['agent_menu'];
            $items[] = [
                'icon' => $section['icon'] ?? 'fas fa-headset',
                'title' => $section['title'] ?? 'Helpdesk',
                'route' => $section['route'] ?? null,
                'url' => $section['route'] ? route($section['route']) : null,
                'active' => request()->routeIs($section['route'].'*'),
            ];
        }

        return $items;
    }
}
