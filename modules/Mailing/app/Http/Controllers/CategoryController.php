<?php

namespace Modules\Mailing\Http\Controllers\Site;

use Illuminate\Http\Request;
use Modules\Mailing\Http\Controllers\Controller;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        return view('site.categories.index');
    }
}
