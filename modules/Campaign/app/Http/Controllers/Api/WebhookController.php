<?php

namespace Modules\Campaign\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\Campaign\Library\WebhookDispatcher;
use Modules\Campaign\Models\Campaign;
use Modules\Campaign\Models\CampaignWebhook;

class WebhookController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = CampaignWebhook::query();

        if ($request->filled('campaign_uid')) {
            $campaign = Campaign::findByUid($request->campaign_uid);
            if ($campaign) {
                $query->where('campaign_id', $campaign->id);
            }
        }

        return response()->json([
            'data' => $query->orderBy('created_at', 'desc')->paginate(50),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'campaign_uid' => ['required', 'string'],
            'name' => ['nullable', 'string', 'max:255'],
            'event' => ['required', 'string', 'max:64'],
            'url' => ['required', 'url', 'max:191'],
            'method' => ['nullable', 'string', 'in:POST,PUT,PATCH,GET'],
            'headers' => ['nullable', 'array'],
            'enabled' => ['boolean'],
            'secret' => ['nullable', 'string', 'max:128'],
        ]);

        $campaign = Campaign::where('uid', $data['campaign_uid'])->first();
        if (! $campaign) {
            return response()->json(['message' => 'Campaign not found.'], 404);
        }

        $webhook = CampaignWebhook::create([
            'uid' => (string) Str::uuid(),
            'campaign_id' => $campaign->id,
            'name' => $data['name'] ?? null,
            'event' => $data['event'],
            'url' => $data['url'],
            'method' => $data['method'] ?? 'POST',
            'headers' => $data['headers'] ?? null,
            'enabled' => $data['enabled'] ?? true,
            'secret' => $data['secret'] ?? null,
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $webhook,
        ], 201);
    }

    public function show(string $uid): JsonResponse
    {
        $webhook = CampaignWebhook::where('uid', $uid)->firstOrFail();

        return response()->json(['data' => $webhook]);
    }

    public function update(Request $request, string $uid): JsonResponse
    {
        $webhook = CampaignWebhook::where('uid', $uid)->firstOrFail();

        $data = $request->validate([
            'name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'event' => ['sometimes', 'string', 'max:64'],
            'url' => ['sometimes', 'url', 'max:191'],
            'method' => ['sometimes', 'string', 'in:POST,PUT,PATCH,GET'],
            'headers' => ['sometimes', 'nullable', 'array'],
            'enabled' => ['sometimes', 'boolean'],
            'secret' => ['sometimes', 'nullable', 'string', 'max:128'],
        ]);

        $webhook->update($data);

        return response()->json([
            'status' => 'success',
            'data' => $webhook,
        ]);
    }

    public function delete(string $uid): JsonResponse
    {
        $webhook = CampaignWebhook::where('uid', $uid)->firstOrFail();
        $webhook->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Webhook deleted.',
        ]);
    }

    public function toggle(string $uid): JsonResponse
    {
        $webhook = CampaignWebhook::where('uid', $uid)->firstOrFail();
        $webhook->update(['enabled' => ! $webhook->enabled]);

        return response()->json([
            'status' => 'success',
            'data' => $webhook,
        ]);
    }

    public function test(string $uid): JsonResponse
    {
        $webhook = CampaignWebhook::where('uid', $uid)->firstOrFail();

        $payload = [
            'event' => $webhook->event,
            'campaign_uid' => $webhook->campaign?->uid,
            'campaign_id' => $webhook->campaign_id,
            'subscriber_uid' => 'test-subscriber',
            'email' => 'test@example.com',
            'message_id' => 'test-message-id',
            'timestamp' => now()->toIso8601String(),
        ];

        WebhookDispatcher::emit($webhook->campaign, $webhook->event, $payload);

        return response()->json([
            'status' => 'success',
            'message' => 'Test webhook dispatched.',
        ]);
    }
}
