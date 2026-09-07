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
    /**
     * Desglose de la audiencia de la preparación en curso.
     *
     * Se guarda aparte para que una campaña que aborta a mitad pueda explicar
     * igualmente en el panel a cuánta gente habría escrito: es lo único que
     * queda de un día que ya no se puede reconstruir.
     *
     * @var array<string, int>|null
     */
    private ?array $lastAudienceStats = null;

    public function __construct(
        private readonly BirthdayAudienceStatsService $audienceStats,
        private readonly BirthdayBonoGenerator $bonos,
        private readonly BirthdayAudienceService $audience,
        private readonly BirthdayScheduleCalculator $calculator,
        private readonly BirthdaySettings $settings,
    ) {}

    /**
     * Deja lista la campaña de $date: bonos emitidos, destinatarios cargados y
     * cada uno con su hora de envío.
     *
     * Es idempotente por el UNIQUE de campaign_date: si ya existe una campaña
     * que pasó de draft, no se toca (no vamos a recargar destinatarios de algo
     * que ya está enviando). La excepción es una campaña `failed` de un día que
     * todavía no ha pasado: esa sí se reintenta, porque el motivo del fallo
     * suele ser un ERP que no respondía hace un rato.
     *
     * Cualquier excepción se convierte en una campaña `failed` con su aviso: si
     * se dejara subir, el comando muere de madrugada y el día entero de
     * cumpleaños pasa sin que nadie se entere. Ha ocurrido.
     */
    public function prepare(CarbonImmutable $date): BirthdayCampaign
    {
        // La campaña nace ya con su plantilla y su ventana. Creándola solo con
        // la fecha, cualquier fallo posterior dejaba una fila muda —sin estilo
        // de correo, sin horario, sin nada— que en el panel no se distinguía de
        // un error de datos. Pasó con la del 6-sep.
        $settings = $this->settings->all();

        $campaign = BirthdayCampaign::firstOrCreate(
            ['campaign_date' => $date->toDateString()],
            [
                'status' => BirthdayCampaign::STATUS_DRAFT,
                'template_key' => $settings['template_key'],
                'window_start' => $settings['window_start'].':00',
                'window_end' => $settings['window_end'].':00',
                'throttle_per_hour' => (int) $settings['throttle_per_hour'],
            ]
        );

        if (! $this->isPreparable($campaign, $date)) {
            Log::info('[HelpdeskBirthday] La campaña del día ya estaba preparada', [
                'date' => $date->toDateString(),
                'status' => $campaign->status,
            ]);

            return $campaign;
        }

        try {
            return $this->buildCampaign($campaign, $date);
        } catch (BirthdayAudienceException $e) {
            return $this->fail($campaign, $e->getMessage(), $this->lastAudienceStats);
        } catch (Throwable $e) {
            // Un timeout del ERP, Redis caído, un error de plantilla… da igual
            // cuál sea: lo que no puede pasar es que el fallo sea mudo.
            Log::error('[HelpdeskBirthday] Excepción preparando la campaña', [
                'date' => $date->toDateString(),
                'exception' => $e::class,
                'error' => $e->getMessage(),
            ]);

            return $this->fail(
                $campaign,
                'No se pudo preparar la campaña: '.$e->getMessage(),
                $this->lastAudienceStats,
            );
        }
    }

    /**
     * Una campaña se prepara desde `draft`, y se reintenta desde `failed`
     * mientras siga siendo su día: el fallo típico es un ERP que no respondía a
     * las 6 de la mañana y sí responde a las 7.
     */
    private function isPreparable(BirthdayCampaign $campaign, CarbonImmutable $date): bool
    {
        if ($campaign->status === BirthdayCampaign::STATUS_DRAFT) {
            return true;
        }

        return $campaign->status === BirthdayCampaign::STATUS_FAILED
            && $date->isSameDay(CarbonImmutable::today())
            && $campaign->recipients()->count() === 0;
    }

    private function buildCampaign(BirthdayCampaign $campaign, CarbonImmutable $date): BirthdayCampaign
    {
        $settings = $this->settings->all();

        // Antes que nada, y aunque la campaña acabe abortando: así una campaña
        // fallida sigue pudiendo explicar en el panel a cuánta gente habría
        // escrito y por qué el resto quedaba fuera. Es una lectura, no envía
        // nada.
        $audienceStats = $this->lastAudienceStats = $this->audienceStats->forDate($date);

        // No hay cupón de campaña. El bono lo emite Gestión POR CLIENTE
        // (POST /api-gestion/generacion-bono/): cada persona recibe el suyo, con
        // su código de verificación, su importe y su validez. Lo único que se
        // configura aquí es el TIPO de bono.
        //
        // Sin tipo configurado nadie puede recibir un regalo, pero eso NO es
        // motivo para tirar la audiencia: quién cumple años hoy es un dato que
        // caduca —mañana ya no se puede reconstruir— y con él en la mano se ve a
        // cuánta gente afecta el ajuste que falta. La campaña se prepara igual y
        // queda EN PAUSA: reúne y programa, pero no envía.
        $sinCupones = ! $this->bonos->isConfigured();

        $recipients = $this->audience->fetchForDate($date, $this->settings->exclusions());

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

        DB::connection('helpdesk')->transaction(function () use ($campaign, $recipients, $plan, $settings, $skipped, $audienceStats, $sinCupones): void {
            $campaign->fill([
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
                'status' => $sinCupones
                    ? BirthdayCampaign::STATUS_PAUSED
                    : BirthdayCampaign::STATUS_SCHEDULED,
                'error_message' => $sinCupones
                    ? 'En pausa: falta el tipo de bono en Ajustes, así que Gestión no puede emitir el bono de cada cliente. Los cumpleañeros ya están reunidos y no se enviará nada hasta que lo configures.'
                    : null,
            ])->save();

            $this->storeRecipients($campaign, $recipients, $plan);
        });

        // Los bonos, ya con los destinatarios en la BD y FUERA de la
        // transacción: son varias llamadas HTTP a Gestión (una por cada 100
        // clientes) y tenerlas dentro mantendría abierta una transacción
        // durante minutos, bloqueando la tabla para el resto.
        $bonos = $this->generateBonos($campaign);

        // Una campaña que no puede regalar nada no envía, y eso hay que
        // decirlo: el efecto para el cliente es el mismo que si hubiera
        // fallado —no recibe su felicitación— y el día no se repite.
        if ($sinCupones) {
            $this->notifyFailure($campaign, (string) $campaign->error_message);
        }

        Log::info('[HelpdeskBirthday] Campaña preparada', [
            'date' => $date->toDateString(),
            'total' => count($recipients),
            'sendable' => count($sendable),
            'skipped' => $skipped,
            'bonos' => $bonos,
            'interval_seconds' => $plan->intervalSeconds,
            'overflows_window' => $plan->overflowsWindow(),
        ]);

        return $campaign->refresh();
    }

    /**
     * Pide a Gestión el bono de cada destinatario y aparta a quien se quedó
     * sin él.
     *
     * Es el paso que convierte una lista de cumpleañeros en una campaña
     * enviable. Sin él los destinatarios quedan sin `coupon_code`, y un correo
     * de cumpleaños sin el regalo dentro no se puede repetir al día siguiente.
     *
     * Quien se queda sin bono pasa a `skipped/no_coupon` en vez de quedarse
     * `pending`: si se quedara pendiente, dispatch-due lo reservaría cada
     * minuto, el job lo devolvería a pendiente y la campaña no cerraría nunca.
     * Aparcado como omitido se ve en el panel, se explica el motivo y se puede
     * reintentar a mano.
     *
     * @return array{generated: int, failed: int}|null null si no hay nada que emitir
     */
    private function generateBonos(BirthdayCampaign $campaign): ?array
    {
        if (! $this->bonos->isConfigured()) {
            return null;
        }

        $pending = $campaign->recipients()
            ->where('status', BirthdayRecipient::STATUS_PENDING)
            ->get();

        if ($pending->isEmpty()) {
            return null;
        }

        $result = $this->bonos->generateFor(
            $pending,
            sprintf('Cumpleaños %s', $campaign->campaign_date?->format('d/m/Y') ?? ''),
        );

        // A quien Gestión no le emitió bono no se le escribe. Su fila conserva
        // el motivo en coupon_error, que es lo que se enseña en el panel.
        $sinBono = $campaign->recipients()
            ->where('status', BirthdayRecipient::STATUS_PENDING)
            ->whereNull('coupon_code')
            ->update([
                'status' => BirthdayRecipient::STATUS_SKIPPED,
                'skip_reason' => BirthdayRecipient::SKIP_NO_COUPON,
                'scheduled_at' => null,
                'updated_at' => now(),
            ]);

        if ($sinBono > 0) {
            $campaign->increment('skipped_count', $sinBono);

            Log::warning('[HelpdeskBirthday] Destinatarios sin bono emitido', [
                'campaign_id' => $campaign->id,
                'count' => $sinBono,
            ]);
        }

        // Nadie recibió bono: la campaña no puede enviar nada. Se pausa con el
        // motivo a la vista en vez de dejarla "programada" sin salida.
        if ($campaign->recipients()->withCoupon()->count() === 0) {
            $motivo = 'Gestión no emitió ningún bono para esta campaña. Revisa el tipo de bono en Ajustes y reintenta la generación.';

            $campaign->update([
                'status' => BirthdayCampaign::STATUS_PAUSED,
                'error_message' => $motivo,
            ]);

            $this->notifyFailure($campaign, $motivo);
        }

        return ['generated' => $result['generated'], 'failed' => $result['failed']];
    }

    /**
     * Reintenta la emisión para los que se quedaron sin bono, sin tocar a
     * quienes ya lo tienen: regenerarlos les cambiaría el código de uno que ya
     * puede estar en su buzón.
     *
     * @return array{generated: int, failed: int}
     */
    public function retryBonos(BirthdayCampaign $campaign): array
    {
        $sinBono = $campaign->recipients()
            ->whereIn('status', [BirthdayRecipient::STATUS_PENDING, BirthdayRecipient::STATUS_SKIPPED])
            ->where(fn ($q) => $q->whereNull('skip_reason')->orWhere('skip_reason', BirthdayRecipient::SKIP_NO_COUPON))
            ->whereNull('coupon_code')
            ->get();

        if ($sinBono->isEmpty()) {
            return ['generated' => 0, 'failed' => 0];
        }

        $result = $this->bonos->generateFor(
            $sinBono,
            sprintf('Cumpleaños %s', $campaign->campaign_date?->format('d/m/Y') ?? ''),
        );

        // Los que ya tienen bono vuelven a la cola con hora de ahora: el
        // reparto original ya pasó y esperar a mañana sería no enviarlo.
        $recovered = $campaign->recipients()
            ->whereIn('id', $sinBono->pluck('id'))
            ->withCoupon()
            ->update([
                'status' => BirthdayRecipient::STATUS_PENDING,
                'skip_reason' => null,
                'scheduled_at' => now(),
                'updated_at' => now(),
            ]);

        if ($recovered > 0) {
            $campaign->update([
                'skipped_count' => max(0, $campaign->skipped_count - $recovered),
                // Una campaña pausada por falta de bonos vuelve a estar viva:
                // si no, nadie recogería a los que acaban de recibir el suyo.
                'status' => $campaign->isActive() ? $campaign->status : BirthdayCampaign::STATUS_SCHEDULED,
                'error_message' => null,
                'finished_at' => null,
            ]);
        }

        return $result;
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
     * Cierra una campaña a la que se le pasó el día.
     *
     * Una felicitación de cumpleaños tiene fecha de caducidad: llega el mismo
     * día o no llega. Si el worker estuvo caído toda la tarde, mandar mañana
     * los correos de hoy es peor que no mandarlos — el cliente recibe un «feliz
     * cumpleaños» con dos días de retraso y un bono que ya lleva tiempo
     * corriendo.
     *
     * Sin esto, los pendientes se quedaban en la cola indefinidamente: la
     * campaña del día siguiente convivía con la anterior y ninguna de las dos
     * cerraba nunca.
     *
     * @return int cuántos destinatarios se quedaron sin felicitación
     */
    public function expireIfOverdue(BirthdayCampaign $campaign, ?CarbonImmutable $now = null): int
    {
        $now ??= CarbonImmutable::now();
        $graceHours = max(0, (int) config('helpdeskbirthday.expire_after_hours', 6));

        // El plazo se cuenta desde el final del día de la campaña en hora local
        // (que es la del cumpleaños), más la gracia configurada para que un
        // atasco de última hora todavía pueda resolverse.
        $deadline = $campaign->campaign_date
            ?->copy()
            ->endOfDay()
            ->addHours($graceHours);

        if ($deadline === null || $now->lessThan($deadline)) {
            return 0;
        }

        $expired = 0;

        DB::connection('helpdesk')->transaction(function () use ($campaign, &$expired): void {
            $expired = $campaign->recipients()
                ->unfinished()
                ->update([
                    'status' => BirthdayRecipient::STATUS_SKIPPED,
                    'skip_reason' => BirthdayRecipient::SKIP_EXPIRED,
                    'updated_at' => now(),
                ]);

            $campaign->update([
                'status' => BirthdayCampaign::STATUS_COMPLETED,
                'skipped_count' => $campaign->skipped_count + $expired,
                'finished_at' => $campaign->finished_at ?? now(),
            ]);
        });

        if ($expired > 0) {
            Log::warning('[HelpdeskBirthday] Campaña caducada con envíos sin salir', [
                'campaign_id' => $campaign->id,
                'date' => $campaign->campaign_date?->toDateString(),
                'expired' => $expired,
            ]);
        }

        return $expired;
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
