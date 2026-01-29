<?php

namespace Modules\Mailing\Http\Controllers\Settings;

use Modules\Mailing\Http\Controllers\Controller;

class ApiController extends Controller
{
    /**
     * Docs for api v1 - Admin.
     *
     * @return \Illuminate\Http\Response
     */
    public function doc()
    {
        return view('docs.api.v1', ['view' => 'backend']);
    }
}
