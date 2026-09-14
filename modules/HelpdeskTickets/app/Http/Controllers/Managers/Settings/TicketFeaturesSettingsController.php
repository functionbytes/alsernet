<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Managers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Helpdesk\Models\Setting;
use Modules\HelpdeskTickets\Http\Requests\UpdateTicketFeaturesSettingsRequest;
use Modules\HelpdeskTickets\Support\TicketFeatures;

/**
 * Settings → Helpdesk · Tickets → Funcionalidades (14-sep-2026) — mismo
 * patrón que FeaturesSettingsController de Conversaciones, pero para la
 * vista de detalle del ticket. El catálogo de secciones/keys vive en
 * TicketFeatures (compartido con la vista del listado, que lo pasa al JS).
 */
class TicketFeaturesSettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:helpdesk.settings.view')->only('index');
        $this->middleware('can:helpdesk.settings.update')->only('update');
    }

    public function index(): View
    {
        $settings = TicketFeatures::resolved();
        $sections = TicketFeatures::SECTIONS;

        return view('helpdesktickets::managers.settings.features.index', compact('settings', 'sections'));
    }

    public function update(UpdateTicketFeaturesSettingsRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // boolean(), no has(): la vista usa <select> Activado/Desactivado
        // (convención del proyecto, no checkboxes) — el campo SIEMPRE viene
        // presente en el POST, así que has() daría true sin importar la
        // opción elegida. boolean() interpreta correctamente "1"/"0".
        foreach (TicketFeatures::keys() as $key) {
            $validated[$key] = $request->boolean($key);
        }

        Setting::setMany($validated, TicketFeatures::GROUP, 'settings.ticket_features.updated');

        cache()->forget('helpdesk_ticket_features');

        return back()->with('success', 'Configuración de funcionalidades actualizada correctamente.');
    }
}
