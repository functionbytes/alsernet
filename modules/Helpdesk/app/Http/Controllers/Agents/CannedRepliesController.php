<?php

namespace Modules\Helpdesk\Http\Controllers\Agents;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Helpdesk\Models\CannedReply;

class CannedRepliesController extends Controller
{
    /**
     * Search canned replies for the autocomplete widget.
     * Returns global replies + replies owned by the current agent.
     */
    public function search(Request $request): JsonResponse
    {
        $query = CannedReply::forUser(auth()->id());

        if ($request->filled('q')) {
            $query->search($request->q);
        }

        if ($request->filled('category')) {
            $query->byCategory($request->category);
        }

        $replies = $query
            ->orderByDesc('usage_count')
            ->limit(20)
            ->get(['id', 'title', 'body', 'html_body', 'category', 'shortcut', 'usage_count']);

        return response()->json($replies);
    }
}
