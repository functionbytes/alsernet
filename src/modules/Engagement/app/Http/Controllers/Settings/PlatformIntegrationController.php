<?php

namespace Modules\Engagement\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Engagement\Concerns\RecordsAudit;
use Modules\Engagement\Models\PlatformIntegration;
use Modules\Helpdesk\Models\Inbox;

class PlatformIntegrationController extends Controller
{
    use RecordsAudit;

    public function __construct()
    {
        $this->middleware('can:helpdesk.livechat.platforms.view')->only('page', 'index', 'show');
        $this->middleware('can:helpdesk.livechat.platforms.create')->only('store');
        $this->middleware('can:helpdesk.livechat.platforms.update')->only('update', 'rotateSecret');
        $this->middleware('can:helpdesk.livechat.platforms.delete')->only('destroy');
    }

    public function page(): View
    {
        $inboxes = Inbox::query()->where('is_active', true)->get(['id', 'name']);

        return view('engagement::settings.engagement.platforms', compact('inboxes'));
    }

    public function index(Request $request): JsonResponse
    {
        $rows = PlatformIntegration::query()
            ->when($request->input('inbox_id'), fn ($q, $id) => $q->forInbox((int) $id))
            ->with('inbox:id,name')
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $rows->map(fn ($i) => $this->presentSummary($i)),
        ]);
    }

    public function show(PlatformIntegration $platformIntegration): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->presentDetail($platformIntegration),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'inbox_id' => ['required', 'integer', 'exists:helpdesk.helpdesk_inboxes,id'],
            'platform' => ['required', 'in:prestashop,shopify,woocommerce,custom'],
            'store_url' => ['nullable', 'url', 'max:500'],
            'config' => ['nullable', 'array'],
            'is_active' => ['boolean'],
        ]);

        $integration = PlatformIntegration::query()->create($validated);
        $this->audit('created', 'integration', $integration->id, ['platform' => $integration->platform, 'inbox_id' => $integration->inbox_id]);

        return response()->json([
            'success' => true,
            'data' => $this->presentDetail($integration),
        ], 201);
    }

    public function update(Request $request, PlatformIntegration $platformIntegration): JsonResponse
    {
        $validated = $request->validate([
            'store_url' => ['nullable', 'url', 'max:500'],
            'config' => ['nullable', 'array'],
            'is_active' => ['boolean'],
        ]);

        $platformIntegration->update($validated);

        return response()->json([
            'success' => true,
            'data' => $this->presentDetail($platformIntegration->fresh()),
        ]);
    }

    public function rotateSecret(PlatformIntegration $platformIntegration): JsonResponse
    {
        $platformIntegration->update(['webhook_secret' => Str::random(64)]);
        $this->audit('rotated_secret', 'integration', $platformIntegration->id);

        return response()->json([
            'success' => true,
            'data' => $this->presentDetail($platformIntegration->fresh()),
        ]);
    }

    public function destroy(PlatformIntegration $platformIntegration): JsonResponse
    {
        $id = $platformIntegration->id;
        $platformIntegration->delete();
        $this->audit('deleted', 'integration', $id);

        return response()->json(['success' => true]);
    }

    private function presentSummary(PlatformIntegration $i): array
    {
        return [
            'id' => $i->id,
            'inbox' => ['id' => $i->inbox_id, 'name' => $i->inbox?->name],
            'platform' => $i->platform,
            'store_url' => $i->store_url,
            'is_active' => $i->is_active,
            'last_event_at' => $i->last_event_at?->toIso8601String(),
            'created_at' => $i->created_at?->toIso8601String(),
        ];
    }

    private function presentDetail(PlatformIntegration $i): array
    {
        return array_merge($this->presentSummary($i), [
            'webhook_secret' => $i->webhook_secret,
            'webhook_url' => url("/hd/api/sdk/webhook/{$i->platform}/{$i->id}"),
            'config' => $i->config,
        ]);
    }
}
