<?php

namespace Modules\HelpdeskBirthday\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\HelpdeskBirthday\Jobs\SendBirthdayEmailJob;
use Modules\HelpdeskBirthday\Models\BirthdayCampaign;
use Modules\HelpdeskBirthday\Models\BirthdayRecipient;
use Modules\HelpdeskBirthday\Services\BirthdayCampaignService;

/**
 * Motor del escalonado: cada minuto recoge los destinatarios a los que ya les
 * tocaba salir y encola su correo.
 *
 * Que el reparto viva en scheduled_at y no en jobs con ->delay() es lo que hace
 * que pausar funcione de verdad: aquí se comprueba el estado de la campaña en
 * cada pasada, así que una pausa surte efecto en menos de un minuto sin tener
 * que rescatar nada de Redis.
 */
class DispatchDueBirthdayEmails extends Command
{
    protected $signature = 'helpdeskbirthday:dispatch-due
                            {--limit= : Máximo de correos a encolar en esta pasada}';

    protected $description = 'Encola los correos de cumpleaños cuya hora de envío ya ha llegado';

    public function handle(BirthdayCampaignService $campaigns): int
    {
        if (! helpdesk_birthday_enabled()) {
            return self::SUCCESS;
        }

        $limit = (int) ($this->option('limit') ?: config('helpdeskbirthday.dispatch_batch_size', 100));
        $dispatched = 0;

        $active = BirthdayCampaign::query()->active()->orderBy('campaign_date')->get();

        foreach ($active as $campaign) {
            // Antes de encolar nada: a una campaña de ayer ya no se le envía.
            // Un «feliz cumpleaños» con un día de retraso es peor que ninguno,
            // y sin este corte los pendientes de ayer salían hoy mezclados con
            // los de hoy.
            $campaigns->expireIfOverdue($campaign);

            if (! $campaign->isActive()) {
                continue;
            }

            $dispatched += $this->dispatchFor($campaign, $limit - $dispatched);

            if ($campaign->pendingCount() === 0) {
                $campaigns->finalizeIfDone($campaign);
            }

            if ($dispatched >= $limit) {
                break;
            }
        }

        if ($dispatched > 0) {
            $this->info("Encolados {$dispatched} correos de cumpleaños.");
        }

        return self::SUCCESS;
    }

    /**
     * Reserva los destinatarios vencidos marcándolos 'sending' antes de encolar.
     *
     * El UPDATE ... WHERE status = 'pending' es la reserva atómica: si dos
     * pasadas se solapan (o corren en dos nodos), solo una se lleva cada fila y
     * nadie recibe el correo dos veces.
     */
    private function dispatchFor(BirthdayCampaign $campaign, int $limit): int
    {
        if ($limit <= 0) {
            return 0;
        }

        $ids = $campaign->recipients()
            ->due()
            // Solo quien tiene su bono emitido, salvo que la promoción reparta
            // un código único para todos (el de la campaña). Encolar a quien no
            // lo tiene era un bucle: el job lo devolvía a pendiente y la
            // siguiente pasada volvía a cogerlo, un minuto tras otro.
            ->when(! $campaign->coupon_code, fn ($q) => $q->withCoupon())
            ->orderBy('scheduled_at')
            ->limit($limit)
            ->pluck('id')
            ->all();

        if ($ids === []) {
            return 0;
        }

        $reserved = [];

        DB::connection('helpdesk')->transaction(function () use ($ids, &$reserved): void {
            foreach ($ids as $id) {
                $claimed = BirthdayRecipient::query()
                    ->whereKey($id)
                    ->where('status', BirthdayRecipient::STATUS_PENDING)
                    ->update([
                        'status' => BirthdayRecipient::STATUS_SENDING,
                        'updated_at' => now(),
                    ]);

                if ($claimed === 1) {
                    $reserved[] = $id;
                }
            }
        });

        if ($reserved === []) {
            return 0;
        }

        // Marca el arranque real de la campaña la primera vez que sale algo.
        if ($campaign->status === BirthdayCampaign::STATUS_SCHEDULED) {
            $campaign->update([
                'status' => BirthdayCampaign::STATUS_SENDING,
                'started_at' => $campaign->started_at ?? now(),
            ]);
        }

        foreach ($reserved as $id) {
            SendBirthdayEmailJob::dispatch($id);
        }

        return count($reserved);
    }
}
