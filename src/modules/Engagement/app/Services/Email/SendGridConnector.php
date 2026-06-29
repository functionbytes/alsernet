<?php

declare(strict_types=1);

namespace Modules\Engagement\Services\Email;

use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Modules\Engagement\Models\EmailCampaign;

class SendGridConnector implements EmailProviderContract
{
    private Client $client;

    public function __construct(
        protected string $apiKey,
    ) {
        $this->client = new Client([
            'base_uri' => 'https://api.sendgrid.com/v3/',
            'headers' => [
                'Authorization' => 'Bearer '.$apiKey,
                'Content-Type' => 'application/json',
            ],
            'timeout' => 30,
            'connect_timeout' => 10,
        ]);
    }

    public function sendCampaign(EmailCampaign $campaign): array
    {
        if (! $this->healthCheck()) {
            throw new \RuntimeException('SendGrid no está configurado correctamente.');
        }

        try {
            $sgCampaign = $this->createSingleSend($campaign);
            $this->scheduleSingleSend($sgCampaign['id'], $campaign->scheduled_at);

            return [
                'provider_campaign_id' => $sgCampaign['id'],
                'status' => $campaign->scheduled_at ? 'scheduled' : 'sent',
            ];
        } catch (GuzzleException $e) {
            throw new \RuntimeException('Error de SendGrid: '.$e->getMessage(), 0, $e);
        }
    }

    public function getCampaignStats(string $providerCampaignId): array
    {
        try {
            $response = $this->client->get("marketing/stats/singlesends/{$providerCampaignId}");
            $data = json_decode((string) $response->getBody(), true);

            return [
                'sent' => $data['send_count'] ?? 0,
                'opened' => $data['unique_opens'] ?? 0,
                'clicked' => $data['unique_clicks'] ?? 0,
                'bounced' => ($data['bounce_count'] ?? 0) + ($data['dropped_count'] ?? 0),
                'unsubscribed' => $data['unsubscribe_count'] ?? 0,
            ];
        } catch (GuzzleException $e) {
            throw new \RuntimeException('Error obteniendo stats de SendGrid: '.$e->getMessage(), 0, $e);
        }
    }

    public function healthCheck(): bool
    {
        if (empty($this->apiKey)) {
            return false;
        }

        try {
            $response = $this->client->get('user/profile');

            return $response->getStatusCode() === 200;
        } catch (GuzzleException) {
            return false;
        }
    }

    /**
     * @return array{id: string}
     *
     * @throws GuzzleException
     */
    private function createSingleSend(EmailCampaign $campaign): array
    {
        $response = $this->client->post('marketing/singlesends', [
            'json' => [
                'name' => $campaign->name,
                'send_to' => [
                    'list_ids' => $campaign->provider_list_id ? [$campaign->provider_list_id] : [],
                ],
                'email_config' => [
                    'subject' => $campaign->subject,
                    'html_content' => $campaign->html_content ?? '',
                    'plain_content' => $campaign->text_content ?? '',
                    'sender_id' => null,
                    'custom_unsubscribe_url' => null,
                ],
            ],
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * @throws GuzzleException
     */
    private function scheduleSingleSend(string $singleSendId, ?\DateTimeInterface $scheduledAt): void
    {
        if ($scheduledAt) {
            $utc = Carbon::parse($scheduledAt)->utc();
            $this->client->put("marketing/singlesends/{$singleSendId}/schedule", [
                'json' => ['send_at' => $utc->format('Y-m-d\TH:i:s\Z')],
            ]);
        } else {
            $this->client->put("marketing/singlesends/{$singleSendId}/schedule", [
                'json' => ['send_at' => 'now'],
            ]);
        }
    }
}
