<?php

namespace Modules\Mailrelay\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class ApiBatchService
{
    private string $apiUrl;

    public function __construct(private readonly Client $client)
    {
        // TODO: migrate api_key to config('mailrelay.api_key') once MailrelayProvider
        //       removes its own env() call (tracked in MR-002).
        $this->apiUrl = config('mailrelay.api_url', 'https://app.mailrelay.com/api');
    }

    /**
     * @param  array<mixed>  $batchData
     * @return array<mixed>
     */
    public function executeBatch(array $batchData): array
    {
        try {
            $response = $this->client->post("{$this->apiUrl}/batches", [
                'json' => $batchData,
                'headers' => [
                    'Authorization' => 'Bearer '.config('mailrelay.api_key'),
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true) ?? [];
        } catch (RequestException $e) {
            Log::channel('mailrelay')->error('Mailrelay batch request failed', [
                'url' => "{$this->apiUrl}/batches",
                'error' => $e->getMessage(),
            ]);

            return ['error' => 'Batch request failed'];
        }
    }
}
