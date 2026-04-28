<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Ecommerce\Models\Customer;
use Symfony\Component\HttpFoundation\Response;

class EnsureFrontendIsCustomer
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() instanceof Customer) {
            return response()->json([
                'success' => false,
                'message' => 'Token no autorizado para esta API.',
                'code' => 'AUDIENCE_MISMATCH',
            ], 403);
        }

        return $next($request);
    }
}
