<?php

namespace Modules\Mailrelay\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class ABTestService
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

    public function createABTest($name, $subjectA, $subjectB, $listId)
    {
        try {
            $response = $this->client->post("{$this->apiUrl}/ab_tests", [
                'json' => [
                    'name' => $name,
                    'subject_a' => $subjectA,
                    'subject_b' => $subjectB,
                    'list_id' => $listId,
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
}
