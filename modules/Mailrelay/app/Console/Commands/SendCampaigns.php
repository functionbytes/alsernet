<?php

namespace Modules\Mailrelay\Console\Commands;

use Illuminate\Console\Command;
use Modules\Mailrelay\Services\MailRelayService;

class SendCampaigns extends Command
{
    protected $signature = 'send:campaigns';

    protected $description = 'Enviar campañas programadas';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(MailRelayService $mailRelayService)
    {
        // Lógica para enviar campañas, invoca el servicio de MailRelay
        $response = $mailRelayService->sendCampaigns(); // Asegúrate de que esta función exista en el servicio

        $this->info('Campañas enviadas con éxito!');
    }
}
