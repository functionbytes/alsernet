<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\MailHelper;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Foundation\Auth\RedirectsUsers;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Events\Auth\UserLoggedOut;
use App\Events\Auth\UserLoggedIn;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

class LoginController extends Controller
{
    use AuthenticatesUsers, RedirectsUsers, ValidatesRequests;

    protected $redirectTo = '/login';

    public function showLoginForm(){


        MailHelper::sendMail('revoxservices@gmail.com', 'Correo de Prueba', 'Hola, este es un correo enviado sin SMTP.');

        if($this->guard()->check()){
            return $this->guard()->user()->redirect();
        }else{
            return view('auth.login');
        }
    }

    public function login(Request $request)
    {

        $this->validateLogin($request);

        if ($this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);
            return response()->json([
                'success' => false,
                'message' => 'Demasiados intentos de inicio de sesión. Inténtelo de nuevo más tarde.'
            ], 429);
        }

        if ($this->attemptLogin($request)) {
            return $this->sendLoginResponse($request);
        }

        $this->incrementLoginAttempts($request);

        return $this->sendFailedLoginResponse($request);
    }

    protected function attemptLogin(Request $request)
    {
        return $this->guard()->attempt(
            $this->credentials($request), $request->remember
        );
    }

    protected function sendLoginResponse(Request $request)
    {

        if ($this->guard()->user()->available) {

            return response()->json([
                'success' => true,
                'message' => 'Inicio de sesión exitoso.',
                'redirect' => route($this->guard()->user()->redirect())
            ]);

        } else {

            $this->guard()->logout();
            $request->session()->invalidate();

            return response()->json([
                'success' => false,
                'message' => 'Tu cuenta está deshabilitada. Contacta al administrador.'
            ]);
        }
    }

    protected function sendFailedLoginResponse(Request $request)
    {
        if (!User::where('email', $request->email)->first()) {
            return response()->json([
                'success' => false,
                'message' => "El correo o cédula no coincide con nuestros registros."
            ]);
        }

        if (!Hash::check($request->password, User::where('email', $request->email)->first()->password)) {
            return response()->json([
                'success' => false,
                'message' => "La contraseña ingresada no es válida."
            ]);
        }
    }

    protected function validateLogin(Request $request){

        $validator = Validator::make($request->all(), [
            $this->username() => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput($request->only($this->username(), 'remember'));
        }
    }

    protected function credentials(Request $request)
    {
        $login = $request->input($this->username());
        $field = 'email';
        return [
            $field => $login,
            'password' => $request->input('password'),
        ];
    }


    public function logout(Request $request){
        $this->guard()->logout();
        $user = $request->user();
        //event(new UserLoggedOut($user));
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');

    }

    public function username(){
        return 'email';
    }

    protected function guard(){
        return Auth::guard();
    }

}



