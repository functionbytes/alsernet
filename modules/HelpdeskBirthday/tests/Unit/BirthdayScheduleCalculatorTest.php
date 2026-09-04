<?php

namespace Modules\HelpdeskBirthday\Tests\Unit;

use Carbon\CarbonImmutable;
use InvalidArgumentException;
use Modules\HelpdeskBirthday\Services\BirthdayScheduleCalculator;
use Tests\TestCase;

/**
 * El reparto es la pieza que evita que nos bloqueen el servidor de correo.
 *
 * Extiende Tests\TestCase (y no PHPUnit a secas) solo porque la conversión de
 * zona horaria lee config(); no toca base de datos.
 */
class BirthdayScheduleCalculatorTest extends TestCase
{
    private BirthdayScheduleCalculator $calculator;

    private CarbonImmutable $start;

    private CarbonImmutable $end;

    protected function setUp(): void
    {
        parent::setUp();

        $this->calculator = new BirthdayScheduleCalculator;
        // Ventana de 5 horas = 18.000 s.
        $this->start = CarbonImmutable::parse('2026-09-02 09:00:00');
        $this->end = CarbonImmutable::parse('2026-09-02 14:00:00');
    }

    public function test_sin_destinatarios_no_revienta(): void
    {
        $plan = $this->calculator->plan(0, $this->start, $this->end, 600);

        $this->assertSame(0, $plan->recipients);
        $this->assertTrue($plan->estimatedEnd()->equalTo($this->start));
        $this->assertFalse($plan->overflowsWindow());
    }

    public function test_un_solo_destinatario_sale_al_principio_de_la_ventana(): void
    {
        $plan = $this->calculator->plan(1, $this->start, $this->end, 600);

        $this->assertTrue($plan->slotFor(0)->equalTo($this->start));
        $this->assertFalse($plan->overflowsWindow());
    }

    public function test_pocos_destinatarios_se_estiran_por_toda_la_ventana(): void
    {
        // 100 en 18.000 s → uno cada 180 s, muy por debajo del tope (600/h = 6 s).
        $plan = $this->calculator->plan(100, $this->start, $this->end, 600);

        $this->assertSame(180, $plan->intervalSeconds);
        $this->assertTrue($plan->slotFor(99)->lessThanOrEqualTo($this->end));
        $this->assertFalse($plan->overflowsWindow());
    }

    public function test_el_tope_por_hora_manda_cuando_no_caben_todos(): void
    {
        // 10.000 destinatarios con tope de 600/h → suelo de 6 s, no 1,8 s.
        $plan = $this->calculator->plan(10000, $this->start, $this->end, 600);

        $this->assertSame(6, $plan->intervalSeconds);
        $this->assertSame(600, $plan->perHour());

        // Y por tanto la campaña se sale de la ventana: es lo esperado, se
        // prefiere terminar tarde a que nos bloqueen el envío.
        $this->assertTrue($plan->overflowsWindow());
    }

    public function test_el_intervalo_nunca_es_cero(): void
    {
        // Sin tope y con más destinatarios que segundos de ventana, el reparto
        // natural daría 0 y todos saldrían a la vez.
        $plan = $this->calculator->plan(100000, $this->start, $this->end, 0);

        $this->assertGreaterThanOrEqual(1, $plan->intervalSeconds);
    }

    public function test_una_ventana_invertida_es_un_error(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->calculator->plan(10, $this->end, $this->start, 600);
    }

    public function test_la_ventana_nocturna_termina_al_dia_siguiente(): void
    {
        // Sin desfase, para comprobar solo el salto de día.
        config(['app.timezone' => 'UTC', 'helpdeskbirthday.timezone' => 'UTC']);

        [$start, $end] = $this->calculator->windowFor(
            CarbonImmutable::parse('2026-09-02 00:00:00'),
            '22:00',
            '02:00',
        );

        $this->assertSame('2026-09-02 22:00:00', $start->format('Y-m-d H:i:s'));
        $this->assertSame('2026-09-03 02:00:00', $end->format('Y-m-d H:i:s'));
    }

    /**
     * La app corre en UTC pero las horas de la ventana son horas de oficina.
     * Sin esta conversión, "de 9 a 2" salía en España a las 11:00 en verano.
     */
    public function test_la_ventana_se_interpreta_en_la_zona_de_negocio(): void
    {
        config(['app.timezone' => 'UTC', 'helpdeskbirthday.timezone' => 'Europe/Madrid']);

        [$start, $end] = $this->calculator->windowFor(
            CarbonImmutable::parse('2026-07-15', 'UTC'),
            '09:00',
            '14:00',
        );

        // Julio: Madrid va +2 sobre UTC.
        $this->assertSame('07:00', $start->setTimezone('UTC')->format('H:i'));
        $this->assertSame('12:00', $end->setTimezone('UTC')->format('H:i'));

        // Y visto desde España es exactamente lo que se configuró.
        $this->assertSame('09:00', $start->setTimezone('Europe/Madrid')->format('H:i'));
        $this->assertSame('14:00', $end->setTimezone('Europe/Madrid')->format('H:i'));
    }

    public function test_el_cambio_de_hora_no_desplaza_la_ventana(): void
    {
        config(['app.timezone' => 'UTC', 'helpdeskbirthday.timezone' => 'Europe/Madrid']);

        // Enero: Madrid va +1, no +2. La hora local debe seguir siendo la 9.
        [$start] = $this->calculator->windowFor(
            CarbonImmutable::parse('2026-01-15', 'UTC'),
            '09:00',
            '14:00',
        );

        $this->assertSame('08:00', $start->setTimezone('UTC')->format('H:i'));
        $this->assertSame('09:00', $start->setTimezone('Europe/Madrid')->format('H:i'));
    }

    public function test_una_hora_mal_escrita_es_un_error(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->calculator->windowFor(CarbonImmutable::parse('2026-09-02'), '9:00', '14:00');
    }
}
