<?php

namespace Modules\CampaignSendingServers\Models;

use Aws\Ses\SesClient;
use Exception;
use Modules\CampaignSendingServers\Library\Traits\ApiSenderTrait;
use Symfony\Component\Mime\Email;

/**
 * Amazon SES vía AWS SDK (SendRawEmail).
 *
 * Requiere `aws/aws-sdk-php` instalado. Si no lo está, lanza excepción
 * descriptiva al primer envío indicando cómo añadirlo a composer.
 *
 * Credenciales: aws_access_key_id, aws_secret_access_key, aws_region.
 */
class SendingServerAmazonApi extends SendingServer
{
    use ApiSenderTrait;

    protected $table = 'campaign_sending_servers';

    public function send($email, array $params = []): array
    {
        if (! $email instanceof Email) {
            throw new Exception('SES API requires Symfony Email instance');
        }
        if (! class_exists(SesClient::class)) {
            throw new Exception('aws/aws-sdk-php no está instalado. Ejecuta: composer require aws/aws-sdk-php');
        }

        $client = $this->client();
        $raw = $email->toString();

        try {
            $result = $client->sendRawEmail([
                'RawMessage' => ['Data' => $raw],
            ]);

            return [
                'status' => self::DELIVERY_STATUS_SENT,
                'runtime_message_id' => $result['MessageId'] ?? null,
            ];
        } catch (\Throwable $e) {
            throw new Exception('SES API error: '.$e->getMessage(), 0, $e);
        }
    }

    public function test(): bool
    {
        if (! class_exists(SesClient::class)) {
            throw new Exception('aws/aws-sdk-php no está instalado.');
        }

        try {
            $this->client()->getSendQuota();

            return true;
        } catch (\Throwable $e) {
            throw new Exception('SES auth failed: '.$e->getMessage(), 0, $e);
        }
    }

    protected function client(): SesClient
    {
        return new SesClient([
            'version' => 'latest',
            'region' => $this->aws_region ?: 'us-east-1',
            'credentials' => [
                'key' => (string) $this->aws_access_key_id,
                'secret' => (string) $this->aws_secret_access_key,
            ],
        ]);
    }
}
