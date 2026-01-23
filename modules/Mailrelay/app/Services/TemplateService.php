<?php

namespace Modules\Mailrelay\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class TemplateService
{
    protected $client;

    protected $apiKey;

    protected $apiUrl;

    public function __construct()
    {
        $this->apiKey = env('MAILRELAY_API_KEY');
        $this->apiUrl = env('MAILRELAY_URL', 'https://app.mailrelay.com/api');
        $this->client = new Client;
    }

    public function createTemplate($name, $htmlContent, $subject)
    {
        try {
            $response = $this->client->post("{$this->apiUrl}/templates", [
                'json' => [
                    'name' => $name,
                    'html_content' => $htmlContent,
                    'subject' => $subject,
                ],
                'headers' => [
                    'Authorization' => "Bearer {$this->apiKey}",
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public function getTemplate($templateId)
    {
        try {
            $response = $this->client->get("{$this->apiUrl}/templates/{$templateId}", [
                'headers' => [
                    'Authorization' => "Bearer {$this->apiKey}",
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
