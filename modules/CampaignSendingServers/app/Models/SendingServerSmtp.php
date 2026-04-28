<?php

namespace Modules\CampaignSendingServers\Models;

use Exception;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mailer\Transport\TransportInterface;

class SendingServerSmtp extends SendingServer
{
    protected $table = 'campaign_sending_servers';

    public function createSymfonyTransport(): TransportInterface
    {
        $tls = strtolower((string) $this->smtp_protocol) === 'tls';
        $transport = new EsmtpTransport(
            (string) $this->host,
            (int) ($this->smtp_port ?: 587),
            $tls
        );

        if (! empty($this->smtp_username)) {
            $transport->setUsername((string) $this->smtp_username);
        }
        if (! empty($this->smtp_password)) {
            $transport->setPassword((string) $this->smtp_password);
        }

        return $transport;
    }

    public function send($email, array $params = []): array
    {
        $transport = $this->createSymfonyTransport();
        $mailer = new Mailer($transport);

        try {
            $mailer->send($email);

            return ['status' => self::DELIVERY_STATUS_SENT];
        } catch (\Throwable $e) {
            throw new Exception(sprintf(
                'Error SMTP al enviar desde "%s" (servidor "%s"): %s',
                $this->default_from_email ?? '#',
                $this->name,
                $e->getMessage()
            ), 0, $e);
        }
    }

    public function test(): bool
    {
        $transport = $this->createSymfonyTransport();
        if (method_exists($transport, 'start')) {
            $transport->start();
        }

        return true;
    }
}
