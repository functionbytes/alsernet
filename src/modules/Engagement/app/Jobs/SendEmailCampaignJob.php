<?php

declare(strict_types=1);

namespace Modules\Engagement\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Engagement\Models\EmailCampaign;
use Modules\Engagement\Services\Email\EmailCampaignService;

class SendEmailCampaignJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(
        public EmailCampaign $campaign,
    ) {}

    public function handle(EmailCampaignService $service): void
    {
        if ($this->campaign->status !== 'scheduled' && $this->campaign->status !== 'draft') {
            return;
        }

        $service->send($this->campaign);
    }

    public function failed(\Throwable $exception): void
    {
        $this->campaign->update([
            'status' => 'failed',
        ]);

        report($exception);
    }
}
