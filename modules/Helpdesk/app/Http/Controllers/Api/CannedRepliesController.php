<?php

namespace Modules\Helpdesk\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Helpdesk\Models\CannedReply;

class CannedRepliesController extends Controller
{
    /**
     * Search canned replies for inline insertion.
     *
     * GET /api/helpdesk/canned-replies?q=
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'nullable|string|min:1',
        ]);

        $query = CannedReply::query();

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sub) use ($q) {
                $sub->where('title', 'like', "%{$q}%")
                    ->orWhere('shortcut', 'like', "%{$q}%");
            });
        }

        $replies = $query->orderByDesc('usage_count')->limit(20)->get(['id', 'title', 'shortcut', 'html_body', 'body', 'tags']);

        $data = $replies->map(fn ($reply) => [
            'id' => $reply->id,
            'title' => $reply->title,
            'shortcut' => $reply->shortcut,
            'content' => substr(strip_tags($reply->html_body ?? $reply->body ?? ''), 0, 200),
            'tags' => $reply->tags,
        ]);

        return response()->json($data);
    }
}
