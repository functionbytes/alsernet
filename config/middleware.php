<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Global Middleware
    |--------------------------------------------------------------------------
    |
    | Estos middlewares se ejecutan en cada solicitud realizada a la aplicación.
    | Aquí puedes incluir middlewares como manejo de CORS, manejo de sesiones,
    | verificación de autenticación, etc. No se pueden excluir de las rutas.
    |
    */

    'global' => [
        \Illuminate\Foundation\Http\Middleware\HandleCors::class,
        \Illuminate\Session\Middleware\StartSession::class,
        \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        \App\Http\Middleware\VerifyCsrfToken::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Route Middleware
    |--------------------------------------------------------------------------
    |
    | Estos middlewares se pueden asignar a rutas específicas o grupos de rutas.
    | Puedes definir middlewares personalizados aquí, como el middleware de roles,
    | y luego usarlos en las rutas para restringir el acceso según sea necesario.
    |
    */

    'route_middlewares' => [

        'auth' => \App\Http\Middleware\Authenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'auth.session' => \Illuminate\Session\Middleware\AuthenticateSession::class,
        'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
        'signed' => \Illuminate\Routing\Middleware\ValidateSignature::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,

        // Middleware personalizado de roles
        'role' => \App\Http\Middleware\RoleMiddleware::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Middleware Groups
    |--------------------------------------------------------------------------
    |
    | Puedes definir grupos de middleware que se aplicarán en conjuntos a las
    | rutas específicas. Laravel define algunos grupos predeterminados, como
    | 'web' y 'api', que puedes personalizar según tus necesidades.
    |
    */

    'middleware_groups' => [
        'web' => [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
        'api' => [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            'throttle:api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
    ],

];
