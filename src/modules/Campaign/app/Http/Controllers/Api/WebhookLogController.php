<?php

namespace Modules\Campaign\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Campaign\Models\CampaignWebhook;
use Modules\Campaign\Models\CampaignWebhookLog;

class WebhookLogController extends Controller
{
    public function index(Request $request, string $uid): JsonResponse
    {
        $webhook = CampaignWebhook::where('uid', $uid)->firstOrFail();

        $query = CampaignWebhookLog::where('campaign_webhook_id', $webhook->id);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $perPage = min(100, max(1, (int) $request->input('per_page', 50)));

        return response()->json([
            'data' => $query->orderBy('created_at', 'desc')->paginate($perPage),
        ]);
    }

    public function stats(string $uid): JsonResponse
    {
        $webhook = CampaignWebhook::where('uid', $uid)->firstOrFail();

        $total = CampaignWebhookLog::where('campaign_webhook_id', $webhook->id)->count();
        $success = CampaignWebhookLog::where('campaign_webhook_id', $webhook->id)->where('status', 'success')->count();
        $failed = CampaignWebhookLog::where('campaign_webhook_id', $webhook->id)->where('status', 'failed')->count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'total' => $total,
                'success' => $success,
                'failed' => $failed,
                'success_rate' => $total > 0 ? round(($success / $total) * 100, 2) : 0,
            ],
        ]);
    }
}
