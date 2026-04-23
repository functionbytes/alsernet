<?php

namespace Modules\Page\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Page\Models\Page;
use Modules\Page\Models\PageWebhook;
use Modules\Page\Services\WebhookUrlValidator;

class PageWebhookController extends Controller
{
    private const ALLOWED_EVENTS = ['published', 'updated', 'deleted'];

    private const ALLOWED_TIMEOUTS = [5, 10, 30];

    public function index(Request $request): View
    {
        $this->authorize('update', Page::class);

        $query = PageWebhook::query();

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $webhooks = $query->latest()->paginate(25);

        $statsRaw = PageWebhook::query()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active')
            ->selectRaw('SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive')
            ->first();

        $stats = [
            'total' => (int) $statsRaw->total,
            'active' => (int) $statsRaw->active,
            'inactive' => (int) $statsRaw->inactive,
        ];

        return view('page::pages.webhooks.index', compact('webhooks', 'stats'));
    }

    public function create(): View
    {
        $this->authorize('update', Page::class);

        return view('page::pages.webhooks.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('update', Page::class);

        $maxWebhooks = config('page.webhooks.max_count', 20);
        if (PageWebhook::count() >= $maxWebhooks) {
            return back()->withErrors(['url' => "Límite máximo de {$maxWebhooks} webhooks alcanzado."]);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'url' => ['required', 'url', 'max:500'],
            'secret' => ['nullable', 'string', 'max:64'],
            'events' => ['required', 'array', 'min:1'],
            'events.*' => ['required', 'string', 'in:'.implode(',', self::ALLOWED_EVENTS)],
            'timeout' => ['required', 'integer', 'in:'.implode(',', self::ALLOWED_TIMEOUTS)],
            'is_active' => ['boolean'],
        ]);

        try {
            WebhookUrlValidator::validate($validated['url']);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['url' => $e->getMessage()])->withInput();
        }

        $validated['is_active'] = $request->boolean('is_active', true);

        PageWebhook::create($validated);

        return redirect()
            ->route('pages.webhooks.index')
            ->with('success', 'Webhook creado exitosamente.');
    }

    public function edit(PageWebhook $webhook): View
    {
        $this->authorize('update', Page::class);

        return view('page::pages.webhooks.edit', compact('webhook'));
    }

    public function update(Request $request, PageWebhook $webhook): RedirectResponse
    {
        $this->authorize('update', Page::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'url' => ['required', 'url', 'max:500'],
            'secret' => ['nullable', 'string', 'max:64'],
            'events' => ['required', 'array', 'min:1'],
            'events.*' => ['required', 'string', 'in:'.implode(',', self::ALLOWED_EVENTS)],
            'timeout' => ['required', 'integer', 'in:'.implode(',', self::ALLOWED_TIMEOUTS)],
            'is_active' => ['boolean'],
        ]);

        try {
            WebhookUrlValidator::validate($validated['url']);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['url' => $e->getMessage()])->withInput();
        }

        $validated['is_active'] = $request->boolean('is_active', false);

        $webhook->update($validated);

        return redirect()
            ->route('pages.webhooks.index')
            ->with('success', 'Webhook actualizado exitosamente.');
    }

    public function destroy(PageWebhook $webhook): RedirectResponse
    {
        $this->authorize('update', Page::class);

        $webhook->delete();

        return redirect()
            ->route('pages.webhooks.index')
            ->with('success', 'Webhook eliminado exitosamente.');
    }

    public function toggle(PageWebhook $webhook): JsonResponse
    {
        $this->authorize('update', Page::class);

        $webhook->update(['is_active' => ! $webhook->is_active]);

        return response()->json([
            'success' => true,
            'message' => $webhook->is_active ? 'Webhook activado' : 'Webhook desactivado',
        ]);
    }

    public function bulkAction(Request $request): JsonResponse
    {
        $this->authorize('update', Page::class);

        $validated = $request->validate([
            'action' => ['required', 'string', 'in:activate,deactivate,delete'],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $webhooks = PageWebhook::whereIn('id', $validated['ids'])->get();
        $count = 0;

        foreach ($webhooks as $webhook) {
            match ($validated['action']) {
                'activate' => $webhook->update(['is_active' => true]),
                'deactivate' => $webhook->update(['is_active' => false]),
                'delete' => $webhook->delete(),
            };
            $count++;
        }

        return response()->json(['success' => true, 'count' => $count]);
    }

    /** Send a test payload synchronously (no queue). */
    public function test(PageWebhook $webhook): JsonResponse
    {
        $this->authorize('update', Page::class);

        try {
            WebhookUrlValidator::validate($webhook->url);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => 'URL del webhook no permitida: '.$e->getMessage(),
            ], 422);
        }

        $timestamp = now()->timestamp;

        $body = json_encode([
            'event' => 'test',
            'timestamp' => now()->toIso8601String(),
            'data' => [
                'id' => 0,
                'title' => 'Página de prueba',
                'slug' => 'pagina-prueba',
                'status' => 'published',
                'url' => url('/pagina-prueba'),
                'published_at' => now()->toIso8601String(),
                'updated_at' => now()->toIso8601String(),
            ],
        ]);

        $headers = [
            'Content-Type' => 'application/json',
            'X-Webhook-Event' => 'test',
            'X-Webhook-Timestamp' => (string) $timestamp,
            'User-Agent' => 'InoqualabsWebhook/1.0',
        ];

        if ($webhook->secret) {
            $headers['X-Webhook-Signature'] = 'sha256='.$webhook->generateSignature($body, $timestamp);
        }

        try {
            $response = Http::timeout($webhook->timeout)
                ->withHeaders($headers)
                ->post($webhook->url, json_decode($body, true));

            return response()->json([
                'success' => true,
                'status_code' => $response->status(),
                'message' => "Webhook enviado correctamente. Respuesta HTTP {$response->status()}.",
            ]);
        } catch (\Exception $e) {
            Log::warning('Webhook test failed', ['webhook_id' => $webhook->id, 'error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Error al enviar el webhook de prueba. Verifica que la URL sea accesible.',
            ], 422);
        }
    }
}
