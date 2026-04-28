<?php

namespace Modules\CampaignSendingServers\Models;

use Symfony\Component\Mailer\Transport\TransportInterface;

/**
 * SendGrid SMTP relay.
 *
 * Host fijo: smtp.sendgrid.net puerto 587 (TLS).
 * Username SMTP siempre `apikey` (literal). Password = la API key.
 */
class SendingServerSendGridSmtp extends SendingServerSmtp
{
    protected $table = 'campaign_sending_servers';

    public function createSymfonyTransport(): TransportInterface
    {
        $this->host = $this->host ?: 'smtp.sendgrid.net';
        $this->smtp_port = $this->smtp_port ?: 587;
        $this->smtp_protocol = $this->smtp_protocol ?: 'tls';
        $this->smtp_username = $this->smtp_username ?: 'apikey';

        return parent::createSymfonyTransport();
    }
}
