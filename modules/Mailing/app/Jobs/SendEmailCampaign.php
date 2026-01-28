<?php

namespace Modules\Mailrelay\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Mailrelay\Services\MailRelayService;

class SendEmailCampaign implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $campaignData;

    public function __construct($campaignData)
    {
        $this->campaignData = $campaignData;
    }

    public function handle(MailRelayService $mailRelayService)
    {
        // Lógica para enviar la campaña usando el servicio
        $mailRelayService->sendEmailCampaign($this->campaignData);
    }
}
