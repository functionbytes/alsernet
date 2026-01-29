<?php

namespace Modules\Mailing\Http\Controllers;

use Illuminate\Http\Request;

class AdminController extends Controller
{
    /**
     * Log in back user.
     *
     *
     * @return \Illuminate\Http\Response
     */
    public function loginBack(Request $request)
    {
        $id = \Session::pull('orig_user_id');
        $orig_user = \App\Models\User::findByUid($id);

        \Auth::login($orig_user);

        return redirect()->action('Admin\UserController@index');
    }
}
