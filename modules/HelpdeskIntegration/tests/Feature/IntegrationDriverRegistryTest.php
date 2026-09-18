<?php

namespace Modules\HelpdeskIntegration\Tests\Feature;

use Illuminate\Support\Facades\Http;
use Modules\HelpdeskErp\Services\ErpContextService;
use Modules\HelpdeskIntegration\Contracts\IntegrationDriverContract;
use Modules\HelpdeskIntegration\Support\Drivers\ErpIntegrationDriver;
use Modules\HelpdeskIntegration\Support\Drivers\PrestashopIntegrationDriver;
use Modules\HelpdeskIntegration\Support\IntegrationDriverRegistry;
use Modules\HelpdeskPrestashop\Services\PrestashopContextService;
use RuntimeException;
use Tests\TestCase;

class IntegrationDriverRegistryTest extends TestCase
{
    public function test_registry_registers_and_resolves_driver(): void
    {
        $registry = new IntegrationDriverRegistry;

        $registry->register('prestashop', PrestashopIntegrationDriver::class);

        $this->assertTrue($registry->has('prestashop'));
        $this->assertInstanceOf(IntegrationDriverContract::class, $registry->get('prestashop'));
        $this->assertSame(['prestashop'], $registry->keys());
    }

    public function test_registry_throws_for_unknown_key(): void
    {
        $registry = new IntegrationDriverRegistry;

        $this->expectException(RuntimeException::class);

        $registry->get('unknown');
    }

    public function test_prestashop_driver_registered_when_helpdeskprestashop_present(): void
    {
        if (! class_exists(PrestashopContextService::class)) {
            $this->markTestSkipped('HelpdeskPrestashop no esta instalado en este entorno.');
        }

        $registry = app(IntegrationDriverRegistry::class);

        $this->assertTrue($registry->has('prestashop'));
        $this->assertTrue($registry->get('prestashop')->isAvailable());
    }

    public function test_erp_driver_is_available_reflects_class_exists(): void
    {
        if (! class_exists(ErpContextService::class)) {
            $this->markTestSkipped('HelpdeskErp no esta instalado en este entorno.');
        }

        $driver = new ErpIntegrationDriver;

        $this->assertTrue($driver->isAvailable());
    }

    /**
     * Una respuesta no exitosa del manager ERP (p. ej. 401 por config
     * incorrecta) debe reportarse como fallo de plataforma, no como "sin
     * resultados" — de lo contrario el agente ve un falso "no existe".
     */
    public function test_erp_driver_reports_platform_error_on_unsuccessful_response(): void
    {
        if (! class_exists(ErpContextService::class)) {
            $this->markTestSkipped('HelpdeskErp no esta instalado en este entorno.');
        }

        config(['helpdeskErp.manager_url' => 'http://manager.test']);
        Http::fake(['*' => Http::response(['error' => 'unauthorized'], 401)]);

        $result = (new ErpIntegrationDriver)->search('someone@example.com', 'email');

        $this->assertFalse($result->ok);
        $this->assertSame([], $result->results);
    }
}
