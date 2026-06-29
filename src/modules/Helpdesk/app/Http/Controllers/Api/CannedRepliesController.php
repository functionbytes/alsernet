<?php

namespace Modules\Helpdesk\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Helpdesk\Http\Resources\CannedReplyResource;
use Modules\Helpdesk\Http\Responses\ApiResponse;
use Modules\Helpdesk\Models\CannedReply;

class CannedRepliesController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('helpdesk.cannedreplies.view');

        $replies = CannedReply::query()
            ->when($request->filled('q'), function ($query) use ($request) {
                $q = $request->q;
                $query->where(fn ($sub) => $sub->where('title', 'like', "%{$q}%")
                    ->orWhere('shortcut', 'like', "%{$q}%"));
            })
            ->orderByDesc('usage_count')
            ->limit(20)
            ->get(['id', 'title', 'shortcut', 'html_body', 'body', 'tags']);

        return ApiResponse::success(CannedReplyResource::collection($replies));
    }
}
