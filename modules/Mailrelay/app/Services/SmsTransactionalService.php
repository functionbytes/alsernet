<?php

namespace Modules\Mailrelay\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class SmsTransactionalService
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

    public function sendTransactionalSMS($phoneNumber, $message)
    {
        try {
            $response = $this->client->post("{$this->apiUrl}/sms", [
                'json' => [
                    'phone_number' => $phoneNumber,
                    'message' => $message,
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
