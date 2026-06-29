<?php

namespace Modules\CampaignSendingServers\Models;

use Exception;
use Modules\CampaignSendingServers\Library\Traits\ApiSenderTrait;
use Symfony\Component\Mime\Email;

/**
 * SendGrid Web API v3.
 *
 * @see https://docs.sendgrid.com/api-reference/mail-send/mail-send
 *
 * Credencial: api_key (Bearer).
 */
class SendingServerSendGridApi extends SendingServer
{
    use ApiSenderTrait;

    protected $table = 'campaign_sending_servers';

    public function send($email, array $params = []): array
    {
        if (! $email instanceof Email) {
            throw new Exception('SendGrid API requires Symfony Email instance');
        }

        $payload = $this->emailToPayload($email);

        $body = [
            'personalizations' => [[
                'to' => [['email' => $payload['to_email'], 'name' => $payload['to_name']]],
            ]],
            'from' => array_filter([
                'email' => $payload['from_email'],
                'name' => $payload['from_name'],
            ]),
            'subject' => $payload['subject'],
            'content' => array_values(array_filter([
                $payload['text'] ? ['type' => 'text/plain', 'value' => $payload['text']] : null,
                $payload['html'] ? ['type' => 'text/html', 'value' => $payload['html']] : null,
            ])),
        ];

        if (! empty($payload['reply_to'])) {
            $body['reply_to'] = ['email' => $payload['reply_to']];
        }
        if (! empty($payload['headers'])) {
            $body['headers'] = $payload['headers'];
        }
        if (! empty($payload['attachments'])) {
            $body['attachments'] = array_map(fn ($a) => [
                'content' => $a['content'],
                'type' => $a['type'],
                'filename' => $a['name'],
                'disposition' => 'attachment',
            ], $payload['attachments']);
        }

        $response = $this->http()
            ->withToken((string) $this->api_key)
            ->post('https://api.sendgrid.com/v3/mail/send', $body);

        if ($response->successful()) {
            return [
                'status' => self::DELIVERY_STATUS_SENT,
                'runtime_message_id' => $response->header('X-Message-Id'),
            ];
        }

        throw new Exception('SendGrid API error: '.$response->status().' '.$response->body());
    }

    public function test(): bool
    {
        $response = $this->http()
            ->withToken((string) $this->api_key)
            ->get('https://api.sendgrid.com/v3/scopes');

        if (! $response->successful()) {
            throw new Exception('SendGrid auth failed: '.$response->status().' '.$response->body());
        }

        return true;
    }
}
