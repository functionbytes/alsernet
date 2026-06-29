<?php

namespace Modules\CampaignSendingServers\Models;

use Exception;
use Modules\CampaignSendingServers\Library\Traits\ApiSenderTrait;
use Symfony\Component\Mime\Email;

/**
 * Brevo (antes Sendinblue) HTTP API.
 *
 * @see https://developers.brevo.com/reference/sendtransacemail
 *
 * Credenciales: api_key (header `api-key`).
 */
class SendingServerBrevoApi extends SendingServer
{
    use ApiSenderTrait;

    protected $table = 'campaign_sending_servers';

    public function send($email, array $params = []): array
    {
        if (! $email instanceof Email) {
            throw new Exception('Brevo API requires Symfony Email instance');
        }

        $payload = $this->emailToPayload($email);

        $body = array_filter([
            'sender' => array_filter([
                'email' => $payload['from_email'],
                'name' => $payload['from_name'],
            ]),
            'to' => [array_filter([
                'email' => $payload['to_email'],
                'name' => $payload['to_name'],
            ])],
            'replyTo' => $payload['reply_to'] ? ['email' => $payload['reply_to']] : null,
            'subject' => $payload['subject'],
            'htmlContent' => $payload['html'] ?: null,
            'textContent' => $payload['text'] ?: null,
            'headers' => $payload['headers'] ?: null,
        ]);

        if (! empty($payload['attachments'])) {
            $body['attachment'] = array_map(fn ($a) => [
                'name' => $a['name'],
                'content' => $a['content'],
            ], $payload['attachments']);
        }

        $response = $this->http()
            ->withHeaders(['api-key' => (string) $this->api_key])
            ->post('https://api.brevo.com/v3/smtp/email', $body);

        if ($response->successful()) {
            return [
                'status' => self::DELIVERY_STATUS_SENT,
                'runtime_message_id' => $response->json('messageId'),
            ];
        }

        throw new Exception('Brevo API error: '.$response->status().' '.$response->body());
    }

    public function test(): bool
    {
        $response = $this->http()
            ->withHeaders(['api-key' => (string) $this->api_key])
            ->get('https://api.brevo.com/v3/account');

        if (! $response->successful()) {
            throw new Exception('Brevo auth failed: '.$response->status().' '.$response->body());
        }

        return true;
    }
}
