<?php

namespace Modules\HelpdeskBirthday\Tests\Unit;

use Carbon\CarbonImmutable;
use Modules\HelpdeskBirthday\Services\BirthdayDayResolver;
use Tests\TestCase;

/**
 * A quien nació un 29 de febrero hay que felicitarle igual los otros tres años.
 */
class BirthdayDayResolverTest extends TestCase
{
    private BirthdayDayResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = new BirthdayDayResolver;
    }

    public function test_un_dia_normal_consulta_solo_ese_dia(): void
    {
        $this->assertSame(['09-02'], $this->resolver->daysFor(CarbonImmutable::parse('2026-09-02')));
    }

    public function test_en_ano_no_bisiesto_el_28_feb_arrastra_a_los_del_29(): void
    {
        config()->set('helpdeskbirthday.leap_day_policy', BirthdayDayResolver::POLICY_FEB_28);

        // 2026 no es bisiesto.
        $days = $this->resolver->daysFor(CarbonImmutable::parse('2026-02-28'));

        $this->assertSame(['02-28', '02-29'], $days);
    }

    public function test_con_la_politica_de_marzo_el_arrastre_cae_el_dia_1(): void
    {
        config()->set('helpdeskbirthday.leap_day_policy', BirthdayDayResolver::POLICY_MAR_01);

        $this->assertSame(['03-01', '02-29'], $this->resolver->daysFor(CarbonImmutable::parse('2026-03-01')));

        // Y entonces el 28 de febrero deja de arrastrar.
        $this->assertSame(['02-28'], $this->resolver->daysFor(CarbonImmutable::parse('2026-02-28')));
    }

    public function test_en_ano_bisiesto_cada_uno_cobra_su_dia(): void
    {
        config()->set('helpdeskbirthday.leap_day_policy', BirthdayDayResolver::POLICY_FEB_28);

        // 2028 sí es bisiesto: el 29 existe, así que el 28 no arrastra a nadie.
        $this->assertSame(['02-28'], $this->resolver->daysFor(CarbonImmutable::parse('2028-02-28')));
        $this->assertSame(['02-29'], $this->resolver->daysFor(CarbonImmutable::parse('2028-02-29')));
    }
}
