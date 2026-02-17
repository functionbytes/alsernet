<?php

namespace Modules\Mailrelay\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class SubscriberService
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

    public function getSubscribersList()
    {
        try {
            $response = $this->client->get("{$this->apiUrl}/subscribers", [
                'headers' => [
                    'Authorization' => "Bearer {$this->apiKey}",
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public function addSubscriberToMailRelay($name, $email, $listId)
    {
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

            return json_decode($response->getBody()->getContents(), true); // Devuelve la respuesta de MailRelay
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()]; // Devuelve el error si la API falla
        }
    }
}
