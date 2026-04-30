<?php

namespace Modules\Chat\Http\Controllers\Helpdesk\Reports;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Chat\Http\Controllers\Controller;
use Modules\Chat\Services\Analytics\SlaReportService;

class SlaReportController extends Controller
{
    public function index(Request $request): View
    {
        $accountId = auth()->user()->account_id;
        abort_unless($accountId, 403, 'User must belong to an account');

        $period = (int) $request->input('period', 30);
        $slaService = new SlaReportService($accountId);
        $policies = $slaService->getPolicyMetrics($period);

        return view('Chat::chats.reports.sla.index', compact('policies', 'period'));
    }
}
