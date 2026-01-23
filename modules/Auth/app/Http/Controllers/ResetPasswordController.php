<?php

namespace Modules\Auth\Http\Controllers;

use App\Events\Auth\Password\ResetPasswordCreated;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ResetPasswordController extends Controller
{
    protected $redirectTo = '/home';

    public function __construct()
    {
        $this->middleware('guest');
    }

    public function showResetForm($uid)
    {

        return view('auth::passwords.reset')->with([
            'email' => $uid,
        ]);

    }

    public function reset(Request $request)
    {

        $new_password_hashed_password = bcrypt($request->new_password);
        $new_password_confirmation_hashed_password = bcrypt($request->new_password_confirmation);

        if (Hash::check($new_password_hashed_password, $new_password_confirmation_hashed_password) == false) {

            $user = User::orWhere('email', $request->email)->first();

            if ($user != null) {

                $user->password = $request->password;
                $user->remember_token = Str::random(60);
                $user->password_reset_token = null;
                $user->password_reset_max_tries = null;
                $user->password_reset_last_tried_on = null;
                $user->save();

                $user->sessions()->delete();

                ResetPasswordCreated::dispatch($user);

                return view('auth::passwords.confirm')->with([
                    'email' => $user->email,
                ]);
            }

        } else {
            return redirect()->back()
                ->withErrors([
                    'password' => 'Lo sentimos, parece que la contraseña que ingresó no es válida.',
                ]);
        }

    }

    protected function guard()
    {
        return Auth::guard();
    }
}
