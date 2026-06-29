<?php

namespace Modules\Helpdesk\Http\Controllers\Managers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Modules\Helpdesk\Models\Setting;
use Nwidart\Modules\Facades\Module;

/**
 * Settings page to enable/disable optional Helpdesk integrations from the UI.
 * Currently exposes a single toggle for HelpdeskTickets — backs the
 * `helpdesk_tickets_enabled()` helper via the helpdesk_settings table.
 */
class IntegrationsController extends Controller
{
    public function index(): View
    {
        $this->authorize('helpdesk.settings.view');

        $ticketsModule = Module::find('HelpdeskTickets');

        return view('helpdesk::settings.integrations', [
            'ticketsModuleInstalled' => $ticketsModule !== null,
            'ticketsModuleEnabled' => $ticketsModule?->isEnabled() ?? false,
            'ticketsConfigEnabled' => (bool) config('helpdesk.tickets.enabled', true),
            'ticketsIntegrationEnabled' => helpdesk_tickets_enabled(),
            'ticketsStoredValue' => Setting::get('tickets.integration_enabled', '1'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorize('helpdesk.settings.update');

        $request->validate([
            'tickets_integration_enabled' => ['nullable', 'in:0,1'],
        ]);

        $value = $request->has('tickets_integration_enabled') ? '1' : '0';

        Setting::set('tickets.integration_enabled', $value, 'integrations');

        Cache::forget('helpdesk:tickets:integration_enabled');

        return back()->with('success', 'Integraciones actualizadas.');
    }
}
