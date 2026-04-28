<?php

namespace Modules\CampaignSendingServers\Models;

use Exception;
use Modules\CampaignSendingServers\Library\Traits\ApiSenderTrait;
use Symfony\Component\Mime\Email;

/**
 * Mailgun HTTP API.
 *
 * @see https://documentation.mailgun.com/en/latest/api-sending.html
 *
 * Credenciales: api_key (basic auth como user `api`), domain (subdominio
 * verificado en Mailgun, ej: mg.example.com), aws_region opcional para EU
 * (`api.eu.mailgun.net` vs `api.mailgun.net`).
 */
class SendingServerMailgunApi extends SendingServer
{
    use ApiSenderTrait;

    protected $table = 'campaign_sending_servers';

    public function send($email, array $params = []): array
    {
        if (! $email instanceof Email) {
            throw new Exception('Mailgun API requires Symfony Email instance');
        }

        $payload = $this->emailToPayload($email);
        $base = $this->getApiBase();

        $form = array_filter([
            'from' => $payload['from_name']
                ? sprintf('%s <%s>', $payload['from_name'], $payload['from_email'])
                : $payload['from_email'],
            'to' => $payload['to_email'],
            'subject' => $payload['subject'],
            'text' => $payload['text'],
            'html' => $payload['html'],
            'h:Reply-To' => $payload['reply_to'] ?: null,
        ], fn ($v) => $v !== null && $v !== '');

        // Headers personalizados
        foreach ($payload['headers'] as $name => $value) {
            $form['h:'.$name] = $value;
        }

        $request = $this->http()->asMultipart()->withBasicAuth('api', (string) $this->api_key);

        // Attachments via multipart
        $multipart = [];
        foreach ($form as $name => $value) {
            $multipart[] = ['name' => $name, 'contents' => $value];
        }
        foreach ($payload['attachments'] as $att) {
            $multipart[] = [
                'name' => 'attachment',
                'contents' => base64_decode($att['content']),
                'filename' => $att['name'],
                'headers' => ['Content-Type' => $att['type']],
            ];
        }

        $response = $request->post("{$base}/{$this->domain}/messages", $multipart);

        if ($response->successful()) {
            return [
                'status' => self::DELIVERY_STATUS_SENT,
                'runtime_message_id' => $response->json('id'),
            ];
        }

        throw new Exception('Mailgun API error: '.$response->status().' '.$response->body());
    }

    public function test(): bool
    {
        $base = $this->getApiBase();
        $response = $this->http()
            ->withBasicAuth('api', (string) $this->api_key)
            ->get("{$base}/domains");

        if (! $response->successful()) {
            throw new Exception('Mailgun auth failed: '.$response->status().' '.$response->body());
        }

        return true;
    }

    protected function getApiBase(): string
    {
        // Region EU si aws_region (reusamos columna) contiene 'eu'.
        $region = strtolower((string) ($this->aws_region ?: ''));

        return str_contains($region, 'eu')
            ? 'https://api.eu.mailgun.net/v3'
            : 'https://api.mailgun.net/v3';
    }
}
