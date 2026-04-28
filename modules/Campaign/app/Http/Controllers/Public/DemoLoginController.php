<?php

namespace Modules\Campaign\Http\Controllers\Public;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

/**
 * Magic link de un solo uso para acceso rápido al builder demo sin
 * pedir credenciales. Ideal para presentaciones / pruebas locales.
 *
 * La URL la genera `php artisan campaign:demo-login` y se firma con
 * URL::temporarySignedRoute (1 hora de validez). Cualquier modificación
 * o caducidad rompe la firma y devuelve 403.
 */
class DemoLoginController extends Controller
{
    public function login(Request $request)
    {
        // Verifica firma URL (Laravel comprueba expira y HMAC)
        if (! $request->hasValidSignature()) {
            abort(403, 'Magic link inválido o caducado. Regenera con `php artisan campaign:demo-login`.');
        }

        $userId = (int) $request->query('user');
        $user = User::find($userId);

        if (! $user) {
            abort(404, 'Usuario no existe.');
        }

        Auth::login($user, remember: false);

        // Redirige al builder de la plantilla pasada (o a /panel/campaign si no)
        $redirect = $request->query('to', '/panel/campaign/manager/templates/gallery');

        return redirect($redirect);
    }
}
