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

        BirthdayCampaign::query()->active()->each(function (BirthdayCampaign $campaign) use ($campaigns, &$closed): void {
            if ($campaigns->finalizeIfDone($campaign)) {
                $closed++;
            }
        });

        $purged = $this->purgeOld();

        $this->info("Campañas cerradas: {$closed}. Campañas purgadas: {$purged}.");

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
