<?php

namespace App\Http\Controllers\Warehouses;

use App\Http\Controllers\Controller;
use App\Models\Enterprise\Enterprise;
use App\Models\Subscriber;
use App\Structure\Elements;

class DashboardController extends Controller
{
    public function dashboard(){

        return view('warehouses.views.dashboard.index')->with([
        ]);

    }

}
