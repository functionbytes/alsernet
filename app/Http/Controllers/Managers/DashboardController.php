<?php

namespace App\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use App\Models\Enterprise\Enterprise;
use App\Models\Newsletter;
use App\Structure\Elements;

class DashboardController extends Controller
{
    public function dashboard(){


        return view('managers.views.dashboard.index')->with([
        ]);

    }

}
