<?php

namespace Modules\CampaignSendingServers\Models;

use Exception;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mailer\Transport\TransportInterface;

/**
 * Servidor de envío Amazon SES vía SMTP.
 *
 * Usa las credenciales SMTP que SES genera en su consola
 * (NO los AWS Access Keys de IAM directamente — son distintos).
 *
 * El host varía por región: email-smtp.<region>.amazonaws.com
 */
class SendingServerAmazonSmtp extends SendingServer
{
    protected $table = 'campaign_sending_servers';

    public function createSymfonyTransport(): TransportInterface
    {
        // Si no se ha configurado host pero sí región, lo deducimos.
        $host = $this->host ?: ($this->aws_region
            ? "email-smtp.{$this->aws_region}.amazonaws.com"
            : null);

        if (empty($host)) {
            throw new Exception('Host de Amazon SES no determinado (configurar host o aws_region)');
        }

        $tls = strtolower((string) ($this->smtp_protocol ?: 'tls')) === 'tls';
        $transport = new EsmtpTransport($host, (int) ($this->smtp_port ?: 587), $tls);

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
        $mailer = new Mailer($this->createSymfonyTransport());

        try {
            $sent = $mailer->send($email);

            $messageId = null;
            if ($sent && method_exists($sent, 'getMessageId')) {
                $messageId = $sent->getMessageId();
            }

            return [
                'status' => self::DELIVERY_STATUS_SENT,
                'runtime_message_id' => $messageId,
            ];
        } catch (\Throwable $e) {
            throw new Exception('Error Amazon SES SMTP: '.$e->getMessage(), 0, $e);
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
