<?php

namespace Modules\Remarketing\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Remarketing\Services\EngagementBridgeService;

/**
 * Exposes engagement-derived audiences for Remarketing campaigns:
 *  • hot visitors (score >= 60)
 *  • cart abandoners
 *  • visitors with specific event firing
 */
class EngagementSegmentController extends Controller
{
    public function __construct(
        private readonly EngagementBridgeService $engagement,
    ) {
        $this->middleware('can:remarketing.segments.view')->only('hot', 'cartAbandoners', 'byEvent');
    }

    public function status(): JsonResponse
    {
        return response()->json([
            'available' => $this->engagement->isAvailable(),
            'message' => $this->engagement->isAvailable()
                ? 'Engagement módulo activo — segmentos en tiempo real disponibles.'
                : 'Engagement módulo no disponible — segmentos basados sólo en datos de remarketing.',
        ]);
    }

    public function hot(Request $request): JsonResponse
    {
        $hours = (int) $request->input('hours', 24);
        $rows = $this->engagement->hotVisitorsLast(max(1, min(168, $hours)));

        return response()->json([
            'success' => true,
            'data' => [
                'count' => $rows->count(),
                'visitors' => $rows->map(fn ($r) => [
                    'customer_id' => $r->customer_id,
                    'session_token' => substr((string) $r->session_token, 0, 8).'…',
                    'score' => $r->score,
                    'segment' => $r->segment,
                    'last_activity' => $r->updated_at?->toIso8601String(),
                ]),
            ],
        ]);
    }

    public function cartAbandoners(Request $request): JsonResponse
    {
        $hours = (int) $request->input('hours', 24);
        $customerIds = $this->engagement->customersWhoAbandonedCart(max(1, min(168, $hours)));

        return response()->json([
            'success' => true,
            'data' => [
                'count' => $customerIds->count(),
                'customer_ids' => $customerIds->all(),
            ],
        ]);
    }

    public function byEvent(Request $request): JsonResponse
    {
        $request->validate([
            'event' => ['required', 'string', 'max:100'],
            'hours' => ['integer', 'min:1', 'max:168'],
        ]);

        $customerIds = $this->engagement->customersWithEvent(
            $request->input('event'),
            (int) $request->input('hours', 24),
        );

        return response()->json([
            'success' => true,
            'data' => [
                'count' => $customerIds->count(),
                'event' => $request->input('event'),
                'customer_ids' => $customerIds->all(),
            ],
        ]);
    }
}
