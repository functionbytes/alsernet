<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class ValidationController extends Controller
{
    protected $redirectTo = '/home';

    public function show(): View
    {
        return view('auth::auth.validation');
    }
}
