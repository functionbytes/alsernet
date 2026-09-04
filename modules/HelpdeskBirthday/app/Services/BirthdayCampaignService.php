<?php

namespace Modules\HelpdeskBirthday\Services;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Modules\HelpdeskBirthday\Exceptions\BirthdayAudienceException;
use Modules\HelpdeskBirthday\Models\BirthdayCampaign;
use Modules\HelpdeskBirthday\Models\BirthdayRecipient;
use Modules\HelpdeskBirthday\Notifications\BirthdayCampaignFailedNotification;
use Modules\HelpdeskBirthday\Support\BirthdaySettings;
use Throwable;

/**
 * Ciclo de vida de la campaña del día: prepararla y luego pausar, reanudar o
 * cancelar desde el panel.
 *
 * El envío en sí no vive aquí — lo hace DispatchDueBirthdayEmails leyendo los
 * scheduled_at que este servicio deja escritos.
 */
class BirthdayCampaignService
{
    public function __construct(
        private readonly BirthdayAudienceStatsService $audienceStats,
        private readonly BirthdayAudienceService $audience,
        private readonly BirthdayCouponService $coupons,
        private readonly BirthdayScheduleCalculator $calculator,
        private readonly BirthdaySettings $settings,
    ) {}

    /**
     * Deja lista la campaña de $date: cupón congelado, destinatarios cargados y
     * cada uno con su hora de envío.
     *
     * Es idempotente por el UNIQUE de campaign_date: si ya existe una campaña
     * que pasó de draft, no se toca (no vamos a recargar destinatarios de algo
     * que ya está enviando).
     */
    public function prepare(CarbonImmutable $date): BirthdayCampaign
    {
        $campaign = BirthdayCampaign::firstOrCreate(
            ['campaign_date' => $date->toDateString()],
            ['status' => BirthdayCampaign::STATUS_DRAFT]
        );

        if ($campaign->status !== BirthdayCampaign::STATUS_DRAFT) {
            Log::info('[HelpdeskBirthday] La campaña del día ya estaba preparada', [
                'date' => $date->toDateString(),
                'status' => $campaign->status,
            ]);

            return $campaign;
        }

        $settings = $this->settings->all();

        // Antes que nada, y aunque la campaña acabe abortando: así una campaña
        // fallida sigue pudiendo explicar en el panel a cuánta gente habría
        // escrito y por qué el resto quedaba fuera. Es una lectura, no envía
        // nada.
        $audienceStats = $this->audienceStats->forDate($date);

        $coupon = $this->coupons->resolve($settings);

        if ($coupon === []) {
            return $this->fail(
                $campaign,
                'No hay ningún código de cupón configurado: la campaña no se envía.',
                $audienceStats,
            );
        }

        try {
            $recipients = $this->audience->fetchForDate($date, $this->settings->exclusions());
        } catch (BirthdayAudienceException $e) {
            return $this->fail($campaign, $e->getMessage(), $audienceStats);
        }

        [$windowStart, $windowEnd] = $this->calculator->windowFor(
            $date,
            (string) $settings['window_start'],
            (string) $settings['window_end'],
        );

        // Los omitidos no ocupan hueco en el reparto: nunca se les va a enviar.
        $sendable = array_values(array_filter(
            $recipients,
            static fn (array $r): bool => $r['status'] === BirthdayRecipient::STATUS_PENDING
        ));

        $plan = $this->calculator->plan(
            count($sendable),
            $windowStart,
            $windowEnd,
            (int) $settings['throttle_per_hour'],
        );

        $skipped = count($recipients) - count($sendable);

        DB::connection('helpdesk')->transaction(function () use ($campaign, $coupon, $recipients, $plan, $settings, $skipped, $audienceStats): void {
            $campaign->fill($coupon + [
                'template_key' => $settings['template_key'],
                // Se guarda la hora tal como la configuró el usuario (hora de
                // negocio), no su equivalente UTC: es la que se enseña en el
                // panel, y un 07:00 ahí cuando pediste las 9 confunde a todos.
                // El UTC vive donde tiene que vivir: en los scheduled_at.
                'window_start' => $settings['window_start'].':00',
                'window_end' => $settings['window_end'].':00',
                'throttle_per_hour' => (int) $settings['throttle_per_hour'],
                'interval_seconds' => $plan->intervalSeconds,
                'recipients_total' => count($recipients),
                'skipped_count' => $skipped,
                'audience_stats' => $audienceStats,
                'status' => BirthdayCampaign::STATUS_SCHEDULED,
                'error_message' => null,
            ])->save();

            $this->storeRecipients($campaign, $recipients, $plan);
        });

        Log::info('[HelpdeskBirthday] Campaña preparada', [
            'date' => $date->toDateString(),
            'total' => count($recipients),
            'sendable' => count($sendable),
            'skipped' => $skipped,
            'interval_seconds' => $plan->intervalSeconds,
            'overflows_window' => $plan->overflowsWindow(),
        ]);

        return $campaign->refresh();
    }

    /**
     * Inserta destinatarios repartiendo scheduled_at solo entre los enviables.
     *
     * @param  array<int, array<string, mixed>>  $recipients
     */
    private function storeRecipients(BirthdayCampaign $campaign, array $recipients, BirthdaySchedulePlan $plan): void
    {
        $slot = 0;
        $now = now();
        $rows = [];

        foreach ($recipients as $recipient) {
            $isSendable = $recipient['status'] === BirthdayRecipient::STATUS_PENDING;

            $rows[] = [
                'campaign_id' => $campaign->id,
                'erp_customer_id' => $recipient['erp_customer_id'],
                'email' => $recipient['email'],
                'name' => $recipient['name'],
                'lang' => $recipient['lang'] ?? null,
                'birth_date' => $recipient['birth_date'],
                'scheduled_at' => $isSendable ? $plan->slotFor($slot++) : null,
                'status' => $recipient['status'],
                'skip_reason' => $recipient['skip_reason'],
                'attempts' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            // insertOrIgnore y no insert: el UNIQUE(campaign_id, email) es la
            // red contra un reintento a medias, y no queremos que reviente.
            BirthdayRecipient::query()->insertOrIgnore($chunk);
        }
    }

    public function pause(BirthdayCampaign $campaign): bool
    {
        if (! $campaign->canBePaused()) {
            return false;
        }

        $campaign->update(['status' => BirthdayCampaign::STATUS_PAUSED]);

        return true;
    }

    /**
     * Al reanudar, lo pendiente que ya venció sale en la siguiente pasada del
     * comando (scheduled_at <= now), sin reprogramar nada: si la pausa fue
     * larga, el ritmo lo sigue limitando el throttle del job.
     */
    public function resume(BirthdayCampaign $campaign): bool
    {
        if (! $campaign->canBeResumed()) {
            return false;
        }

        $campaign->update(['status' => BirthdayCampaign::STATUS_SCHEDULED]);

        return true;
    }

    public function cancel(BirthdayCampaign $campaign): bool
    {
        if (! $campaign->canBeCancelled()) {
            return false;
        }

        DB::connection('helpdesk')->transaction(function () use ($campaign): void {
            $cancelled = $campaign->recipients()
                ->where('status', BirthdayRecipient::STATUS_PENDING)
                ->update([
                    'status' => BirthdayRecipient::STATUS_SKIPPED,
                    'skip_reason' => 'cancelled',
                    'updated_at' => now(),
                ]);

            $campaign->update([
                'status' => BirthdayCampaign::STATUS_CANCELLED,
                'skipped_count' => $campaign->skipped_count + $cancelled,
                'finished_at' => now(),
            ]);
        });

        return true;
    }

    /**
     * Cierra la campaña cuando ya no queda nada por enviar.
     */
    public function finalizeIfDone(BirthdayCampaign $campaign): bool
    {
        if (! $campaign->isActive() || $campaign->pendingCount() > 0) {
            return false;
        }

        $campaign->update([
            'status' => BirthdayCampaign::STATUS_COMPLETED,
            'finished_at' => now(),
        ]);

        return true;
    }

    /**
     * @param  array<string, int>|null  $audienceStats  desglose de la audiencia, si se llegó a leer
     */
    private function fail(BirthdayCampaign $campaign, string $message, ?array $audienceStats = null): BirthdayCampaign
    {
        $campaign->update(array_filter([
            'status' => BirthdayCampaign::STATUS_FAILED,
            'error_message' => $message,
            'finished_at' => now(),
            'audience_stats' => $audienceStats,
        ], static fn ($value): bool => $value !== null));

        Log::error('[HelpdeskBirthday] Campaña abortada', [
            'date' => $campaign->campaign_date?->toDateString(),
            'reason' => $message,
        ]);

        $this->notifyFailure($campaign, $message);

        return $campaign;
    }

    /**
     * Avisa a quien pueda gestionar campañas. Sin esto el fallo es mudo:
     * `prepare` corre de madrugada y nadie se entera hasta abrir el panel.
     *
     * Nunca deja que un fallo de notificación tape el fallo original: si algo
     * revienta aquí, se loguea y se sigue.
     */
    private function notifyFailure(BirthdayCampaign $campaign, string $message): void
    {
        try {
            $recipients = User::query()
                ->whereHas('roles', fn ($q) => $q->whereIn('name', ['admin', 'super-admin', 'super-administrador', 'super-settings']))
                ->get()
                ->filter(fn (User $user): bool => $user->can('helpdeskbirthday.manage'));

            if ($recipients->isEmpty()) {
                return;
            }

            Notification::send($recipients, new BirthdayCampaignFailedNotification(
                campaignDate: $campaign->campaign_date?->format('d/m/Y') ?? '—',
                reason: $message,
                campaignId: $campaign->id,
            ));
        } catch (Throwable $e) {
            Log::warning('[HelpdeskBirthday] No se pudo avisar del fallo de la campaña', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
