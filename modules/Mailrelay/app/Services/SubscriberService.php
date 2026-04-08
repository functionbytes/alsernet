<?php

namespace Modules\Mailrelay\Services;

use App\Services\CircuitBreaker;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class SubscriberService
{
    protected $client;

    protected $apiKey;

    protected $apiUrl;

    private CircuitBreaker $circuitBreaker;

    public function __construct()
    {
        $this->apiKey = config('mailrelay.api_key');
        $this->apiUrl = config('mailrelay.api_url');
        $this->client = new Client;
        $this->circuitBreaker = new CircuitBreaker('mailrelay', 5, 60);
    }

    public function getSubscribersList()
    {
        if (! $this->circuitBreaker->isAvailable()) {
            return ['error' => 'Mailrelay circuit breaker is open'];
        }

        try {
            $response = $this->client->get("{$this->apiUrl}/subscribers", [
                'headers' => [
                    'Authorization' => "Bearer {$this->apiKey}",
                ],
            ]);

            $this->circuitBreaker->recordSuccess();

            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            $this->circuitBreaker->recordFailure();

            return ['error' => $e->getMessage()];
        }
    }

    public function addSubscriberToMailRelay($name, $email, $listId)
    {
        if (! $this->circuitBreaker->isAvailable()) {
            return ['error' => 'Mailrelay circuit breaker is open'];
        }

        $url = $this->apiUrl.'/subscribers';

        try {
            $response = $this->client->post($url, [
                'json' => [
                    'email' => $email,
                    'name' => $name,
                    'list_id' => $listId,  // Pasamos el list_id junto con el correo y nombre
                ],
                'headers' => [
                    'Authorization' => "Bearer {$this->apiKey}",
                ],
            ]);

            $this->circuitBreaker->recordSuccess();

            return json_decode($response->getBody()->getContents(), true); // Devuelve la respuesta de MailRelay
        } catch (\Exception $e) {
            $this->circuitBreaker->recordFailure();

            return ['error' => $e->getMessage()]; // Devuelve el error si la API falla
        }
    }
}
