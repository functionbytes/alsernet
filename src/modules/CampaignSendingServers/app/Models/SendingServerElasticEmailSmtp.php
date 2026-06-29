<?php

namespace Modules\CampaignSendingServers\Models;

use Symfony\Component\Mailer\Transport\TransportInterface;

/**
 * ElasticEmail SMTP relay.
 *
 * Host: smtp.elasticemail.com puerto 2525 (TLS) o 25/587. Username = email
 * de la cuenta. Password = SMTP password generado en la consola.
 */
class SendingServerElasticEmailSmtp extends SendingServerSmtp
{
    protected $table = 'campaign_sending_servers';

    public function createSymfonyTransport(): TransportInterface
    {
        $this->host = $this->host ?: 'smtp.elasticemail.com';
        $this->smtp_port = $this->smtp_port ?: 2525;
        $this->smtp_protocol = $this->smtp_protocol ?: 'tls';

        return parent::createSymfonyTransport();
    }
}
