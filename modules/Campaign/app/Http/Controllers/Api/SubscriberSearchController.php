<?php

namespace Modules\Campaign\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Campaign\Models\CampaignSubscriber;

class SubscriberSearchController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $query = CampaignSubscriber::query();

        // Text search across email, first_name, last_name
        if ($request->filled('q')) {
            $term = '%'.addcslashes($request->q, '%_').'%';
            $query->where(function ($q) use ($term): void {
                $q->where('email', 'like', $term)
                    ->orWhere('first_name', 'like', $term)
                    ->orWhere('last_name', 'like', $term);
            });
        }

        // Filter by subscription status
        if ($request->filled('status')) {
            match ($request->status) {
                'subscribed' => $query->whereNull('unsubscribed_at'),
                'unsubscribed' => $query->whereNotNull('unsubscribed_at'),
                default => null,
            };
        }

        // Filter by country (stored in attributes JSON)
        if ($request->filled('country')) {
            $query->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(attributes, '$.country')) = ?", [$request->country]);
        }

        // Filter by source
        if ($request->filled('source')) {
            $query->where('source', $request->source);
        }

        // Filter by verification status
        if ($request->filled('verification_status')) {
            $query->where('verification_status', $request->verification_status);
        }

        // Date range on subscribed_at
        if ($request->filled('subscribed_since')) {
            $query->whereDate('subscribed_at', '>=', $request->subscribed_since);
        }
        if ($request->filled('subscribed_until')) {
            $query->whereDate('subscribed_at', '<=', $request->subscribed_until);
        }

        // Engagement score filter (requires subscriber_engagement_scores table)
        if ($request->filled('engagement')) {
            $engagementMap = ['hot' => 80, 'warm' => 50, 'cold' => 20];
            $minScore = $engagementMap[$request->engagement] ?? 0;
            $query->whereExists(function ($sub) use ($minScore): void {
                $sub->select(DB::raw(1))
                    ->from('campaign_subscriber_engagement_scores')
                    ->whereColumn('campaign_subscriber_engagement_scores.subscriber_id', 'campaign_subscribers.id')
                    ->where('campaign_subscriber_engagement_scores.score', '>=', $minScore);
            });
        }

        // Custom attribute filter: ?attr_city=Madrid
        foreach ($request->all() as $key => $value) {
            if (str_starts_with($key, 'attr_')) {
                $attrKey = substr($key, 5);
                $query->whereRaw('JSON_UNQUOTE(JSON_EXTRACT(attributes, ?)) = ?', ['$.'.$attrKey, $value]);
            }
        }

        $perPage = min(100, max(1, (int) $request->input('per_page', 25)));

        $results = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'data' => $results,
        ]);
    }
}
