<?php

namespace Modules\HelpdeskErp\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Laravel\Pulse\Pulse;
use Modules\HelpdeskErp\Services\ErpContextService;
use Tests\TestCase;

/**
 * Circuit breaker: cuando el manager ERP está caído cada request cuelga hasta
 * http_timeout (15s). Tras N fallos consecutivos el breaker abre y las llamadas
 * se cortan (contexto vacío inmediato) para no arrastrar el inbox.
 */
class ErpCircuitBreakerTest extends TestCase
{
    use DatabaseTransactions;

    private const CIRCUIT_KEY = 'helpdeskerp:circuit_failures';

    private ErpContextService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(Pulse::class, new class
        {
            public function set(string $type, string $key, mixed $value, mixed $timestamp = null): object
            {
                return new \stdClass;
            }

            public function record(mixed ...$args): object
            {
                return new \stdClass;
            }
        });

        config(['helpdeskErp.manager_url' => 'http://manager.test']);
        config(['helpdeskErp.circuit_failure_threshold' => 3]);
        config(['helpdeskErp.circuit_open_seconds' => 30]);

        Cache::forget(self::CIRCUIT_KEY);

        $this->service = $this->app->make(ErpContextService::class);
    }

    public function test_circuit_opens_after_threshold_connection_failures(): void
    {
        Http::fake(function (): void {
            throw new ConnectionException('Connection refused');
        });

        // Tres clientes distintos (claves de caché distintas) fallan → 3 fallos
        // registrados → el breaker abre al alcanzar el umbral.
        foreach (['a@x.com', 'b@x.com', 'c@x.com'] as $email) {
            $result = $this->service->getCustomerContext($email);
            $this->assertSame('connection', $result['_error']['type']);
        }

        $this->assertGreaterThanOrEqual(3, (int) Cache::get(self::CIRCUIT_KEY));

        // Con el breaker abierto, la siguiente consulta corta SIN tocar HTTP.
        Http::fake();

        $result = $this->service->getCustomerContext('d@x.com');

        $this->assertSame('circuit_open', $result['_error']['type']);
        Http::assertNothingSent();
    }

    public function test_successful_call_resets_the_failure_counter(): void
    {
        // Fake stateful: los dos primeros requests fallan (conexión), el tercero
        // responde. Un único fake evita que el closure que lanza excepción quede
        // registrado con prioridad al re-fakear (Http::fake mergea callbacks).
        $calls = 0;
        Http::fake(function () use (&$calls) {
            $calls++;
            if ($calls <= 2) {
                throw new ConnectionException('down');
            }

            return Http::response(['data' => []]);
        });

        // Dos fallos, por debajo del umbral (3).
        $this->service->getCustomerContext('a@x.com');
        $this->service->getCustomerContext('b@x.com');
        $this->assertSame(2, (int) Cache::get(self::CIRCUIT_KEY));

        // Una respuesta del manager (aunque el cliente no exista) cierra el breaker.
        $this->service->getCustomerContext('c@x.com');

        $this->assertNull(Cache::get(self::CIRCUIT_KEY));
    }

    public function test_search_customers_short_circuits_when_open(): void
    {
        // Fuerza el breaker abierto por encima del umbral.
        Cache::put(self::CIRCUIT_KEY, 5, 30);

        Http::fake();

        $results = $this->service->searchCustomers('anything@x.com', 'email');

        $this->assertSame([], $results);
        Http::assertNothingSent();
    }

    public function test_order_detail_short_circuits_when_open(): void
    {
        Cache::put(self::CIRCUIT_KEY, 5, 30);

        Http::fake();

        $detail = $this->service->getOrderDetail(1, 42);

        $this->assertNull($detail);
        Http::assertNothingSent();
    }
}
