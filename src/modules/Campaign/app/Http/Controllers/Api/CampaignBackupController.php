<?php

namespace Modules\Campaign\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\Campaign\Models\Campaign;
use Modules\Campaign\Models\CampaignWebhook;
use Modules\Campaign\Models\MailList;

class CampaignBackupController extends Controller
{
    public function export(string $uid): JsonResponse
    {
        $campaign = Campaign::with([
            'mailLists',
            'campaignWebhooks',
        ])->where('uid', $uid)->firstOrFail();

        $payload = [
            'version' => '1.0',
            'exported_at' => now()->toIso8601String(),
            'campaign' => [
                'name' => $campaign->name,
                'subject' => $campaign->subject,
                'from_email' => $campaign->from_email,
                'from_name' => $campaign->from_name,
                'reply_to' => $campaign->reply_to,
                'html' => $campaign->html,
                'plain' => $campaign->plain,
                'type' => $campaign->type,
                'status' => $campaign->status,
                'run_at' => $campaign->run_at,
                'delivery_at' => $campaign->delivery_at,
                'tags' => $campaign->tags,
                'notes' => $campaign->notes,
            ],
            'mail_lists' => $campaign->mailLists->map(fn ($list) => [
                'uid' => $list->uid,
                'name' => $list->name,
            ])->all(),
            'webhooks' => $campaign->campaignWebhooks->map(fn ($wh) => [
                'name' => $wh->name,
                'event' => $wh->event,
                'url' => $wh->url,
                'method' => $wh->method,
                'headers' => $wh->headers,
                'enabled' => $wh->enabled,
                'secret' => $wh->secret,
            ])->all(),
        ];

        return response()->json([
            'status' => 'success',
            'data' => $payload,
        ]);
    }

    public function import(Request $request): JsonResponse
    {
        $data = $request->validate([
            'payload' => ['required', 'array'],
            'payload.campaign' => ['required', 'array'],
            'payload.campaign.name' => ['required', 'string', 'max:255'],
        ]);

        $payload = $data['payload'];
        $campaignData = $payload['campaign'];

        $campaign = Campaign::create(array_merge($campaignData, [
            'uid' => (string) Str::uuid(),
            'status' => 'new',
        ]));

        // Re-attach mail lists by uid if they exist
        if (! empty($payload['mail_lists'])) {
            $listUids = collect($payload['mail_lists'])->pluck('uid')->filter()->all();
            $listIds = MailList::whereIn('uid', $listUids)->pluck('id')->all();
            if (! empty($listIds)) {
                $campaign->mailLists()->sync($listIds);
            }
        }

        // Re-create webhooks
        if (! empty($payload['webhooks'])) {
            foreach ($payload['webhooks'] as $wh) {
                CampaignWebhook::create(array_merge($wh, [
                    'uid' => (string) Str::uuid(),
                    'campaign_id' => $campaign->id,
                ]));
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'campaign_uid' => $campaign->uid,
            ],
        ], 201);
    }
}
