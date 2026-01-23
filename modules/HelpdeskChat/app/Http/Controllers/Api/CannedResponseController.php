<?php

namespace Modules\HelpdeskChat\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\HelpdeskChat\Models\Canneds\Canned;

class CannedResponseController extends Controller
{
    /**
     * Search canned responses (for autocomplete in chat).
     *
     * GET /api/canneds/search
     */
    public function search(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $query = $request->get('q', '');
        $limit = min((int) $request->get('limit', 10), 50);

        // Get canned responses accessible by this user
        $cannedResponses = Canned::query()
            ->where('account_id', $user->account_id)
            ->accessibleBy($user)
            ->when($query, function ($q) use ($query) {
                $q->search($query);
            })
            ->orderByRaw('CASE WHEN short_code LIKE ? THEN 1 ELSE 2 END', ["{$query}%"])
            ->orderBy('usage_count', 'desc')
            ->orderBy('title', 'asc')
            ->limit($limit)
            ->get()
            ->map(function ($canned) {
                return [
                    'id' => $canned->id,
                    'short_code' => $canned->short_code,
                    'title' => $canned->title,
                    'description' => $canned->description,
                    'content' => $canned->content,
                    'visibility' => $canned->visibility,
                    'visibility_label' => $canned->visibility_label,
                    'usage_count' => $canned->usage_count,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'results' => $cannedResponses,
                'query' => $query,
                'count' => $cannedResponses->count(),
            ],
        ]);
    }

    /**
     * Get a specific canned response.
     *
     * GET /api/canneds/{id}
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $user = Auth::user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        // Find canned response
        $canned = Canned::where('id', $id)
            ->where('account_id', $user->account_id)
            ->first();

        if (! $canned) {
            return response()->json([
                'success' => false,
                'message' => 'Canned response not found',
            ], 404);
        }

        // Check if user has access
        $hasAccess = $canned->visibility === 'everyone'
            || ($canned->visibility === 'personal' && $canned->user_id === $user->id)
            || ($canned->visibility === 'team' && $canned->user_id === $user->id); // Team visibility check

        if (! $hasAccess) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied to this canned response',
            ], 403);
        }

        // Record usage
        $canned->recordUsage();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $canned->id,
                'short_code' => $canned->short_code,
                'title' => $canned->title,
                'description' => $canned->description,
                'content' => $canned->content,
                'visibility' => $canned->visibility,
                'visibility_label' => $canned->visibility_label,
                'usage_count' => $canned->usage_count,
                'last_used_at' => $canned->last_used_at?->toIso8601String(),
                'available_variables' => Canned::getAvailableVariables(),
            ],
        ]);
    }
}
