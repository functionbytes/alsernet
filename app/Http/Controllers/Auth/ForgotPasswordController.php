<?php

namespace App\Http\Controllers\Auth;


use App\Events\Auth\Password\ForgotPasswordCreated;
use Illuminate\Support\Facades\Password;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Str;

class ForgotPasswordController extends Controller
{

    public function showLinkRequest(){
        return view('auth.passwords.email');
    }

    public function sendResetLinkEmail(Request $request){

        $user = User::orWhere('email', $request->email)->orWhere('identification', $request->email)->get();

        $email = $request->email;

        if (count($user)==0) {

            return redirect()->back()
                ->withInput($request->only($this->username(), 'remember'))
                ->withErrors([
                    $this->username() => "El correo o cedula  no coincide con nuestros registros". $email,
                ]);

        }else{

            $user = $user->first();
            $reset_tries = 0;

            if ($user->password_reset_last_tried_on != "") {

                $current_date = date("Y-m-d");
                $last_tried_date = date("Y-m-d", strtotime($user->password_reset_last_tried_on));

                if ($last_tried_date == $current_date && $user->password_reset_max_tries >= 3) {
                    return redirect()->back()
                        ->withInput($request->only($this->username(), 'remember'))
                        ->withErrors([
                            'tried' => "Ya lo has probado 3 veces hoy. Comuníquese con el administrador para restablecer la contraseña.",
                        ]);

                }

                if ($last_tried_date == $current_date && $user->password_reset_max_tries < 3) {
                    $reset_tries = $user->password_reset_max_tries + 1;
                } else if ($last_tried_date != $current_date) {
                    $reset_tries = $reset_tries + 1;
                }

            } else {
                $reset_tries = $reset_tries + 1;
            }

            $password_token = Str::random(50);

            $user->password_reset_token = $password_token;
            $user->password_reset_max_tries = $reset_tries;
            $user->password_reset_last_tried_on = now();
            $user->save();

            event(new ForgotPasswordCreated($user));

            return view('auth.passwords.success')->with([
                'email' => $user->email
            ]);

        }

    }

    protected function validateEmail(Request $request){
        $request->validate(['email' => 'required|email']);

    }

    protected function credentials(Request $request){
        return $request->only('email');
    }

    protected function sendResetLinkResponse(Request $request, $response){
        return view('auth.passwords.success');
    }

    protected function sendResetLinkFailedResponse(Request $request, $response){
        if ( ! User::where('email', $request->email)->first() ) {
            return redirect()->back()
                ->withInput($request->only($this->username(), 'remember'))
                ->withErrors([
                    $this->username() => "El correo o cedula  no coincide con nuestros registros.",
                ]);
        }
    }

    public function username(){
        return 'email';
    }

    public function broker(){
        return Password::broker();
    }

}
