<?php

namespace Modules\HelpdeskBirthday\Console\Commands;

use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Modules\HelpdeskBirthday\Models\BirthdayCampaign;
use Modules\HelpdeskBirthday\Models\BirthdayRecipient;
use Modules\HelpdeskBirthday\Services\BirthdayCampaignService;

/**
 * Cierre diario: marca como completadas las campañas sin pendientes y purga las
 * antiguas.
 *
 * También rescata destinatarios encallados en 'sending' — un worker que muere a
 * medio job deja la fila reservada para siempre, y sin esto ese cliente no
 * recibiría nada ni aparecería como fallido.
 */
class FinalizeBirthdayCampaigns extends Command
{
    protected $signature = 'helpdeskbirthday:finalize';

    protected $description = 'Cierra las campañas de cumpleaños terminadas y limpia las antiguas';

    /** Tiempo tras el cual un 'sending' se considera encallado. */
    private const STUCK_AFTER_MINUTES = 60;

    public function handle(BirthdayCampaignService $campaigns): int
    {
        if (! helpdesk_birthday_enabled()) {
            return self::SUCCESS;
        }

        $this->rescueStuck();

        $closed = 0;
        $expired = 0;

        // También las pausadas: una campaña que se quedó en pausa el martes no
        // debe poder reanudarse el viernes y soltar de golpe las felicitaciones
        // de aquel día.
        BirthdayCampaign::query()
            ->whereIn('status', [
                ...BirthdayCampaign::ACTIVE_STATUSES,
                BirthdayCampaign::STATUS_PAUSED,
            ])
            ->each(function (BirthdayCampaign $campaign) use ($campaigns, &$closed, &$expired): void {
                $caducados = $campaigns->expireIfOverdue($campaign);

                if ($caducados > 0) {
                    $expired += $caducados;

                    return;
                }

                if ($campaigns->finalizeIfDone($campaign)) {
                    $closed++;
                }
            });

        $abandoned = $this->purgeAbandonedDrafts();
        $purged = $this->purgeOld();

        if ($abandoned > 0) {
            $this->warn("Borradores de días pasados descartados: {$abandoned}.");
        }

        $this->info("Campañas cerradas: {$closed}. Envíos caducados: {$expired}. Campañas purgadas: {$purged}.");

        return self::SUCCESS;
    }

    private function rescueStuck(): void
    {
        $cutoff = now()->subMinutes(self::STUCK_AFTER_MINUTES);

        BirthdayRecipient::query()
            ->where('status', BirthdayRecipient::STATUS_SENDING)
            ->where('updated_at', '<', $cutoff)
            ->update([
                'status' => BirthdayRecipient::STATUS_PENDING,
                'updated_at' => now(),
            ]);
    }

    /**
     * Descarta los borradores de días ya pasados que nunca llegaron a reunir a
     * nadie.
     *
     * Son la huella de una preparación que murió a mitad: una campaña sin
     * destinatarios, sin cupones y sin nada que explicar, que además ocupa el
     * UNIQUE de su día. Borrarlas deja el listado diciendo la verdad —ese día
     * no hubo campaña— en vez de enseñar una fila vacía que parece un error de
     * datos.
     */
    private function purgeAbandonedDrafts(): int
    {
        return BirthdayCampaign::query()
            ->where('status', BirthdayCampaign::STATUS_DRAFT)
            ->whereDate('campaign_date', '<', CarbonImmutable::today()->toDateString())
            ->whereDoesntHave('recipients')
            ->delete();
    }

    private function purgeOld(): int
    {
        $days = (int) config('helpdeskbirthday.retention_days', 365);

        if ($days <= 0) {
            return 0;
        }

        // Los destinatarios caen solos por el cascadeOnDelete de la FK.
        return BirthdayCampaign::query()
            ->whereDate('campaign_date', '<', CarbonImmutable::today()->subDays($days))
            ->delete();
    }
}
