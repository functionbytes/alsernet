<?php

namespace Modules\Helpdesk\Http\Controllers\Managers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Helpdesk\Http\Requests\StoreWebhookRequest;
use Modules\Helpdesk\Http\Requests\UpdateWebhookRequest;
use Modules\Helpdesk\Models\Webhook;
use Modules\Helpdesk\Models\WebhookDelivery;

class WebhooksController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:helpdesk.webhooks.view')->only(['index', 'show']);
        $this->middleware('can:helpdesk.webhooks.create')->only(['create', 'store']);
        $this->middleware('can:helpdesk.webhooks.update')->only(['edit', 'update', 'toggleActive', 'test']);
        $this->middleware('can:helpdesk.webhooks.delete')->only(['destroy']);
    }

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

        $webhookRow = Webhook::query()->selectRaw('
            COUNT(*) as total,
            SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active,
            SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive
        ')->first();

        $stats = [
            'total' => (int) $webhookRow->total,
            'active' => (int) $webhookRow->active,
            'inactive' => (int) $webhookRow->inactive,
            'deliveries_today' => WebhookDelivery::whereDate('created_at', today())->count(),
        ];

        return view('helpdesk::settings.webhooks.index', compact('webhooks', 'stats'));
    }

    public function create(): View
    {
        return view('helpdesk::settings.webhooks.create', [
            'availableEvents' => self::AVAILABLE_EVENTS,
        ]);
    }

    public function store(StoreWebhookRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['headers'] = $this->parseHeaders($request->input('headers'));
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['integration_type'] = $validated['integration_type'] ?? 'generic';

        Webhook::create($validated);

        return redirect()
            ->route('settings.helpdesk.webhooks.index')
            ->with('success', 'Webhook creado exitosamente.');
    }

    public function edit(Webhook $webhook): View
    {
        return view('helpdesk::settings.webhooks.edit', [
            'webhook' => $webhook,
            'availableEvents' => self::AVAILABLE_EVENTS,
        ]);
    }

    public function update(UpdateWebhookRequest $request, Webhook $webhook): RedirectResponse
    {
        $validated = $request->validated();
        $validated['headers'] = $this->parseHeaders($request->input('headers'));
        $validated['is_active'] = $request->boolean('is_active');
        $validated['integration_type'] = $validated['integration_type'] ?? 'generic';

        if (blank($validated['secret'] ?? null)) {
            unset($validated['secret']);
        }

        $webhook->update($validated);

        return redirect()
            ->route('settings.helpdesk.webhooks.index')
            ->with('success', 'Webhook actualizado exitosamente.');
    }

    public function destroy(Webhook $webhook): RedirectResponse
    {
        $webhook->delete();

        return redirect()
            ->route('settings.helpdesk.webhooks.index')
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
