<?php

namespace Modules\HelpdeskBirthday\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Mockery;
use Modules\Erp\Services\ErpService;
use Modules\HelpdeskBirthday\Models\BirthdayCampaign;
use Modules\HelpdeskBirthday\Models\BirthdayRecipient;
use Modules\HelpdeskBirthday\Services\BirthdayBonoGenerator;
use Modules\HelpdeskBirthday\Support\BirthdaySettings;
use Tests\TestCase;

/**
 * Ciclo del bono contra Gestión, con ErpService doblado: generar de verdad
 * ESCRIBE en el ERP (crea bonos que no se pueden deshacer), así que un test
 * jamás debe llegar hasta allí.
 */
class BirthdayBonoGeneratorTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['helpdesk', 'mysql', 'mariadb'];

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    /**
     * El tipo de bono se configura en el panel (Ajustes → Cupón del día), así
     * que se dobla BirthdaySettings: tocar config() ya no cambia nada, y leer
     * el ajuste real haría que el test dependiera de lo que haya guardado en
     * este entorno.
     */
    private function withBonoType(int $id): void
    {
        $settings = Mockery::mock(BirthdaySettings::class);
        $settings->shouldReceive('all')->andReturn(['bono_type_id' => $id]);

        $this->app->instance(BirthdaySettings::class, $settings);
    }

    private function recipient(array $extra = []): BirthdayRecipient
    {
        $campaign = BirthdayCampaign::create([
            'uid' => (string) Str::uuid(),
            'campaign_date' => '2031-05-06',
            'status' => BirthdayCampaign::STATUS_DRAFT,
        ]);

        return BirthdayRecipient::create(array_merge([
            'campaign_id' => $campaign->id,
            'erp_customer_id' => '39094',
            'email' => 'ana@example.com',
            'name' => 'ANA',
            'status' => BirthdayRecipient::STATUS_PENDING,
        ], $extra));
    }

    public function test_no_genera_nada_sin_tipo_de_bono_configurado(): void
    {
        $this->withBonoType(0);

        $erp = Mockery::mock(ErpService::class);
        $erp->shouldNotReceive('generarBonos');
        $this->app->instance(ErpService::class, $erp);

        $this->expectExceptionMessageMatches('/tipo de bono/');

        app(BirthdayBonoGenerator::class)->generateFor(new Collection([$this->recipient()]), 'Cumpleaños');
    }

    /**
     * Respuesta calcada de la del ERP real (4-sep-2026): el bono llega anidado
     * dentro de la línea, no suelto.
     */
    private function lineaConBono(string $idcliente = '39094'): array
    {
        return [
            'idcliente' => $idcliente,
            'idtbono_promocion' => '4',
            'observacion' => 'Bono cumpleanos',
            'bono' => [
                'idbono_promocion' => '103205316',
                'codigo_verificacion' => '3D388',
                'importe' => '5.0',
                'importeminimoventa' => [],
                'fvalidez_desde' => '2026-09-04',
                'fvalidez_hasta' => '2026-10-04 14:13:25',
                'descripcion_estado_extendido' => 'activo',
                'estado_extendido' => '1',
            ],
        ];
    }

    public function test_manda_una_linea_por_cliente_y_recupera_su_bono(): void
    {
        $this->withBonoType(82);

        $erp = Mockery::mock(ErpService::class);
        $erp->shouldReceive('generarBonos')
            ->once()
            ->withArgs(function (array $lineas, string $descripcion): bool {
                return count($lineas) === 1
                    && $lineas[0]['idcliente'] === 39094
                    && $lineas[0]['idtbono_promocion'] === 82;
            })
            ->andReturn(['success' => true, 'batch_id' => '100267866']);
        // Segundo paso: la generación solo devuelve el id del lote, así que hay
        // que preguntar qué bono le tocó a cada uno.
        $erp->shouldReceive('consultarGeneracionBono')
            ->once()
            ->with('100267866')
            ->andReturn(['success' => true, 'lines' => [$this->lineaConBono()]]);
        $this->app->instance(ErpService::class, $erp);

        $recipient = $this->recipient();
        $result = app(BirthdayBonoGenerator::class)->generateFor(new Collection([$recipient]), 'Cumpleaños');

        $this->assertSame(1, $result['generated']);
        $this->assertSame(['100267866'], $result['batches']);

        $fresh = $recipient->fresh();
        $this->assertSame('103205316', $fresh->coupon_code);
        $this->assertSame('3D388', $fresh->coupon_verification_code);
        $this->assertSame('5.0000', (string) $fresh->coupon_amount);
        $this->assertSame('activo', $fresh->coupon_status);
        $this->assertNotNull($fresh->coupon_generated_at);
    }

    public function test_cada_bono_va_a_su_cliente_y_no_al_de_al_lado(): void
    {
        $this->withBonoType(4);

        $ana = $this->recipient(['erp_customer_id' => '39094', 'email' => 'ana@example.com']);
        $luis = BirthdayRecipient::create([
            'campaign_id' => $ana->campaign_id,
            'erp_customer_id' => '67230',
            'email' => 'luis@example.com',
            'name' => 'LUIS',
            'status' => BirthdayRecipient::STATUS_PENDING,
        ]);

        $lineaLuis = $this->lineaConBono('67230');
        $lineaLuis['bono']['idbono_promocion'] = '103205317';
        $lineaLuis['bono']['codigo_verificacion'] = '49FA8';

        $erp = Mockery::mock(ErpService::class);
        $erp->shouldReceive('generarBonos')->andReturn(['success' => true, 'batch_id' => '101295884']);
        // A propósito en orden inverso al enviado: las líneas se casan por
        // idcliente, no por posición. Equivocarse aquí manda a alguien el bono
        // de otro.
        $erp->shouldReceive('consultarGeneracionBono')
            ->andReturn(['success' => true, 'lines' => [$lineaLuis, $this->lineaConBono('39094')]]);
        $this->app->instance(ErpService::class, $erp);

        app(BirthdayBonoGenerator::class)->generateFor(new Collection([$ana, $luis]), 'Cumpleaños');

        $this->assertSame('103205316', $ana->fresh()->coupon_code);
        $this->assertSame('103205317', $luis->fresh()->coupon_code);
    }

    public function test_si_gestion_no_emite_el_bono_de_alguien_ese_queda_sin_cupon(): void
    {
        $this->withBonoType(4);

        $erp = Mockery::mock(ErpService::class);
        $erp->shouldReceive('generarBonos')->andReturn(['success' => true, 'batch_id' => '101295884']);
        // Lote aceptado pero sin bono para este cliente (pasa con
        // generar_bonos=0: la línea existe y el nodo del bono viene vacío).
        $erp->shouldReceive('consultarGeneracionBono')->andReturn([
            'success' => true,
            'lines' => [['idcliente' => '39094', 'idtbono_promocion' => '4', 'observacion' => '', 'bono' => null]],
        ]);
        $this->app->instance(ErpService::class, $erp);

        $recipient = $this->recipient();
        $result = app(BirthdayBonoGenerator::class)->generateFor(new Collection([$recipient]), 'Cumpleaños');

        $this->assertSame(0, $result['generated']);
        // Sin cupón no se le envía nada: eso lo garantiza SendBirthdayEmailJob.
        $this->assertNull($recipient->fresh()->coupon_code);
        $this->assertStringContainsString('no devolvió', (string) $recipient->fresh()->coupon_error);
    }

    public function test_quien_no_tiene_id_de_cliente_no_entra_en_la_generacion(): void
    {
        $this->withBonoType(82);

        // El bono se emite contra un idcliente de Gestión, no contra un correo.
        $erp = Mockery::mock(ErpService::class);
        $erp->shouldNotReceive('generarBonos');
        $this->app->instance(ErpService::class, $erp);

        $result = app(BirthdayBonoGenerator::class)
            ->generateFor(new Collection([$this->recipient(['erp_customer_id' => null])]), 'Cumpleaños');

        $this->assertSame(0, $result['generated']);
    }

    public function test_un_fallo_queda_anotado_en_la_fila_de_cada_uno(): void
    {
        $this->withBonoType(82);

        $erp = Mockery::mock(ErpService::class);
        $erp->shouldReceive('generarBonos')->andReturn(['success' => false, 'message' => 'Gestión rechazó el lote']);
        $erp->shouldNotReceive('consultarGeneracionBono');
        $this->app->instance(ErpService::class, $erp);

        $recipient = $this->recipient();
        $result = app(BirthdayBonoGenerator::class)->generateFor(new Collection([$recipient]), 'Cumpleaños');

        $this->assertSame(1, $result['failed']);
        $this->assertStringContainsString('rechazó', (string) $recipient->fresh()->coupon_error);
    }

    public function test_guarda_lo_que_gestion_responde_sobre_el_bono(): void
    {
        $erp = Mockery::mock(ErpService::class);
        // Respuesta calcada del ejemplo de la documentación (pág. 18).
        $erp->shouldReceive('consultaBono')->once()->andReturn([
            'success' => true,
            'data' => [
                'idbono_promocion' => '101558564',
                'codigo_verificacion' => 'C4843',
                'importe' => '10.4076',
                'importeminimoventa' => '200.0',
                'fvalidez_desde' => '2020-01-07',
                'fvalidez_hasta' => '2020-01-19',
                'descripcion_estado_extendido' => 'caducado',
                'tipo' => '2',
                // El XML manda los vacíos como array: no son ceros.
                'idtbono_promocion' => [],
            ],
        ]);
        $this->app->instance(ErpService::class, $erp);

        $recipient = $this->recipient(['coupon_code' => '101558564', 'coupon_verification_code' => 'C4843']);

        $this->assertTrue(app(BirthdayBonoGenerator::class)->syncDetails($recipient));

        $fresh = $recipient->fresh();
        $this->assertSame('10.4076', (string) $fresh->coupon_amount);
        $this->assertSame('200.00', (string) $fresh->coupon_min_purchase);
        $this->assertSame('2020-01-07', $fresh->coupon_valid_from->toDateString());
        $this->assertSame('caducado', $fresh->coupon_status);
        $this->assertSame('2', $fresh->coupon_data['tipo']);
    }

    public function test_un_bono_que_gestion_no_reconoce_queda_marcado(): void
    {
        $erp = Mockery::mock(ErpService::class);
        $erp->shouldReceive('consultaBono')->andReturn(['success' => false, 'message' => 'Bono no encontrado o inválido']);
        $this->app->instance(ErpService::class, $erp);

        $recipient = $this->recipient(['coupon_code' => '999']);

        $this->assertFalse(app(BirthdayBonoGenerator::class)->syncDetails($recipient));
        $this->assertStringContainsString('no encontrado', (string) $recipient->fresh()->coupon_error);
    }
}
