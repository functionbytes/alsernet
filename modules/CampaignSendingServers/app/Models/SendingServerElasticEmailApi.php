<?php

namespace Modules\CampaignSendingServers\Models;

use Exception;
use Modules\CampaignSendingServers\Library\Traits\ApiSenderTrait;
use Symfony\Component\Mime\Email;

/**
 * ElasticEmail HTTP API v4.
 *
 * @see https://elasticemail.com/developers/api-documentation/rest-api
 *
 * Credenciales: api_key (header `X-ElasticEmail-ApiKey`).
 */
class SendingServerElasticEmailApi extends SendingServer
{
    use ApiSenderTrait;

    protected $table = 'campaign_sending_servers';

    public function send($email, array $params = []): array
    {
        if (! $email instanceof Email) {
            throw new Exception('ElasticEmail API requires Symfony Email instance');
        }

        $payload = $this->emailToPayload($email);

        $body = [
            'Recipients' => [
                ['Email' => $payload['to_email']],
            ],
            'Content' => array_filter([
                'Body' => array_values(array_filter([
                    $payload['html'] ? ['ContentType' => 'HTML', 'Content' => $payload['html'], 'Charset' => 'utf-8'] : null,
                    $payload['text'] ? ['ContentType' => 'PlainText', 'Content' => $payload['text'], 'Charset' => 'utf-8'] : null,
                ])),
                'From' => $payload['from_name']
                    ? sprintf('%s <%s>', $payload['from_name'], $payload['from_email'])
                    : $payload['from_email'],
                'ReplyTo' => $payload['reply_to'] ?: null,
                'Subject' => $payload['subject'],
                'Headers' => $payload['headers'] ?: null,
            ]),
        ];

        if (! empty($payload['attachments'])) {
            $body['Content']['Attachments'] = array_map(fn ($a) => [
                'Name' => $a['name'],
                'BinaryContent' => $a['content'],
                'ContentType' => $a['type'],
            ], $payload['attachments']);
        }

        $response = $this->http()
            ->withHeaders(['X-ElasticEmail-ApiKey' => (string) $this->api_key])
            ->post('https://api.elasticemail.com/v4/emails', $body);

        if ($response->successful()) {
            return [
                'status' => self::DELIVERY_STATUS_SENT,
                'runtime_message_id' => $response->json('MessageID'),
            ];
        }

        throw new Exception('ElasticEmail API error: '.$response->status().' '.$response->body());
    }

    public function test(): bool
    {
        $response = $this->http()
            ->withHeaders(['X-ElasticEmail-ApiKey' => (string) $this->api_key])
            ->get('https://api.elasticemail.com/v4/account/load');

        if (! $response->successful()) {
            throw new Exception('ElasticEmail auth failed: '.$response->status().' '.$response->body());
        }

        return true;
    }
}
