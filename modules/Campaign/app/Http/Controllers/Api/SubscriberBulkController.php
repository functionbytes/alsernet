<?php

namespace Modules\Campaign\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Campaign\Models\CampaignMaillist;
use Modules\Campaign\Models\CampaignSubscriber;

class SubscriberBulkController extends Controller
{
    public function bulk(Request $request): JsonResponse
    {
        $data = $request->validate([
            'action' => ['required', 'string', 'in:move_list,copy_list,tag,untag,delete,unsubscribe'],
            'subscriber_uids' => ['required', 'array', 'min:1'],
            'subscriber_uids.*' => ['required', 'string', 'exists:campaign_subscribers,uid'],
            'target_list_uid' => ['required_if:action,move_list,copy_list', 'nullable', 'string', 'exists:campaign_maillists,uid'],
            'tags' => ['required_if:action,tag,untag', 'nullable', 'array'],
            'tags.*' => ['string', 'max:64'],
        ]);

        $subscribers = CampaignSubscriber::whereIn('uid', $data['subscriber_uids'])->get();
        $affected = 0;

        DB::transaction(function () use ($subscribers, $data, &$affected): void {
            foreach ($subscribers as $subscriber) {
                match ($data['action']) {
                    'move_list' => $this->moveToList($subscriber, $data['target_list_uid']),
                    'copy_list' => $this->copyToList($subscriber, $data['target_list_uid']),
                    'tag' => $this->addTags($subscriber, $data['tags'] ?? []),
                    'untag' => $this->removeTags($subscriber, $data['tags'] ?? []),
                    'delete' => $subscriber->delete(),
                    'unsubscribe' => $subscriber->update(['unsubscribed_at' => now()]),
                    default => null,
                };
                $affected++;
            }
        });

        return response()->json([
            'status' => 'success',
            'message' => "{$affected} subscriber(s) processed with action: {$data['action']}",
            'data' => [
                'action' => $data['action'],
                'affected' => $affected,
            ],
        ]);
    }

    private function moveToList(CampaignSubscriber $subscriber, string $listUid): void
    {
        $list = CampaignMaillist::where('uid', $listUid)->firstOrFail();
        $subscriber->mailLists()->sync([$list->id]);
    }

    private function copyToList(CampaignSubscriber $subscriber, string $listUid): void
    {
        $list = CampaignMaillist::where('uid', $listUid)->firstOrFail();
        $subscriber->mailLists()->syncWithoutDetaching([$list->id]);
    }

    private function addTags(CampaignSubscriber $subscriber, array $tags): void
    {
        if (empty($tags)) {
            return;
        }

        $attributes = $subscriber->attributes ?? [];
        $currentTags = $attributes['tags'] ?? [];
        $attributes['tags'] = array_values(array_unique(array_merge($currentTags, $tags)));
        $subscriber->attributes = $attributes;
        $subscriber->save();
    }

    private function removeTags(CampaignSubscriber $subscriber, array $tags): void
    {
        if (empty($tags)) {
            return;
        }

        $attributes = $subscriber->attributes ?? [];
        $currentTags = $attributes['tags'] ?? [];
        $attributes['tags'] = array_values(array_diff($currentTags, $tags));
        $subscriber->attributes = $attributes;
        $subscriber->save();
    }
}
