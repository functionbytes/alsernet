<?php

declare(strict_types=1);

namespace Modules\Engagement\Console\Commands;

use Illuminate\Console\Command;
use Modules\Engagement\Jobs\SendEmailCampaignJob;
use Modules\Engagement\Models\EmailCampaign;

class SendScheduledCampaignsCommand extends Command
{
    protected $signature = 'engagement:send-scheduled-campaigns';

    protected $description = 'Envía las campañas de email programadas cuya fecha ha llegado.';

    public function handle(): int
    {
        $campaigns = EmailCampaign::query()
            ->where('status', 'scheduled')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->get();

        if ($campaigns->isEmpty()) {
            $this->info('No hay campañas programadas para enviar.');

            return self::SUCCESS;
        }

        foreach ($campaigns as $campaign) {
            SendEmailCampaignJob::dispatch($campaign);
            $this->info("Job despachado para campaña: {$campaign->name}");
        }

        $this->info("{$campaigns->count()} campaña(s) despachada(s).");

        return self::SUCCESS;
    }
}
