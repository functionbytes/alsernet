<?php

namespace Modules\Helpdesk\Http\Controllers\Managers;

use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Helpdesk\Models\ConversationItem;

class HeatmapReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:helpdesk.reports.view');
    }

    public function index(): View
    {
        $rows = ConversationItem::query()
            ->whereNull('user_id')
            ->where('type', 'message')
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('DAYOFWEEK(created_at) as dow, HOUR(created_at) as hour, COUNT(*) as cnt')
            ->groupBy(DB::connection('helpdesk')->raw('DAYOFWEEK(created_at)'), DB::connection('helpdesk')->raw('HOUR(created_at)'))
            ->get();

        // Build a [dow][hour] matrix with counts
        $matrix = [];
        foreach ($rows as $row) {
            $matrix[$row->dow][$row->hour] = (int) $row->cnt;
        }

        $maxCount = $rows->max('cnt') ?: 1;

        $days = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];

        return view('helpdesk::managers.helpdesk.reports.heatmap', compact('matrix', 'maxCount', 'days'));
    }
}
