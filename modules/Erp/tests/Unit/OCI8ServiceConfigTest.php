<?php

namespace Modules\Erp\Tests\Unit;

use Modules\Erp\Services\OCI8Service;
use Tests\TestCase;

class OCI8ServiceConfigTest extends TestCase
{
    public static function fakeDynamic(array &$config): void
    {
        $config['username'] = 'panel_user';
        $config['service_name'] = 'PANELSVC';
    }

    public function test_uses_the_same_dynamic_config_hook_as_laravel_connection(): void
    {
        config([
            'database.connections.oracle.host' => 'oracle.local',
            'database.connections.oracle.username' => '',
            'database.connections.oracle.dynamic' => [self::class, 'fakeDynamic'],
        ]);

        $service = new OCI8Service;
        $read = fn (string $prop) => (fn () => $this->{$prop})->call($service);

        $this->assertSame('panel_user', $read('username'));
        $this->assertSame('PANELSVC', $read('database'));
        $this->assertSame('oracle.local', $read('host'));
    }
}
