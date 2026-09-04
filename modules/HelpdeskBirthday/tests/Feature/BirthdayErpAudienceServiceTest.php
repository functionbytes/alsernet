<?php

namespace Modules\HelpdeskBirthday\Tests\Feature;

use Carbon\CarbonImmutable;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Modules\HelpdeskBirthday\Exceptions\BirthdayAudienceException;
use Modules\HelpdeskBirthday\Services\BirthdayDayResolver;
use Modules\HelpdeskBirthday\Services\BirthdayErpAudienceService;
use Tests\TestCase;

/**
 * Lectura de cumpleañeros desde Gestión, con respuestas enlatadas: pegar al ERP
 * real desde un test traería datos personales de cientos de clientes y tardaría
 * ~17 segundos por caso.
 */
class BirthdayErpAudienceServiceTest extends TestCase
{
    /** @var array<int, Request> */
    private array $enviadas = [];

    private function servicio(Response ...$respuestas): BirthdayErpAudienceService
    {
        $mock = new MockHandler($respuestas);
        $stack = HandlerStack::create($mock);
        $stack->push(function (callable $handler) {
            return function (Request $request, array $options) use ($handler) {
                $this->enviadas[] = $request;

                return $handler($request, $options);
            };
        });

        $client = new Client(['handler' => $stack, 'base_uri' => 'http://interges:8080', 'http_errors' => false]);

        return new class(app(BirthdayDayResolver::class), $client) extends BirthdayErpAudienceService
        {
            public function __construct(BirthdayDayResolver $days, private readonly Client $fake)
            {
                parent::__construct($days);
            }

            protected function client(string $base): Client
            {
                return $this->fake;
            }
        };
    }

    private function respuestaConClientes(): Response
    {
        return new Response(200, [], file_get_contents(__DIR__.'/../Fixtures/erp-clientes-cumpleanos.xml'));
    }

    public function test_consulta_el_endpoint_de_clientes_con_una_fecha_completa(): void
    {
        $this->servicio($this->respuestaConClientes())
            ->fetchForDate(CarbonImmutable::parse('2026-09-04'));

        $url = (string) $this->enviadas[0]->getUri();

        $this->assertStringContainsString('/api-gestion/cliente/', $url);
        // El ERP ignora el año pero exige uno válido: un '09-04' pelado responde
        // HTTP 500.
        $this->assertStringContainsString('fnacimiento=2000-09-04', urldecode($url));
    }

    public function test_devuelve_solo_los_clientes_a_los_que_se_puede_escribir(): void
    {
        $filas = $this->servicio($this->respuestaConClientes())
            ->fetchForDate(CarbonImmutable::parse('2026-09-04'));

        // De los cinco del fixture solo pasa uno: el resto se descarta por no
        // querer publicidad, no tener correo, estar de baja o no tener LOPD.
        $this->assertCount(1, $filas);
        $this->assertSame('ana.garcia@example.com', $filas[0]['email']);
        $this->assertSame('101578688', $filas[0]['id']);
    }

    public function test_no_cuenta_los_resources_anidados_de_cada_cliente(): void
    {
        // El primer cliente del fixture trae un <resource> dentro de
        // cliente_catalogo; si se contara, aparecería como un cliente más.
        $filas = $this->servicio($this->respuestaConClientes())
            ->fetchForDate(CarbonImmutable::parse('2026-09-04'), ['lopd_accepted' => false, 'commercial_optin' => false, 'has_email' => false]);

        $ids = array_column($filas, 'id');

        $this->assertSame(['101578688', '101569786', '400065083', '400065085'], $ids);
    }

    public function test_las_exclusiones_se_pueden_desactivar_una_a_una(): void
    {
        $filas = $this->servicio($this->respuestaConClientes())
            ->fetchForDate(CarbonImmutable::parse('2026-09-04'), ['commercial_optin' => false]);

        $emails = array_column($filas, 'email');

        // Ahora entra también quien marcó "no quiero información comercial".
        $this->assertContains('luis.martin@example.com', $emails);
        // Pero el dado de baja en Gestión no entra nunca.
        $this->assertNotContains('baja@example.com', $emails);
    }

    public function test_no_inventa_la_fecha_de_nacimiento(): void
    {
        $filas = $this->servicio($this->respuestaConClientes())
            ->fetchForDate(CarbonImmutable::parse('2026-09-04'));

        // El endpoint acepta fnacimiento como filtro pero no lo devuelve.
        // Rellenarlo con el día consultado falsearía la verificación de que el
        // filtro se aplicó (ver BirthdayAudienceService::assertFilterWasApplied).
        $this->assertNull($filas[0]['birth_date']);
    }

    public function test_un_error_del_erp_no_pasa_por_una_audiencia_vacia(): void
    {
        $this->expectException(BirthdayAudienceException::class);

        // Sin esto, un 500 devolvería "0 cumpleañeros" y la campaña se daría por
        // buena sin haber escrito a nadie.
        $this->servicio(new Response(500, [], 'Internal Server Error'))
            ->fetchForDate(CarbonImmutable::parse('2026-09-04'));
    }

    public function test_corta_si_el_erp_devuelve_mas_gente_de_la_permitida(): void
    {
        config(['helpdeskbirthday.max_recipients' => 2]);

        $muchos = '<?xml version="1.0"?><response>';
        for ($i = 1; $i <= 10; $i++) {
            $muchos .= "<resource><idcliente>{$i}</idcliente><email>cliente{$i}@example.com</email>"
                .'<nombre>N</nombre><apellidos>A</apellidos><faceptacion_lopd>2020-01-01</faceptacion_lopd>'
                .'<no_informacion_comercial_lopd>0</no_informacion_comercial_lopd><fbaja></fbaja></resource>';
        }
        $muchos .= '</response>';

        $this->expectException(BirthdayAudienceException::class);

        $this->servicio(new Response(200, [], $muchos))
            ->fetchForDate(CarbonImmutable::parse('2026-09-04'));
    }
}
