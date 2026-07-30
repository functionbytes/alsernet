<?php

namespace Modules\Helpdesk\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Helpdesk\Http\Requests\SearchCannedRepliesRequest;

class CannedRepliesController extends Controller
{
    public function search(SearchCannedRepliesRequest $request): JsonResponse
    {
        $q = $request->validated()['q'] ?? null;

        $replies = \DB::connection('helpdesk')
            ->table('helpdesk_canned_replies')
            ->whereNull('deleted_at')
            ->when($q, function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('title', 'like', "%{$q}%")
                        ->orWhere('shortcut', 'like', "%{$q}%")
                        ->orWhere('body', 'like', "%{$q}%");
                });
            })
            ->orderByDesc('usage_count')
            ->limit(50)
            ->get(['id', 'title', 'shortcut', 'body', 'category', 'usage_count']);

        return response()->json(
            $replies->map(fn ($reply) => [
                'id' => $reply->id,
                'name' => $reply->title,
                'shortcut' => $reply->shortcut,
                'body' => $reply->body,
                'category' => $reply->category,
                'usage_count' => (int) $reply->usage_count,
            ])
        );
    }
}
