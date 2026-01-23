<?php

namespace Modules\Mailrelay\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class CampaignService
{
    protected $client;

    protected $apiKey;

    protected $apiUrl;

    public function __construct()
    {
        $this->apiKey = env('IPZMARKETING_API_KEY');
        $this->apiUrl = env('IPZMARKETING_URL', 'https://inoqualab.ipzmarketing.com/api/v1');
        $this->client = new Client;
    }

    protected function sendRequest($method, $endpoint, $data = null)
    {
        try {
            $options = [
                'headers' => [
                    'content-type' => 'application/json',
                    'x-auth-token' => $this->apiKey,
                ],
            ];

            if ($data !== null) {
                $options['json'] = $data;
            }

            $response = $this->client->request($method, "{$this->apiUrl}{$endpoint}", $options);

            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public function createCampaign($data)
    {
        return $this->sendRequest('POST', '/campaigns', $data);
    }

    public function getCampaign($campaignId)
    {
        return $this->sendRequest('GET', "/campaigns/{$campaignId}");
    }

    public function deleteCampaign($campaignId)
    {
        return $this->sendRequest('DELETE', "/campaigns/{$campaignId}");
    }

    public function updateCampaign($campaignId, $data)
    {
        return $this->sendRequest('PATCH', "/campaigns/{$campaignId}", $data);
    }

    public function sendAllCampaign($campaignId, $data)
    {
        return $this->sendRequest('POST', "/campaigns/{$campaignId}/send_all", $data);
    }

    public function sendTestCampaign($campaignId, $data)
    {
        return $this->sendRequest('POST', "/campaigns/{$campaignId}/send_test", $data);
    }
}
