<?php

namespace App\Http\Middleware;

use App\Models\HelpdeskDataAccessLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuditDataAccess
{
    public function handle(Request $request, Closure $next, string $source = 'unknown', string $action = 'view'): Response
    {
        $response = $next($request);

        if ($response->getStatusCode() < 400 && $request->user()) {
            $email = $request->route('email') ?? $request->input('email');

            if ($email) {
                HelpdeskDataAccessLog::create([
                    'user_id' => $request->user()->id,
                    'email_accessed' => mb_substr((string) $email, 0, 255),
                    'source' => $source,
                    'action' => $action,
                    'ip_address' => $request->ip(),
                    'user_agent' => mb_substr((string) $request->userAgent(), 0, 512),
                    'request_id' => $request->header('X-Request-Id'),
                    'accessed_at' => now(),
                ]);
            }
        }

        return $response;
    }
}
