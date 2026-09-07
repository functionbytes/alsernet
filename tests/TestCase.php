<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Modules\Core\Http\Middleware\VerifyCsrfToken;

abstract class TestCase extends BaseTestCase
{
    /**
     * Sin esto, TODA petición POST/PUT/PATCH/DELETE del cliente de test
     * recibe 419 (confirmado 29-ago-2026: el proyecto no desactivaba CSRF
     * para tests en ningún sitio central — varios archivos lo parcheaban
     * suelto, uno por uno, con esta misma llamada). El objetivo de un test
     * HTTP es la lógica del controller, no el intercambio real de token
     * CSRF; eso lo cubre el propio framework/JS del navegador.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);

        // CACHE_STORE=array (phpunit.xml) vive en memoria durante TODO el
        // proceso de PHPUnit, no por test — el RateLimiter (throttle:*) usa
        // ese mismo cache, así que sin este flush los contadores se
        // acumulan entre tests de toda la suite y, al correrla completa,
        // rutas públicas con throttle bajo (helpdesk-feedback, ticket-forms,
        // widget) empiezan a devolver 429 en tests que no tienen nada que
        // ver con rate-limiting (confirmado 30-ago-2026, 23 fallos en
        // HelpdeskTickets desaparecieron con este fix).
        Cache::flush();

        // Ningún sitio del proyecto configura un timeout por defecto para
        // Http:: — cualquier test que olvide Http::fake() y golpee una URL
        // real puede colgarse indefinidamente (confirmado 30-ago-2026: una
        // corrida completa quedó colgada 14+ horas sin avisar). Esto es
        // solo una red de seguridad para tests: si el test SÍ fakea la
        // llamada, esto no aplica; si no la fakea, falla rápido (5s) en vez
        // de colgar el proceso entero.
        Http::globalOptions(['connect_timeout' => 3, 'timeout' => 5]);
    }

    /**
     * Create and authenticate a manager user
     */
    protected function actingAsManager(?User $user = null): self
    {
        $user ??= User::factory()->create();

        return $this->actingAs($user);
    }
}
