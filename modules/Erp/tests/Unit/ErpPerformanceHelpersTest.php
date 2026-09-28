<?php

namespace Modules\Erp\Tests\Unit;

use Illuminate\Database\Connection;
use Illuminate\Database\LostConnectionException;
use Illuminate\Support\Facades\DB;
use Modules\Core\Models\Setting;
use Modules\Erp\Http\Controllers\Api\ApiController;
use Modules\Erp\Models\Oracle\Catalogo\Modelo;
use Modules\Erp\Providers\ErpServiceProvider;
use Modules\Erp\Services\CatalogHierarchyService;
use Modules\Erp\Services\OCI8Service;
use Tests\TestCase;

class ErpPerformanceHelpersTest extends TestCase
{
    private function cacheCaller(): object
    {
        return new class extends ApiController
        {
            public function run(string $key, \Closure $cb): array
            {
                return $this->cachedResult($key, $cb);
            }
        };
    }

    public function test_cached_result_releases_the_sentinel_when_the_callback_fails(): void
    {
        $caller = $this->cacheCaller();
        cache()->forget('erp-test-key');
        // cachedResult() respeta el ajuste oracle_enable_cache del panel.
        $bundle = Setting::getErpSettings();
        $bundle['oracle_enable_cache'] = true;
        cache()->put('settings_erp_bundle', $bundle, 60);

        try {
            $caller->run('erp-test-key', fn () => throw new \RuntimeException('ORA-03113'));
            $this->fail('La excepción debía propagarse');
        } catch (\RuntimeException) {
        }

        // Antes el centinela se quedaba 1 h: la siguiente llamada no cacheaba.
        $first = $caller->run('erp-test-key', fn () => ['ok' => 1]);
        $second = $caller->run('erp-test-key', fn () => ['ok' => 2]);

        $this->assertFalse($first['cached']);
        $this->assertTrue($second['cached']);
        $this->assertSame(['ok' => 1], $second['data']);
    }

    public function test_hierarchy_is_served_from_cache_without_touching_oracle(): void
    {
        $row = ['idgrupo_cl' => 7, 'idsubfamilia_cl' => 70, 'idfamilia_cl' => 700, 'idcategoria_cl' => 7000,
            'iddeporte_cl' => '9', 'familia' => ['id' => 700], 'deporte' => ['id' => 9]];
        cache()->put('erp:catalog_hierarchy:grupo:7', $row, 60);

        DB::shouldReceive('connection')->never();

        $this->assertSame([7 => $row], app(CatalogHierarchyService::class)->forGroups([7, '7', null, 0]));
    }

    public function test_fast_paginate_hydrates_clobs_longer_than_the_inline_limit(): void
    {
        $long = str_repeat('x', 5000);

        $oci8 = $this->createMock(OCI8Service::class);
        $oci8->expects($this->once())
            ->method('query')
            ->with($this->stringContains('WHERE idmodelo IN (:id0)'), ['id0' => '2'])
            ->willReturn([['idmodelo' => '2', 'descripcion' => $long]]);

        $rows = [
            ['idmodelo' => '1', 'descripcion' => 'corta', 'descripcion__len' => '5'],
            ['idmodelo' => '2', 'descripcion' => str_repeat('x', 4000), 'descripcion__len' => '5000'],
            ['idmodelo' => '3', 'descripcion' => null, 'descripcion__len' => '0'],
            ['idmodelo' => '4', 'descripcion' => null, 'descripcion__len' => null],
        ];

        $model = new class extends Modelo
        {
            public static function hydrate_(OCI8Service $o, array $r): array
            {
                return self::hydrateLobColumns($o, $r);
            }
        };

        $out = $model::hydrate_($oci8, $rows);

        $this->assertSame('corta', $out[0]['descripcion']);
        $this->assertSame($long, $out[1]['descripcion']);
        $this->assertSame('', $out[2]['descripcion'], 'EMPTY_CLOB debe seguir saliendo como cadena vacía');
        $this->assertNull($out[3]['descripcion']);
        $this->assertArrayNotHasKey('descripcion__len', $out[0]);
    }

    public function test_oracle_resolver_retries_without_persistence_after_a_dead_handle(): void
    {
        $original = Connection::getResolver('oracle');
        $flag = (new \ReflectionClass(ErpServiceProvider::class))->getProperty('oracleResolverHardened');
        $flagBefore = $flag->getValue();

        $calls = [];
        Connection::resolverFor('oracle', function ($c, $d, $p, $config) use (&$calls) {
            $calls[] = $config['options'][\PDO::ATTR_PERSISTENT] ?? null;
            if (count($calls) === 1) {
                throw new LostConnectionException('Lost connection and no reconnector available.');
            }

            return 'fresh-connection';
        });

        try {
            $flag->setValue(null, false);
            $provider = new ErpServiceProvider($this->app);
            (fn () => $this->hardenOracleResolver())->call($provider);

            $result = Connection::getResolver('oracle')(null, 'db', '', ['options' => [\PDO::ATTR_PERSISTENT => true]]);

            $this->assertSame('fresh-connection', $result);
            $this->assertSame([true, false], $calls);
        } finally {
            Connection::resolverFor('oracle', $original);
            $flag->setValue(null, $flagBefore);
        }
    }
}
