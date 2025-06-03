<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Return;

class CheckReturnStatus
{
    public function handle($request, Closure $next)
    {
        $return = $request->route('return');

        if ($return instanceof Return) {
        // No permitir cambios en costos si la devolución está cerrada
        if (in_array($return->status, ['completed', 'cancelled'])) {
            if ($request->wantsJson()) {
                return response()->json([
                    'error' => 'No se pueden modificar costos en una devolución cerrada'
                ], 403);
            }

            return redirect()
                ->route('returns.show', $return)
                ->withErrors(['error' => 'No se pueden modificar costos en una devolución cerrada']);
        }
    }

        return $next($request);
    }
}
