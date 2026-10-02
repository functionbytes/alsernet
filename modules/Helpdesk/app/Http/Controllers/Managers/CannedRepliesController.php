<?php

namespace Modules\Helpdesk\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Helpdesk\Http\Requests\SearchCannedRepliesRequest;
use Modules\Helpdesk\Models\CannedReply;

class CannedRepliesController extends Controller
{
    public function search(SearchCannedRepliesRequest $request): JsonResponse
    {
        $q = $request->validated()['q'] ?? null;
        $like = $q ? '%'.addcslashes($q, '%_\\').'%' : null;

        $replies = CannedReply::query()
            ->forUser($request->user()->id)
            ->when($like, function ($query) use ($like) {
                $query->where(function ($sub) use ($like) {
                    $sub->where('title', 'like', $like)
                        ->orWhere('shortcut', 'like', $like)
                        ->orWhere('body', 'like', $like);
                });
            })
            ->orderByDesc('usage_count')
            ->limit(50)
            ->get(['id', 'title', 'shortcut', 'body', 'category', 'usage_count']);

        return response()->json(
            $replies->map(fn (CannedReply $reply) => [
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
