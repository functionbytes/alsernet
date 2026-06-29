<?php

namespace Modules\CampaignSendingServers\Models;

use Symfony\Component\Mailer\Transport\TransportInterface;

/**
 * SparkPost SMTP relay.
 *
 * Host: smtp.sparkpostmail.com (US) o smtp.eu.sparkpostmail.com (EU).
 * Puerto 587 TLS. Username SMTP siempre `SMTP_Injection`. Password = API key.
 */
class SendingServerSparkPostSmtp extends SendingServerSmtp
{
    protected $table = 'campaign_sending_servers';

    public function createSymfonyTransport(): TransportInterface
    {
        $this->host = $this->host ?: 'smtp.sparkpostmail.com';
        $this->smtp_port = $this->smtp_port ?: 587;
        $this->smtp_protocol = $this->smtp_protocol ?: 'tls';
        $this->smtp_username = $this->smtp_username ?: 'SMTP_Injection';

        return parent::createSymfonyTransport();
    }
}
