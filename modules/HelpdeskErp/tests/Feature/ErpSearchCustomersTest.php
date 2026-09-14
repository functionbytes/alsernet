<?php

namespace Modules\HelpdeskErp\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Laravel\Pulse\Pulse;
use Modules\HelpdeskErp\Services\ErpContextService;
use RuntimeException;
use Tests\TestCase;

/**
 * Una respuesta HTTP no exitosa del manager ERP (p. ej. 401 por una URL de
 * manager mal configurada) no es lo mismo que "sin resultados": debe quedar
 * visible como fallo de plataforma en vez de una lista vacia silenciosa.
 */
class ErpSearchCustomersTest extends TestCase
{
    use DatabaseTransactions;

    private const CIRCUIT_KEY = 'helpdeskerp:circuit_failures';

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

        Cache::forget(self::CIRCUIT_KEY);
    }

    public function test_search_customers_throws_on_unsuccessful_response(): void
    {
        Http::fake(['*' => Http::response(['error' => 'unauthorized'], 401)]);

        $this->expectException(RuntimeException::class);

        $this->app->make(ErpContextService::class)->searchCustomers('someone@example.com', 'email');
    }

    public function test_search_customers_throws_on_connection_failure(): void
    {
        // Un timeout/conexión rechazada tampoco es "sin resultados" — igual que
        // la respuesta no-2xx, debe quedar visible como fallo de plataforma en
        // vez de dar a entender que el cliente no existe en el ERP.
        Http::fake(function (): void {
            throw new ConnectionException('Operation timed out');
        });

        $this->expectException(RuntimeException::class);

        $this->app->make(ErpContextService::class)->searchCustomers('someone@example.com', 'email');
    }

    public function test_search_customers_returns_data_on_successful_response(): void
    {
        Http::fake(['*' => Http::response(['data' => [
            ['id' => '1', 'label' => 'Jane', 'surnames' => 'Doe', 'email' => 'jane@example.com'],
        ]])]);

        $results = $this->app->make(ErpContextService::class)->searchCustomers('jane@example.com', 'email');

        $this->assertSame('jane@example.com', $results[0]['email']);
    }
}
