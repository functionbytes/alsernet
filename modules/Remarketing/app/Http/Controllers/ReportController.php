<?php

namespace Modules\Remarketing\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Remarketing\Models\Automation;
use Modules\Remarketing\Models\Campaign;
use Modules\Remarketing\Models\Store;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny-report');

        $user = auth()->user();

        $storeIds = Store::query()
            ->when(! $user->can('remarketing.manage'), fn ($q) => $q->where('user_id', $user->id))
            ->pluck('id');

        $campaigns = Campaign::query()
            ->whereIn('store_id', $storeIds)
            ->whereIn('status', ['sent', 'sending'])
            ->when($request->input('store_id'), fn ($q, $id) => $q->where('store_id', $id))
            ->select([
                'id', 'name', 'status', 'sent', 'delivered', 'opened',
                'clicked', 'bounced', 'unsubscribed', 'complained', 'revenue',
                'recipients_total', 'started_at', 'completed_at',
            ])
            ->latest('started_at')
            ->paginate(20);

        $automations = Automation::query()
            ->with(['store'])
            ->whereIn('store_id', $storeIds)
            ->withCount('runs')
            ->latest()
            ->get();

        $stores = Store::query()->whereIn('id', $storeIds)->get(['id', 'name']);

        return view('remarketing::reports.index', compact('campaigns', 'automations', 'stores'));
    }
}
