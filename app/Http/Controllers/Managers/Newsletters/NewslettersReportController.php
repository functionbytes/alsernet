<?php

namespace App\Http\Controllers\Managers\Newsletters;

use App\Exports\Managers\Newsletters\NewsletterListExport;
use App\Models\Newsletter\NewsletterList;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Carbon\Carbon;

class NewslettersReportController extends Controller
{
    public function report(){

        $lists = NewsletterList::available()->get();
        $lists = $lists->pluck('title', 'id');
        $lists->prepend('Todos', '0');

        return view('managers.views.newsletters.lists.reports')->with([
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
