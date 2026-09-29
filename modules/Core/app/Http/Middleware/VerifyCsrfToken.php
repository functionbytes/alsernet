<?php

namespace Modules\Core\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    // 29-sep-2026: fuera 'api/*' (dejaba sin CSRF 54 rutas con sesión de
    // Document/Notification/helpcenter; todo su JS ya envía X-CSRF-TOKEN) y las
    // excepciones de Acelle sin ninguna ruta detrás. Las rutas del grupo `api`
    // no pasan por este middleware. Los webhooks validan su propia firma.
    protected $except = [
        'webhooks/*',
    ];
}
