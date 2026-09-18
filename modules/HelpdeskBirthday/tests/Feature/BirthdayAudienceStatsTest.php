<?php

namespace Modules\HelpdeskBirthday\Tests\Feature;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use Modules\HelpdeskBirthday\Services\BirthdayAudienceStatsService;
use Tests\TestCase;

/**
 * Desglose de la audiencia que se enseña en la campaña ("cumplen 727, se les
 * puede escribir a 483, y estos son los motivos de los otros").
 */
class BirthdayAudienceStatsTest extends TestCase
{
    private function servicio(): BirthdayAudienceStatsService
    {
        config(['helpdeskbirthday.customers_api_url' => 'http://nginx']);

        return app(BirthdayAudienceStatsService::class);
    }

    private function respuesta(): array
    {
        return [
            'success' => true,
            'days' => ['09-04'],
            'stats' => [
                'total' => 727,
                'unsubscribed' => 12,
                'no_email' => 50,
                'no_lopd' => 123,
                'no_commercial_optin' => 90,
                'writable' => 483,
            ],
        ];
    }

    public function test_pide_el_desglose_del_dia(): void
    {
        Http::fake(['*' => Http::response($this->respuesta())]);

        $stats = $this->servicio()->forDate(CarbonImmutable::parse('2026-09-04'));

        $this->assertSame(727, $stats['total']);
        $this->assertSame(483, $stats['writable']);
        $this->assertSame(123, $stats['no_lopd']);

        Http::assertSent(fn ($request) => str_contains($request->url(), '/api/erp/customer/birthday-stats')
            && str_contains(urldecode($request->url()), 'day=09-04'));
    }

    public function test_un_fallo_no_impide_preparar_la_campana(): void
    {
        Http::fake(['*' => Http::response(['success' => false], 502)]);

        // Es información para el panel, no un requisito para enviar: se devuelve
        // null y la campaña sigue su curso.
        $this->assertNull($this->servicio()->forDate(CarbonImmutable::parse('2026-09-04')));
    }

    public function test_una_respuesta_sin_stats_se_trata_como_ausente(): void
    {
        Http::fake(['*' => Http::response(['success' => true], 200)]);

        $this->assertNull($this->servicio()->forDate(CarbonImmutable::parse('2026-09-04')));
    }
}
