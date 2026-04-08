<?php

namespace Modules\Mailrelay\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class ImportService
{
    protected $client;

    protected $apiKey;

    protected $apiUrl;

    public function __construct()
    {
        $this->apiKey = config('mailrelay.api_key');
        $this->apiUrl = config('mailrelay.api_url');
        $this->client = new Client;
    }

    public function importSubscribers($listId, $file)
    {
        try {
            $response = $this->client->post("{$this->apiUrl}/imports", [
                'multipart' => [
                    [
                        'name' => 'file',
                        'contents' => fopen($file, 'r'),
                        'filename' => basename($file),
                    ],
                    [
                        'name' => 'list_id',
                        'contents' => $listId,
                    ],
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
