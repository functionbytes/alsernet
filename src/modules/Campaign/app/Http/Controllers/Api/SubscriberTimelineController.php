<?php

namespace Modules\Campaign\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Modules\Campaign\Models\CampaignSubscriber;

class SubscriberTimelineController extends Controller
{
    public function timeline(string $uid): JsonResponse
    {
        $subscriber = CampaignSubscriber::where('uid', $uid)->firstOrFail();

        $timeline = [];

        // Suscripción
        $pivots = DB::table('campaign_maillists_subscribers')
            ->where('subscriber_id', $subscriber->id)
            ->join('campaign_maillists', 'campaign_maillists.id', '=', 'campaign_maillists_subscribers.mail_list_id')
            ->select('campaign_maillists.name', 'campaign_maillists_subscribers.status', 'campaign_maillists_subscribers.subscribed_at', 'campaign_maillists_subscribers.unsubscribed_at')
            ->get();

        foreach ($pivots as $p) {
            $timeline[] = [
                'type' => 'subscription',
                'list' => $p->name,
                'status' => $p->status,
                'at' => $p->subscribed_at,
            ];
            if ($p->unsubscribed_at) {
                $timeline[] = [
                    'type' => 'unsubscription',
                    'list' => $p->name,
                    'at' => $p->unsubscribed_at,
                ];
            }
        }

        // Tracking logs
        $logs = DB::table('campaign_tracking_logs')
            ->where('subscriber_id', $subscriber->id)
            ->join('campaigns', 'campaigns.id', '=', 'campaign_tracking_logs.campaign_id')
            ->select('campaigns.name as campaign_name', 'campaign_tracking_logs.status', 'campaign_tracking_logs.created_at', 'campaign_tracking_logs.error')
            ->orderByDesc('campaign_tracking_logs.created_at')
            ->limit(100)
            ->get();

        foreach ($logs as $log) {
            $timeline[] = [
                'type' => 'delivery',
                'campaign' => $log->campaign_name,
                'status' => $log->status,
                'error' => $log->error,
                'at' => $log->created_at,
            ];
        }

        // Opens
        $opens = DB::table('campaign_open_logs')
            ->join('campaign_tracking_logs', 'campaign_tracking_logs.id', '=', 'campaign_open_logs.tracking_log_id')
            ->join('campaigns', 'campaigns.id', '=', 'campaign_tracking_logs.campaign_id')
            ->where('campaign_tracking_logs.subscriber_id', $subscriber->id)
            ->select('campaigns.name as campaign_name', 'campaign_open_logs.created_at', 'campaign_open_logs.ip', 'campaign_open_logs.country')
            ->orderByDesc('campaign_open_logs.created_at')
            ->limit(100)
            ->get();

        foreach ($opens as $o) {
            $timeline[] = [
                'type' => 'open',
                'campaign' => $o->campaign_name,
                'ip' => $o->ip,
                'country' => $o->country,
                'at' => $o->created_at,
            ];
        }

        // Clicks
        $clicks = DB::table('campaign_click_logs')
            ->join('campaign_tracking_logs', 'campaign_tracking_logs.id', '=', 'campaign_click_logs.tracking_log_id')
            ->join('campaigns', 'campaigns.id', '=', 'campaign_tracking_logs.campaign_id')
            ->where('campaign_tracking_logs.subscriber_id', $subscriber->id)
            ->select('campaigns.name as campaign_name', 'campaign_click_logs.created_at', 'campaign_click_logs.ip')
            ->orderByDesc('campaign_click_logs.created_at')
            ->limit(100)
            ->get();

        foreach ($clicks as $c) {
            $timeline[] = [
                'type' => 'click',
                'campaign' => $c->campaign_name,
                'ip' => $c->ip,
                'at' => $c->created_at,
            ];
        }

        // Ordenar cronológicamente
        usort($timeline, fn ($a, $b) => strcmp($b['at'] ?? '', $a['at'] ?? ''));

        return response()->json([
            'subscriber' => [
                'uid' => $subscriber->uid,
                'email' => $subscriber->email,
                'first_name' => $subscriber->first_name,
                'last_name' => $subscriber->last_name,
                'subscribed_at' => $subscriber->subscribed_at,
            ],
            'timeline' => $timeline,
        ]);
    }
}
