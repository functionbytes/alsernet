<?php

namespace Modules\Mailrelay\Http\Controllers\Web;

use App\Http\Controllers\Controller;

class ValidationController extends Controller
{
    /**
     * Show the email validation test interface
     */
    public function test()
    {
        return view('mailrelay::validation.test');
    }
}
