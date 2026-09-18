<?php

namespace Modules\Helpdesk\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route as RouteFacade;
use Modules\Helpdesk\Models\ConversationStatus;
use Tests\TestCase;

/**
 * Cierra la clase de bug de BUG-01/HOTFIX-00: una ruta con `throttle:<nombre>`
 * cuyo limiter nunca se registró vía RateLimiter::for() no lanza un 429 al
 * agotarse — ThrottleRequests::handle() cae al modo numérico crudo,
 * tratando el nombre como maxAttempts no numérico (500 o comportamiento
 * indefinido). El caso original (helpdesk-webhook-inbound sin registrar en
 * el webhook de correo entrante) se cubre aquí junto con un barrido genérico
 * de TODAS las rutas de la app para que no vuelva a pasar con otro módulo.
 */
class RateLimiterRegistrationTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    /**
     * (8b) Regresión puntual de HOTFIX-00: el webhook de correo entrante no
     * debe devolver 500. Antes del hotfix, `throttle:helpdesk-webhook-inbound`
     * apuntaba a un limiter sin registrar.
     */
    public function test_inbound_email_webhook_does_not_500(): void
    {
        ConversationStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1]
        );

        $status = $this->postJson(
            route('helpdesk.webhooks.email-inbound', ['provider' => 'generic']),
            ['from' => 'test@example.com', 'subject' => 'Hello', 'body' => 'Test body']
        )->getStatusCode();

        $this->assertNotSame(500, $status);
    }

    /**
     * (8c) Barrido genérico: toda ruta registrada que use `throttle:<nombre>`
     * (forma con nombre, no `throttle:N,M[,prefix]`) debe tener su
     * RateLimiter::for(<nombre>, ...) registrado. Recorre route:list en vez
     * de fijar una lista a mano para que cubra módulos futuros también.
     */
    public function test_every_named_throttle_middleware_has_a_registered_rate_limiter(): void
    {
        $missing = [];

        foreach (RouteFacade::getRoutes() as $route) {
            foreach ($route->gatherMiddleware() as $middleware) {
                $limiterName = $this->namedThrottleLimiter($middleware);

                if ($limiterName === null) {
                    continue;
                }

                if (RateLimiter::limiter($limiterName) === null) {
                    $missing["{$limiterName} (route: {$route->getName()})"] = true;
                }
            }
        }

        $this->assertSame(
            [],
            array_keys($missing),
            'Rutas con throttle:<nombre> sin RateLimiter::for() registrado. '
            .'Cada nombre listado necesita su registro en el ServiceProvider del módulo.'
        );
    }

    /**
     * Extrae el nombre del limiter de un string de middleware `throttle:...`,
     * o null si no aplica (no es throttle, o es la forma numérica cruda
     * `throttle:N,M` / `throttle:N,M,prefix` que no requiere registro).
     */
    private function namedThrottleLimiter(string $middleware): ?string
    {
        if (! str_starts_with($middleware, 'throttle:')) {
            return null;
        }

        $params = substr($middleware, strlen('throttle:'));
        $firstSegment = explode(',', $params)[0];

        return is_numeric($firstSegment) ? null : $firstSegment;
    }
}
