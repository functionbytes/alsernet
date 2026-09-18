<?php

namespace Modules\HelpdeskEmailActivity\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Modules\Core\Models\Setting;
use Modules\HelpdeskEmailActivity\Support\TrackingUrl;
use Tests\TestCase;

/**
 * La base con la que se generan el píxel y los enlaces del correo. Lo que se
 * prueba aquí no es una preferencia estética: si esa base no es alcanzable
 * desde fuera, los enlaces del correo llevan al vacío para el destinatario.
 */
class TrackingUrlTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    protected function tearDown(): void
    {
        parent::tearDown();

        // Setting::get() cachea fuera de la transacción del test.
        Cache::forget('setting_helpdeskemailactivity.tracking_base_url');
    }

    public function test_falls_back_to_the_application_url(): void
    {
        config(['app.url' => 'https://panel.ejemplo.com']);

        $this->assertSame('https://panel.ejemplo.com', TrackingUrl::base());
    }

    public function test_a_configured_base_replaces_the_application_url(): void
    {
        config(['app.url' => 'https://panel.interno.ejemplo.com']);

        Setting::set('helpdeskemailactivity.tracking_base_url', 'https://correo.ejemplo.com');
        Cache::forget('setting_helpdeskemailactivity.tracking_base_url');

        $url = TrackingUrl::route('helpdeskemailactivity.pixel', ['emailLog' => '11111111-2222-4333-8444-555555555555']);

        $this->assertStringStartsWith('https://correo.ejemplo.com/e/', $url);
        $this->assertStringNotContainsString('panel.interno', $url);
    }

    public function test_trailing_slashes_do_not_duplicate(): void
    {
        Setting::set('helpdeskemailactivity.tracking_base_url', 'https://correo.ejemplo.com/');
        Cache::forget('setting_helpdeskemailactivity.tracking_base_url');

        $this->assertSame('https://correo.ejemplo.com', TrackingUrl::base());
    }

    /**
     * @dataProvider unreachableBases
     */
    public function test_detects_bases_a_recipient_cannot_open(string $url): void
    {
        $this->assertFalse(TrackingUrl::isPubliclyReachable($url), $url.' debería marcarse como inalcanzable');
    }

    public static function unreachableBases(): array
    {
        return [
            'localhost' => ['http://localhost:8092'],
            'loopback' => ['http://127.0.0.1'],
            'red privada' => ['http://192.168.1.5'],
            'otra red privada' => ['http://10.0.0.9:8000'],
            'dominio .test' => ['https://webadmin.test'],
            'dominio .local' => ['https://panel.local'],
        ];
    }

    /**
     * @dataProvider reachableBases
     */
    public function test_accepts_bases_a_recipient_can_open(string $url): void
    {
        $this->assertTrue(TrackingUrl::isPubliclyReachable($url), $url.' debería marcarse como alcanzable');
    }

    public static function reachableBases(): array
    {
        return [
            'dominio propio' => ['https://correo.a-alvarez.com'],
            'con puerto' => ['https://enlaces.ejemplo.com:8443'],
            'ip pública' => ['http://8.8.8.8'],
        ];
    }

    public function test_an_empty_or_broken_base_is_never_considered_reachable(): void
    {
        $this->assertFalse(TrackingUrl::isPubliclyReachable(''));
        $this->assertFalse(TrackingUrl::isPubliclyReachable('no-es-una-url'));
    }
}
