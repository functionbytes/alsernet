<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Return\ReturnRequest;

class CheckReturnAccess
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        $id = $request->route('id');

        $return = ReturnRequest::where('id_return_request', $id)->first();

        if (!$return) {
            abort(404, 'Devolución no encontrada.');
        }

        // Validar si el usuario tiene acceso
        $hasAccess = false;

        if ($user->id_customer && $return->id_customer === $user->id_customer) {
            $hasAccess = true;
        }

        if ($user->email && $return->email === $user->email) {
            $hasAccess = true;
        }

        if (!$hasAccess) {
            abort(403, 'No tienes permiso para acceder a esta devolución.');
        }

        return $next($request);
    }
}
