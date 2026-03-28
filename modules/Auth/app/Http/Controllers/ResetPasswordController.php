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

    public function showResetForm(Request $request, string $token): \Illuminate\View\View
    {
        return view('auth::passwords.reset')->with([
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    public function reset(Request $request)
    {
        if ($request->password !== $request->password_confirmation) {
            return redirect()->back()
                ->withErrors(['password' => 'Las contraseñas no coinciden.']);
        }

        $user = User::where('email', $request->email)->first();

        if ($user === null) {
            return redirect()->back()
                ->withErrors(['password' => 'No se encontró una cuenta con ese correo electrónico.']);
        }

        if (! Hash::check($request->token, $user->password_reset_token)) {
            return redirect()->back()
                ->withErrors(['password' => 'El enlace de restablecimiento es inválido o ha expirado.']);
        }

        $user->password = Hash::make($request->password);
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

    protected function guard()
    {
        return Auth::guard();
    }
}
