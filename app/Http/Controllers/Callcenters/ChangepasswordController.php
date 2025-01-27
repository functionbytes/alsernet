<?php

namespace App\Http\Controllers\Callcenters;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Rules\MatchOldPassword;
use Hash, Auth;
use App\Models\User;

class ChangepasswordController extends Controller
{
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        return view('user.auth.passwords.changepassword');
    }



    public function changePassword(Request $request)
    {

        $request->validate([
          'current_password' => 'required|max:255',
          'password' => 'required|string|min:8|confirmed|max:255',
          'password_confirmation' => 'required|max:255',

        ]);
        if($request->current_password == $request->password){
            return back()->with('error', lang('Your new password can not be the same as your old password. Please choose a new password.', 'alerts'));
        }else{
            $user = Auth::user();
            if (Hash::check($request->current_password, $user->password)) {
                $user->password = Hash::make($request->password);
                $user->save();

                Auth::logout();
                $request->session()->flush();

                return  redirect()->route('login')->with('success', lang('The password has been successfully changed!', 'alerts'));
            }
            else{
                return back()->with('error', lang('The current password does not match!', 'alerts'));
            }
        }


    }
}
