<?php

namespace Modules\Subscriber\Http\Controllers;

use App\Exports\Managers\Newsletters\NewsletterListExport;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Subscriber\Models\SubscriberList;

class SubscribersReportController extends Controller
{
    public function report()
    {

        $lists = SubscriberList::available()->get();
        $lists = $lists->pluck('title', 'id');
        $lists->prepend('Todos', '0');

        return view('theme.views.subscribers.lists.reports')->with([
            'lists' => $lists,
        ]);

    }

    public function generate(Request $request)
    {

        $list = $request->list;
        $date = explode(' - ', $request->range);
        $start = Carbon::parse($date[0])->startOfDay();
        $end = Carbon::parse($date[1])->endOfDay();

        return Excel::download(new NewsletterListExport($list, $start, $end), 'Reporte Listado.xlsx');

    }
}
