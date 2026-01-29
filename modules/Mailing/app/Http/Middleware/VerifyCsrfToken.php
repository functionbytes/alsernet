<?php

namespace Modules\Mailing\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
        'webhooks/*',
        'plugins/webhooks/*',
        'delivery/*',
        'api/*',
        'manager/*',
        '*/embedded-form-*',
        'payments/stripe/credit-card*',
        'frontend/*',
    ];
}
