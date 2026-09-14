<?php

namespace Modules\Helpdesk\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
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
        $days = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];

        [$matrix, $maxCount] = Cache::remember('helpdesk:reports:heatmap', 1800, function () {
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

            return [$matrix, $rows->max('cnt') ?: 1];
        });

        return view('helpdesk::helpdesk.reports.heatmap', compact('matrix', 'maxCount', 'days'));
    }
}
