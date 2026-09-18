<?php

namespace Modules\HelpdeskBirthday\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Modules\HelpdeskBirthday\Models\BirthdayCampaign;
use Modules\HelpdeskBirthday\Models\BirthdayRecipient;
use Modules\HelpdeskEmailActivity\Services\EmailDeliveryLookupService;

/**
 * Estadísticas del panel de campañas.
 *
 * Lo que pasó con los envíos (entregas, aperturas, clics, rebotes) NO se
 * calcula aquí: se pide a EmailDeliveryLookupService, que es la fuente única
 * para todos los módulos. Aquí solo viven los agregados propios de las
 * campañas de cumpleaños (cuántas, cuántos cumpleañeros, cuántos omitidos).
 */
class BirthdayDashboardService
{
    public const MODULE = 'HelpdeskBirthday';

    public function __construct(
        private readonly EmailDeliveryLookupService $delivery,
        private readonly BirthdayRedemptionService $redemptions,
        private readonly BirthdayQueueHealthService $health,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function overview(int $days = 30): array
    {
        $from = CarbonImmutable::now()->subDays($days)->startOfDay();
        $to = CarbonImmutable::now()->endOfDay();

        return [
            'days' => $days,
            'campaigns' => $this->campaignTotals($from, $to),
            'delivery' => $this->delivery->statsForModule(self::MODULE, $from, $to),
            'trend' => $this->trend($from, $to),
            'today' => $this->today(),
            'redemption' => $this->redemption($from, $to),
            'health' => $this->health->check(),
        ];
    }

    /**
     * El embudo: de los que cumplían años, a cuántos se les escribió, cuántos
     * abrieron, cuántos hicieron clic y cuántos compraron.
     *
     * Los cuatro números ya estaban repartidos por el panel; verlos en fila con
     * la caída entre pasos es lo que enseña dónde se pierde la gente.
     *
     * @param  array<string, mixed>  $campaigns
     * @param  array<string, mixed>  $delivery
     * @param  array<string, mixed>  $redemption
     * @return array<int, array<string, mixed>>
     */
    public function funnel(array $campaigns, array $delivery, array $redemption): array
    {
        $sent = (int) $delivery['sent'];

        $steps = [
            ['label' => 'Cumpleañeros', 'value' => (int) $campaigns['recipients']],
            ['label' => 'Correos enviados', 'value' => $sent],
            ['label' => 'Abiertos', 'value' => (int) $delivery['opened']],
            ['label' => 'Con clic', 'value' => (int) $delivery['clicked']],
        ];

        if ($redemption['available']) {
            $steps[] = ['label' => 'Compraron', 'value' => (int) $redemption['attributed']];
        }

        $first = $steps[0]['value'] ?: 1;
        $previous = null;

        foreach ($steps as $i => $step) {
            // Porcentaje sobre el total (ancho de la barra) y caída respecto al
            // paso anterior (lo que de verdad se quiere leer).
            $steps[$i]['percent'] = (int) round(($step['value'] / $first) * 100);
            $steps[$i]['drop'] = $previous !== null && $previous > 0
                ? (int) round((($previous - $step['value']) / $previous) * 100)
                : null;
            $previous = $step['value'];
        }

        return $steps;
    }

    /**
     * Canje del cupón agregado, que es la conversión real de la campaña.
     * Vive en PrestaShop; si esa BD no está configurada se devuelve no
     * disponible y el panel oculta la tarjeta en vez de mostrar ceros que
     * parecerían un mal resultado.
     *
     * @return array<string, mixed>
     */
    private function redemption(CarbonImmutable $from, CarbonImmutable $to): array
    {
        if (! $this->redemptions->isAvailable()) {
            return ['available' => false, 'redemptions' => 0, 'attributed' => 0, 'revenue' => 0.0, 'rate' => 0.0];
        }

        $campaigns = BirthdayCampaign::query()
            ->whereBetween('campaign_date', [$from->toDateString(), $to->toDateString()])
            ->whereNotNull('coupon_code')
            ->get();

        // Una sola consulta para todo el periodo: pedirlo campaña a campaña
        // era un N+1 contra la base de PrestaShop (90 días = 90 JOINs).
        return $this->redemptions->forCampaigns($campaigns);
    }

    /**
     * @return array<string, int|string|null>
     */
    private function campaignTotals(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $campaigns = BirthdayCampaign::query()
            ->whereBetween('campaign_date', [$from->toDateString(), $to->toDateString()])
            ->get(['id', 'status', 'recipients_total', 'sent_count', 'failed_count', 'skipped_count']);

        return [
            'count' => $campaigns->count(),
            'recipients' => (int) $campaigns->sum('recipients_total'),
            'sent' => (int) $campaigns->sum('sent_count'),
            'failed' => (int) $campaigns->sum('failed_count'),
            'skipped' => (int) $campaigns->sum('skipped_count'),
            'completed' => $campaigns->where('status', BirthdayCampaign::STATUS_COMPLETED)->count(),
            'failed_campaigns' => $campaigns->where('status', BirthdayCampaign::STATUS_FAILED)->count(),
        ];
    }

    /**
     * Cumpleañeros por día, para la barra de tendencia del panel.
     *
     * @return array<int, array{date: string, label: string, sent: int, total: int}>
     */
    private function trend(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $rows = BirthdayCampaign::query()
            ->whereBetween('campaign_date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('campaign_date')
            ->get(['campaign_date', 'sent_count', 'recipients_total']);

        return $rows->map(fn (BirthdayCampaign $c): array => [
            'date' => $c->campaign_date->toDateString(),
            'label' => $c->campaign_date->format('d/m'),
            'sent' => (int) $c->sent_count,
            'total' => (int) $c->recipients_total,
        ])->all();
    }

    /**
     * Estado de la campaña de hoy, que es lo primero que quiere saber quien
     * abre el panel por la mañana.
     *
     * @return array<string, mixed>|null
     */
    private function today(): ?array
    {
        $campaign = BirthdayCampaign::query()
            ->whereDate('campaign_date', CarbonImmutable::today()->toDateString())
            ->first();

        if (! $campaign) {
            return null;
        }

        $nextAt = BirthdayRecipient::query()
            ->where('campaign_id', $campaign->id)
            ->where('status', BirthdayRecipient::STATUS_PENDING)
            ->min('scheduled_at');

        return [
            'id' => $campaign->id,
            'status' => $campaign->status,
            'progress' => $campaign->progressPercent(),
            'sent' => (int) $campaign->sent_count,
            'total' => (int) $campaign->recipients_total,
            'pending' => $campaign->pendingCount(),
            'next_at' => $nextAt,
            'coupon_code' => $campaign->coupon_code,
        ];
    }

    /**
     * Cuántos cumpleañeros esperan cada uno de los próximos días, según las
     * campañas ya preparadas. Sirve para anticipar un día de mucho volumen.
     *
     * @return array<int, array{date: string, recipients: int}>
     */
    public function upcoming(int $days = 7): array
    {
        return BirthdayCampaign::query()
            ->whereDate('campaign_date', '>', CarbonImmutable::today()->toDateString())
            ->whereDate('campaign_date', '<=', CarbonImmutable::today()->addDays($days)->toDateString())
            ->orderBy('campaign_date')
            ->get(['campaign_date', 'recipients_total'])
            ->map(fn (BirthdayCampaign $c): array => [
                'date' => $c->campaign_date->toDateString(),
                'recipients' => (int) $c->recipients_total,
            ])->all();
    }

    /**
     * Motivos por los que se omitió gente, agregados. Explica la diferencia
     * entre "cumpleañeros encontrados" y "correos enviados".
     *
     * @return array<string, int>
     */
    public function skipReasons(int $days = 30): array
    {
        $from = CarbonImmutable::now()->subDays($days)->startOfDay();

        return BirthdayRecipient::query()
            ->join('helpdesk_birthday_campaigns as c', 'c.id', '=', 'helpdesk_birthday_recipients.campaign_id')
            ->where('helpdesk_birthday_recipients.status', BirthdayRecipient::STATUS_SKIPPED)
            ->whereDate('c.campaign_date', '>=', $from->toDateString())
            ->select('helpdesk_birthday_recipients.skip_reason', DB::raw('COUNT(*) as total'))
            ->groupBy('helpdesk_birthday_recipients.skip_reason')
            ->pluck('total', 'skip_reason')
            ->map(static fn ($v): int => (int) $v)
            ->all();
    }
}
