<?php

namespace Modules\Auth\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Modules\Auth\Jobs\DeleteUserAccountJob;

class AccountDeletionController extends Controller
{
    /**
     * Schedule the authenticated user's account for deletion.
     * Requires password confirmation and an exact typed confirmation string.
     */
    public function destroy(Request $request): JsonResponse
    {
        $request->validate([
            'password' => ['required', 'string'],
            'confirmation' => ['required', 'string', 'in:ELIMINAR MI CUENTA'],
        ], [
            'confirmation.in' => 'Debes escribir exactamente "ELIMINAR MI CUENTA" para confirmar.',
        ]);

        $user = $request->user();

        if (! Hash::check($request->input('password'), $user->password)) {
            return response()->json(['message' => 'Contraseña incorrecta.'], 422);
        }

        DeleteUserAccountJob::dispatch($user->id)->delay(now()->addMinutes(5));

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'success' => true,
            'message' => 'Tu cuenta será eliminada en los próximos minutos. Hemos cerrado tu sesión.',
            'redirect' => route('auth.login'),
        ]);
    }
}
