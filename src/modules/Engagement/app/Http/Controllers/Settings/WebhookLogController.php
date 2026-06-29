<?php

namespace Modules\Engagement\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Engagement\Concerns\RecordsAudit;
use Modules\Engagement\Jobs\ProcessWebhookJob;
use Modules\Engagement\Models\WebhookLog;

class WebhookLogController extends Controller
{
    use RecordsAudit;

    public function __construct()
    {
        $this->middleware('can:helpdesk.livechat.platforms.view')->only('page', 'index', 'show');
        $this->middleware('can:helpdesk.livechat.platforms.update')->only('retry');
    }

    public function page(): View
    {
        return view('engagement::settings.engagement.webhook-logs');
    }

    public function index(Request $request): JsonResponse
    {
        $rows = WebhookLog::query()
            ->when($request->input('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->input('platform'), fn ($q, $p) => $q->where('platform', $p))
            ->when($request->input('integration_id'), fn ($q, $id) => $q->where('platform_integration_id', (int) $id))
            ->latest()
            ->limit(200)
            ->get([
                'id', 'platform_integration_id', 'platform', 'topic', 'status',
                'attempts', 'last_error', 'processed_at', 'created_at',
            ]);

        return response()->json(['success' => true, 'data' => $rows]);
    }

    public function show(WebhookLog $webhookLog): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $webhookLog]);
    }

    public function retry(WebhookLog $webhookLog): JsonResponse
    {
        if (! in_array($webhookLog->status, [WebhookLog::STATUS_FAILED, WebhookLog::STATUS_DEAD], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Solo se pueden reintentar webhooks fallidos o muertos.',
            ], 422);
        }

        $webhookLog->update([
            'status' => WebhookLog::STATUS_RECEIVED,
            'last_error' => null,
        ]);

        ProcessWebhookJob::dispatch($webhookLog->id);
        $this->audit('retried', 'webhook_log', $webhookLog->id);

        return response()->json(['success' => true]);
    }
}
