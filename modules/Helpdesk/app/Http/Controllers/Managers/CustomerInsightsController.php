<?php

namespace Modules\Helpdesk\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Services\CustomerInsightsService;

class CustomerInsightsController extends Controller
{
    public function __construct(
        private readonly CustomerInsightsService $insights
    ) {
        $this->middleware('can:helpdesk.customers.view');
    }

    public function show(Customer $customer): JsonResponse
    {
        $this->authorize('view', $customer);

        return response()->json([
            'health_score' => $this->insights->healthScore($customer),
            'lifetime_metrics' => $this->insights->lifetimeMetrics($customer),
            'journey_timeline' => $this->insights->journeyTimeline($customer),
        ]);
    }
}
