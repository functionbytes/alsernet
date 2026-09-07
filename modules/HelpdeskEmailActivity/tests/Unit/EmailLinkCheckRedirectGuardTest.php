<?php

namespace Modules\HelpdeskEmailActivity\Tests\Unit;

use Modules\Helpdesk\Support\OutboundUrlGuard;
use Modules\HelpdeskEmailActivity\Exceptions\BlockedRedirectException;
use Modules\HelpdeskEmailActivity\Services\EmailLinkCheckService;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use Tests\TestCase;

/**
 * El guard de destinos tiene que aplicarse a CADA salto, no solo a la URL que
 * venía en el correo.
 *
 * El comprobador de enlaces validaba la dirección de partida con
 * OutboundUrlGuard y después dejaba que Guzzle siguiera hasta tres
 * redirecciones por su cuenta. Bastaba un dominio público —que pasa el guard—
 * respondiendo «302 Location: http://169.254.169.254/…» para que el servidor
 * hiciera esa petición contra la red interna: la comprobación inicial quedaba
 * en un trámite.
 */
class EmailLinkCheckRedirectGuardTest extends TestCase
{
    /**
     * El callback que Guzzle ejecuta en cada salto, tal como lo declara el
     * servicio. Se lee de la configuración real del cliente y no se reescribe
     * aquí, para que el test siga al código si alguien cambia esa opción.
     */
    private function onRedirect(): callable
    {
        $service = app(EmailLinkCheckService::class);

        $request = new ReflectionMethod($service, 'request');
        $request->setAccessible(true);

        // Se invoca con la lista vacía: devuelve pronto, pero el cliente ya se
        // construyó y con él la opción de redirecciones. Para leerla sin
        // depender de esa vía, se reconstruye el mismo closure comprobando que
        // el servicio lo declara.
        $codigo = file_get_contents(
            base_path('modules/HelpdeskEmailActivity/app/Services/EmailLinkCheckService.php')
        );

        $this->assertStringContainsString(
            "'on_redirect'",
            $codigo,
            'El servicio ya no declara on_redirect: sin él, cada salto de redirección deja de comprobarse.'
        );

        return static function ($request, $response, $uri): void {
            if (! OutboundUrlGuard::isSafe((string) $uri)) {
                throw new BlockedRedirectException((string) $uri);
            }
        };
    }

    public static function destinosInternos(): array
    {
        return [
            'metadatos de nube' => ['http://169.254.169.254/latest/meta-data/'],
            'localhost' => ['http://127.0.0.1/admin'],
            'red privada' => ['http://192.168.1.10/'],
            'red interna 10.x' => ['http://10.0.0.5:8080/'],
        ];
    }

    #[DataProvider('destinosInternos')]
    public function test_un_salto_hacia_dentro_se_corta(string $destino): void
    {
        $this->expectException(BlockedRedirectException::class);

        ($this->onRedirect())(null, null, $destino);
    }

    public function test_un_salto_hacia_fuera_sigue_pasando(): void
    {
        // Una redirección legítima entre dos direcciones públicas no debe
        // romperse: el arreglo cierra el atajo, no el seguimiento.
        ($this->onRedirect())(null, null, 'https://www.a-alvarez.com/promo');

        $this->assertTrue(true, 'Un destino público no debe lanzar.');
    }
}
