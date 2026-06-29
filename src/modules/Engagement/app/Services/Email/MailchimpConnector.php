<?php

declare(strict_types=1);

namespace Modules\Engagement\Services\Email;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Modules\Engagement\Models\EmailCampaign;

class MailchimpConnector implements EmailProviderContract
{
    private Client $client;

    public function __construct(
        protected string $apiKey,
        protected string $serverPrefix,
    ) {
        $this->client = new Client([
            'base_uri' => "https://{$serverPrefix}.api.mailchimp.com/3.0/",
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
            throw new \RuntimeException('Mailchimp no está configurado correctamente.');
        }

        try {
            $listId = $campaign->provider_list_id;
            if (! $listId) {
                throw new \RuntimeException('No se ha configurado provider_list_id para la campaña.');
            }

            $mcCampaign = $this->createCampaign($campaign, $listId);
            $this->setContent($mcCampaign['id'], $campaign);
            $this->send($mcCampaign['id']);

            return [
                'provider_campaign_id' => $mcCampaign['id'],
                'status' => 'sent',
            ];
        } catch (GuzzleException $e) {
            throw new \RuntimeException('Error de Mailchimp: '.$e->getMessage(), 0, $e);
        }
    }

    public function getCampaignStats(string $providerCampaignId): array
    {
        try {
            $response = $this->client->get("reports/{$providerCampaignId}");
            $data = json_decode((string) $response->getBody(), true);

            return [
                'sent' => $data['emails_sent'] ?? 0,
                'opened' => $data['opens']['unique_opens'] ?? 0,
                'clicked' => $data['clicks']['unique_subscriber_clicks'] ?? 0,
                'bounced' => ($data['bounces']['hard_bounces'] ?? 0) + ($data['bounces']['soft_bounces'] ?? 0),
                'unsubscribed' => $data['unsubscribed'] ?? 0,
            ];
        } catch (GuzzleException $e) {
            throw new \RuntimeException('Error obteniendo stats de Mailchimp: '.$e->getMessage(), 0, $e);
        }
    }

    public function healthCheck(): bool
    {
        if (empty($this->apiKey) || empty($this->serverPrefix)) {
            return false;
        }

        try {
            $response = $this->client->get('ping');

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
    private function createCampaign(EmailCampaign $campaign, string $listId): array
    {
        $response = $this->client->post('campaigns', [
            'json' => [
                'type' => 'regular',
                'recipients' => ['list_id' => $listId],
                'settings' => [
                    'subject_line' => $campaign->subject,
                    'from_name' => $campaign->from_name ?: config('app.name'),
                    'reply_to' => $campaign->from_email ?: config('mail.from.address'),
                    'title' => $campaign->name,
                ],
            ],
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * @throws GuzzleException
     */
    private function setContent(string $campaignId, EmailCampaign $campaign): void
    {
        $this->client->put("campaigns/{$campaignId}/content", [
            'json' => [
                'html' => $campaign->html_content ?? '',
                'plain_text' => $campaign->text_content ?? '',
            ],
        ]);
    }

    /**
     * @throws GuzzleException
     */
    private function send(string $campaignId): void
    {
        $this->client->post("campaigns/{$campaignId}/actions/send");
    }
}
