<?php

namespace Modules\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Generic access-audit middleware for GDPR-sensitive read/write endpoints
 * (ERP/PrestaShop customer data, contact merges, ...).
 *
 * Usage: ->middleware('audit.access:{module},{action}')
 *
 * Unlike Modules\Helpdesk\Services\AuditLogService (which persists
 * before/after diffs against a concrete Eloquent entity), this middleware
 * has no entity to attach to — many of the routes it guards accept an
 * arbitrary email/phone rather than a bound model — so it logs to the
 * standard log stack instead of the helpdesk_audit_logs table.
 *
 * NOTE: this alias was referenced by several routes (HelpdeskErp,
 * HelpdeskContacts) without ever being registered, which made every one of
 * those requests fail with a BindingResolutionException. This class fixes
 * that pre-existing gap.
 */
class AuditAccessMiddleware
{
    public function handle(Request $request, Closure $next, string $module, string $action): Response
    {
        Log::info('audit.access', [
            'module' => $module,
            'action' => $action,
            'user_id' => $request->user()?->id,
            'ip' => $request->ip(),
            'route' => $request->path(),
            'params' => $request->except(['password', 'password_confirmation', '_token']),
        ]);

        return $next($request);
    }
}
