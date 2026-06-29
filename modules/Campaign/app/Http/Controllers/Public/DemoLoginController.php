<?php

namespace Modules\Campaign\Http\Controllers\Public;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

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
        $token = (string) $request->query('token');
        if (! $token) {
            abort(400, 'Token requerido.');
        }

        // Token persistido en cache por el comando demo (one-shot, 1h validez).
        $payload = Cache::pull("campaign:demo-login:{$token}");
        if (! $payload || ! isset($payload['user_id'])) {
            abort(403, 'Token inválido o caducado. Regenera con `php artisan campaign:demo`.');
        }

        $user = User::find($payload['user_id']);
        if (! $user) {
            abort(404, 'Usuario no existe.');
        }

        Auth::login($user, remember: false);

        $redirect = $payload['redirect'] ?? '/panel/campaign/manager/templates/gallery';

        return redirect($redirect);
    }
}
