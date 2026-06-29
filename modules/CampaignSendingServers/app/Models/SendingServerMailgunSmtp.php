<?php

namespace Modules\CampaignSendingServers\Models;

use Symfony\Component\Mailer\Transport\TransportInterface;

/**
 * Mailgun SMTP relay.
 *
 * Host: smtp.mailgun.org (US) o smtp.eu.mailgun.org (EU).
 * Puerto 587 TLS. Username/password se generan en la consola Mailgun
 * por dominio (NO son la API key).
 */
class SendingServerMailgunSmtp extends SendingServerSmtp
{
    protected $table = 'campaign_sending_servers';

    public function createSymfonyTransport(): TransportInterface
    {
        $this->host = $this->host ?: 'smtp.mailgun.org';
        $this->smtp_port = $this->smtp_port ?: 587;
        $this->smtp_protocol = $this->smtp_protocol ?: 'tls';

        return parent::createSymfonyTransport();
    }
}
