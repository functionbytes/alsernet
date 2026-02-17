<?php

namespace Modules\Mailrelay\Http\ViewComposers;

use App\Helpers\ModuleStatusHelper;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class NavigationComposer
{
    /**
     * Bind data to the view.
     */
    public function compose(View $view): void
    {
        // ✅ Check if Mailrelay module is enabled before showing navigation
        if (! ModuleStatusHelper::isModuleEnabled('Mailrelay')) {
            $view->with('mailrelayNavigation', []);

            return;
        }

        if (! Auth::check()) {
            return;
        }

        $user = Auth::user();

        // Check if user has access to mailrelay module using Gate instead of hasPermissionTo
        // This uses the gates defined in MailrelayServiceProvider
        if (! Gate::allows('mailrelay.access') && ! $user->hasRole('super-settings')) {
            return;
        }

        // Add mailrelay navigation items
        // This will be picked up by the main navigation system
        $view->with('mailrelayNavigation', [
            'title' => 'Email Marketing',
            'icon' => 'fas fa-envelope-open-text',
            'permission' => 'mailrelay.access',
            'items' => [
                [
                    'title' => 'Dashboard',
                    'route' => 'mailrelay.dashboard',
                    'icon' => 'fas fa-chart-line',
                    'active' => request()->routeIs('mailrelay.dashboard'),
                    'permission' => 'mailrelay.access',
                ],
                [
                    'title' => 'Campañas',
                    'route' => 'mailrelay.campaigns.index',
                    'icon' => 'fas fa-paper-plane',
                    'active' => request()->routeIs('mailrelay.campaigns.*'),
                    'permission' => 'mailrelay.campaigns.view',
                ],
                [
                    'title' => 'Suscriptores',
                    'route' => 'mailrelay.subscribers.index',
                    'icon' => 'fas fa-users',
                    'active' => request()->routeIs('mailrelay.subscribers.*'),
                    'permission' => 'mailrelay.subscribers.view',
                ],
                [
                    'title' => 'Importaciones',
                    'route' => 'mailrelay.imports.index',
                    'icon' => 'fas fa-file-import',
                    'active' => request()->routeIs('mailrelay.imports.*'),
                    'permission' => 'mailrelay.imports.view',
                ],
                [
                    'title' => 'Validación',
                    'route' => 'mailrelay.validation.test',
                    'icon' => 'fas fa-check-circle',
                    'active' => request()->routeIs('mailrelay.validation.*'),
                    'permission' => 'mailrelay.access',
                ],
            ],
        ]);
    }
}
