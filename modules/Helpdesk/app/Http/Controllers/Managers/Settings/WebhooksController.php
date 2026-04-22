<?php

namespace Modules\Helpdesk\Http\Controllers\Managers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Helpdesk\Models\Webhook;
use Modules\Helpdesk\Models\WebhookDelivery;

class WebhooksController extends Controller
{
    public const AVAILABLE_EVENTS = [
        'conversation.created' => 'Conversacion creada',
        'conversation.updated' => 'Conversacion actualizada',
        'conversation.status_changed' => 'Estado de conversacion cambiado',
        'conversation.assigned' => 'Conversacion asignada',
        'conversation.resolved' => 'Conversacion resuelta',
        'conversation.closed' => 'Conversacion cerrada',
    ];

    public function index(Request $request): View
    {
        $query = Webhook::query()->withCount('deliveries');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('url', 'like', "%{$search}%");
            });
        }

        $webhooks = $query->latest()->paginate(20);

        $stats = [
            'total' => Webhook::count(),
            'active' => Webhook::where('is_active', true)->count(),
            'inactive' => Webhook::where('is_active', false)->count(),
            'deliveries_today' => WebhookDelivery::whereDate('created_at', today())->count(),
        ];

        return view('helpdesk::managers.settings.helpdesk.webhooks.index', compact('webhooks', 'stats'));
    }

    public function create(): View
    {
        return view('helpdesk::managers.settings.helpdesk.webhooks.create', [
            'availableEvents' => self::AVAILABLE_EVENTS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'url' => ['required', 'url', 'max:500'],
            'integration_type' => ['nullable', 'string', 'in:generic,slack,discord,teams'],
            'events' => ['required', 'array', 'min:1'],
            'events.*' => ['string', 'in:'.implode(',', array_keys(self::AVAILABLE_EVENTS))],
            'secret' => ['nullable', 'string', 'max:64'],
            'headers' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'url.required' => 'La URL es obligatoria.',
            'url.url' => 'La URL no tiene un formato valido.',
            'events.required' => 'Debes seleccionar al menos un evento.',
            'events.min' => 'Debes seleccionar al menos un evento.',
        ]);

        $validated['headers'] = $this->parseHeaders($request->input('headers'));
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['integration_type'] = $validated['integration_type'] ?? 'generic';

        Webhook::create($validated);

        return redirect()
            ->route('manager.helpdesk.settings.webhooks.index')
            ->with('success', 'Webhook creado exitosamente.');
    }

    public function edit(Webhook $webhook): View
    {
        return view('helpdesk::managers.settings.helpdesk.webhooks.edit', [
            'webhook' => $webhook,
            'availableEvents' => self::AVAILABLE_EVENTS,
        ]);
    }

    public function update(Request $request, Webhook $webhook): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'url' => ['required', 'url', 'max:500'],
            'integration_type' => ['nullable', 'string', 'in:generic,slack,discord,teams'],
            'events' => ['required', 'array', 'min:1'],
            'events.*' => ['string', 'in:'.implode(',', array_keys(self::AVAILABLE_EVENTS))],
            'secret' => ['nullable', 'string', 'max:64'],
            'headers' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'url.required' => 'La URL es obligatoria.',
            'url.url' => 'La URL no tiene un formato valido.',
            'events.required' => 'Debes seleccionar al menos un evento.',
            'events.min' => 'Debes seleccionar al menos un evento.',
        ]);

        $validated['headers'] = $this->parseHeaders($request->input('headers'));
        $validated['is_active'] = $request->boolean('is_active');
        $validated['integration_type'] = $validated['integration_type'] ?? 'generic';

        $webhook->update($validated);

        return redirect()
            ->route('manager.helpdesk.settings.webhooks.index')
            ->with('success', 'Webhook actualizado exitosamente.');
    }

    public function destroy(Webhook $webhook): RedirectResponse
    {
        $webhook->delete();

        return redirect()
            ->route('manager.helpdesk.settings.webhooks.index')
            ->with('success', 'Webhook eliminado exitosamente.');
    }

    /**
     * Parse raw JSON headers string into an array, returning null on failure.
     *
     * @return array<string, string>|null
     */
    private function parseHeaders(?string $raw): ?array
    {
        if (blank($raw)) {
            return null;
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : null;
    }
}
