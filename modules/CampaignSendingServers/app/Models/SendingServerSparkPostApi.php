<?php

namespace Modules\CampaignSendingServers\Models;

use Exception;
use Modules\CampaignSendingServers\Library\Traits\ApiSenderTrait;
use Symfony\Component\Mime\Email;

/**
 * SparkPost HTTP API.
 *
 * @see https://developers.sparkpost.com/api/transmissions/
 *
 * Credenciales: api_key (Authorization). aws_region 'eu' para EU.
 */
class SendingServerSparkPostApi extends SendingServer
{
    use ApiSenderTrait;

    protected $table = 'campaign_sending_servers';

    public function send($email, array $params = []): array
    {
        if (! $email instanceof Email) {
            throw new Exception('SparkPost API requires Symfony Email instance');
        }

        $payload = $this->emailToPayload($email);
        $base = $this->getApiBase();

        $body = [
            'recipients' => [['address' => ['email' => $payload['to_email'], 'name' => $payload['to_name']]]],
            'content' => array_filter([
                'from' => array_filter([
                    'email' => $payload['from_email'],
                    'name' => $payload['from_name'],
                ]),
                'reply_to' => $payload['reply_to'] ?: null,
                'subject' => $payload['subject'],
                'html' => $payload['html'] ?: null,
                'text' => $payload['text'] ?: null,
                'headers' => $payload['headers'] ?: null,
            ]),
        ];

        if (! empty($payload['attachments'])) {
            $body['content']['attachments'] = array_map(fn ($a) => [
                'name' => $a['name'],
                'type' => $a['type'],
                'data' => $a['content'],
            ], $payload['attachments']);
        }

        $response = $this->http()
            ->withHeaders(['Authorization' => (string) $this->api_key])
            ->post("{$base}/transmissions", $body);

        if ($response->successful()) {
            return [
                'status' => self::DELIVERY_STATUS_SENT,
                'runtime_message_id' => $response->json('results.id'),
            ];
        }

        throw new Exception('SparkPost API error: '.$response->status().' '.$response->body());
    }

    public function test(): bool
    {
        $base = $this->getApiBase();
        $response = $this->http()
            ->withHeaders(['Authorization' => (string) $this->api_key])
            ->get("{$base}/account");

        if (! $response->successful()) {
            throw new Exception('SparkPost auth failed: '.$response->status().' '.$response->body());
        }

        return true;
    }

    protected function getApiBase(): string
    {
        $region = strtolower((string) ($this->aws_region ?: ''));

        return str_contains($region, 'eu')
            ? 'https://api.eu.sparkpost.com/api/v1'
            : 'https://api.sparkpost.com/api/v1';
    }
}
