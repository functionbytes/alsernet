<?php

namespace Modules\Campaign\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Campaign\Models\CampaignSubscriber;

class DuplicateSubscriberController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = CampaignSubscriber::query()
            ->select('email', DB::raw('COUNT(*) as count'))
            ->groupBy('email')
            ->having('count', '>', 1)
            ->orderByDesc('count');

        if ($request->filled('q')) {
            $query->where('email', 'like', '%'.addcslashes($request->q, '%_').'%');
        }

        $perPage = min(100, max(1, (int) $request->input('per_page', 25)));
        $results = $query->paginate($perPage);

        return response()->json([
            'data' => $results,
        ]);
    }

    public function show(string $email): JsonResponse
    {
        $subscribers = CampaignSubscriber::where('email', $email)
            ->with('mailLists')
            ->get();

        return response()->json([
            'data' => [
                'email' => $email,
                'occurrences' => $subscribers->count(),
                'subscribers' => $subscribers,
            ],
        ]);
    }

    public function merge(Request $request): JsonResponse
    {
        $data = $request->validate([
            'target_uid' => ['required', 'string', 'exists:campaign_subscribers,uid'],
            'source_uids' => ['required', 'array', 'min:1'],
            'source_uids.*' => ['required', 'string', 'exists:campaign_subscribers,uid'],
        ]);

        $target = CampaignSubscriber::where('uid', $data['target_uid'])->firstOrFail();
        $sources = CampaignSubscriber::whereIn('uid', $data['source_uids'])
            ->where('id', '!=', $target->id)
            ->get();

        if ($sources->isEmpty()) {
            return response()->json(['message' => 'No source subscribers to merge.'], 422);
        }

        DB::transaction(function () use ($target, $sources): void {
            foreach ($sources as $source) {
                // Merge mail list memberships
                $targetListIds = $target->mailLists()->pluck('campaign_maillists.id')->all();
                $sourceListIds = $source->mailLists()->pluck('campaign_maillists.id')->all();
                $newListIds = array_diff($sourceListIds, $targetListIds);

                if (! empty($newListIds)) {
                    $target->mailLists()->attach($newListIds);
                }

                // Merge attributes (source overwrites only if target attribute is empty)
                $targetAttrs = $target->attributes ?? [];
                $sourceAttrs = $source->attributes ?? [];
                $mergedAttrs = array_merge($sourceAttrs, $targetAttrs);
                $target->attributes = $mergedAttrs;

                // Merge first/last name if target is empty
                if (empty($target->first_name) && ! empty($source->first_name)) {
                    $target->first_name = $source->first_name;
                }
                if (empty($target->last_name) && ! empty($source->last_name)) {
                    $target->last_name = $source->last_name;
                }

                $target->save();

                // Reassign tracking logs
                DB::table('campaign_tracking_logs')
                    ->where('subscriber_id', $source->id)
                    ->update(['subscriber_id' => $target->id]);

                // Delete source
                $source->delete();
            }
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Subscribers merged successfully',
            'data' => [
                'target_uid' => $target->uid,
                'merged_count' => $sources->count(),
            ],
        ]);
    }
}
