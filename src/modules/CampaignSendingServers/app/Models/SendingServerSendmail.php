<?php

namespace Modules\CampaignSendingServers\Models;

use Exception;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport\SendmailTransport;
use Symfony\Component\Mailer\Transport\TransportInterface;

class SendingServerSendmail extends SendingServer
{
    protected $table = 'campaign_sending_servers';

    public function createSymfonyTransport(): TransportInterface
    {
        $cmd = $this->sendmail_path
            ? rtrim((string) $this->sendmail_path).' -bs'
            : null;

        return new SendmailTransport($cmd);
    }

    public function send($email, array $params = []): array
    {
        $mailer = new Mailer($this->createSymfonyTransport());

        try {
            $mailer->send($email);

            return ['status' => self::DELIVERY_STATUS_SENT];
        } catch (\Throwable $e) {
            throw new Exception('Error Sendmail: '.$e->getMessage(), 0, $e);
        }
    }

    public function test(): bool
    {
        if (empty($this->sendmail_path)) {
            throw new Exception('sendmail_path no configurado');
        }
        if (! file_exists($this->sendmail_path)) {
            throw new Exception("Binario {$this->sendmail_path} no existe");
        }
        if (! is_executable($this->sendmail_path)) {
            throw new Exception("Binario {$this->sendmail_path} no es ejecutable");
        }

        return true;
    }
}
