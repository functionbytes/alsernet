<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;

class ValidationController extends Controller
{
    protected $redirectTo = '/home';

    public function validation()
    {
        return view('auth.validation');
    }

}
