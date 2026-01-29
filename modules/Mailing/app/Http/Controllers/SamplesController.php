<?php

namespace Modules\Mailing\Http\Controllers;

class SamplesController extends Controller
{
    public function index()
    {
        return view('samples.index');
    }
}
