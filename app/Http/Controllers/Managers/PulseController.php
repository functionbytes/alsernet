<?php

namespace App\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use App\Models\Enterprise\Enterprise;
use App\Models\Product\Product;
use App\Models\Subscriber;
use App\Models\Product\ProductLocation;
use App\Structure\Elements;

class PulseController extends Controller
{
    public function dashboard(){

        return view('managers.views.pulse.dashboard')->with([
        ]);

    }

}
