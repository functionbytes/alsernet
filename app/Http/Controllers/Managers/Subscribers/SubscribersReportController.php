<?php

namespace App\Http\Controllers\Managers\Subscribers;

use App\Exports\Managers\Newsletters\NewsletterListExport;
use App\Models\Subscriber\SubscriberList;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Carbon\Carbon;

class SubscribersReportController extends Controller
{
    public function report(){

        $lists = SubscriberList::available()->get();
        $lists = $lists->pluck('title', 'id');
        $lists->prepend('Todos', '0');

        return view('managers.views.subscribers.lists.reports')->with([
            'lists' => $lists,
        ]);

    }

    public function generate(Request $request){

        $list  = $request->list;
        $date = explode(" - ", $request->range);
        $start = Carbon::parse($date[0])->startOfDay();
        $end = Carbon::parse($date[1])->endOfDay();

        return Excel::download(new NewsletterListExport($list,$start,$end), 'Reporte Listado.xlsx');

    }
}
