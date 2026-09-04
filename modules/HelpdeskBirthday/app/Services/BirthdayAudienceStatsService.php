<?php

namespace Modules\HelpdeskBirthday\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Desglose de la audiencia del día: cuánta gente cumple años y cuánta queda
 * fuera por cada motivo.
 *
 * Existe porque los descartes se resuelven en el WHERE de la consulta: los
 * excluidos no llegan a la campaña, así que sin preguntarlo aparte solo se
 * podría decir "hoy escribo a 576" sin poder explicar qué pasó con los otros
 * 151. Lo contesta `GET /api/erp/customer/birthday-stats` en una sola consulta.
 *
 * Nunca hace fallar la campaña: es información para el panel, no un requisito
 * para enviar. Si la consulta no responde, se guarda vacío y la campaña sigue.
 */
class BirthdayAudienceStatsService
{
    public function __construct(
        private readonly BirthdayDayResolver $days,
    ) {}

    /**
     * @return array<string, int>|null null si no se pudo consultar
     */
    public function forDate(CarbonImmutable $date): ?array
    {
        $baseUrl = rtrim((string) config(
            'helpdeskbirthday.customers_api_url',
            config('supplier.erp_internal_url', 'http://nginx'),
        ), '/');

        if ($baseUrl === '') {
            return null;
        }

        try {
            $response = Http::timeout((int) config('helpdeskbirthday.stats_timeout', 30))
                ->acceptJson()
                ->retry(2, 500, throw: false)
                ->get($baseUrl.'/api/erp/customer/birthday-stats', [
                    // Los mismos días que la audiencia, incluido el traspaso de
                    // los 29-feb (ver BirthdayDayResolver).
                    'day' => implode(',', $this->days->daysFor($date)),
                ]);

            if (! $response->successful() || ($response->json('success') !== true)) {
                Log::warning('[HelpdeskBirthday] No se pudo leer el desglose de la audiencia', [
                    'status' => $response->status(),
                ]);

                return null;
            }

            $stats = $response->json('stats');

            return is_array($stats) ? array_map('intval', $stats) : null;
        } catch (Throwable $e) {
            Log::warning('[HelpdeskBirthday] Fallo al leer el desglose de la audiencia', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
