<?php

namespace Modules\Helpdesk\Http\Controllers\Managers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
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
                        ->orWhere('short_code', 'like', "%{$q}%")
                        ->orWhere('content', 'like', "%{$q}%");
                });
            })
            ->orderByDesc('usage_count')
            ->limit(10)
            ->get(['id', 'title', 'short_code', 'content']);

        return response()->json(
            $replies->map(fn ($reply) => [
                'id' => $reply->id,
                'name' => $reply->title,
                'shortcut' => $reply->short_code,
                'body' => $reply->content,
            ])
        );
    }
}
