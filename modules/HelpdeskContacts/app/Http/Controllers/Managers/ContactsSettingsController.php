<?php

namespace Modules\HelpdeskContacts\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Models\Setting;
use Modules\HelpdeskContacts\Http\Requests\Managers\UpdateContactsSettingsRequest;
use Modules\HelpdeskContacts\Support\ContactLayouts;

/**
 * Ajustes → Helpdesk · Contactos: estilo de la ficha 360 (global, todos los
 * agentes). Mismo patrón que TicketFeaturesSettingsController.
 */
class ContactsSettingsController extends Controller
{
    public function index(Request $request): View
    {
        // Un contacto visible para el agente, para los enlaces "Previsualizar".
        $sample = Customer::query()
            ->forAgent($request->user())
            ->orderByDesc('last_seen_at')
            ->first(['id']);

        return view('contacts::settings.index', [
            'current' => ContactLayouts::current(),
            'options' => ContactLayouts::OPTIONS,
            'sampleId' => $sample?->id,
        ]);
    }

    public function update(UpdateContactsSettingsRequest $request): RedirectResponse
    {
        Setting::setMany(
            [ContactLayouts::KEY => $request->validated('detail_layout')],
            ContactLayouts::GROUP,
            'settings.contacts.updated',
        );

        return back()->with('success', 'Estilo de la ficha de contacto actualizado.');
    }
}
