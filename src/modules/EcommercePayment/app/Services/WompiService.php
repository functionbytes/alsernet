<?php

namespace Modules\EcommercePayment\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use Illuminate\Support\Facades\Log;
use Modules\Core\Models\Setting;

class WompiService
{
    protected string $eventSecret;

    protected string $publicKey;

    protected string $privateKey;

    protected string $integritySecret;

    protected bool $isSandbox;

    protected array $data = [];

    protected string $baseUrl;

    public function __construct()
    {
        $this->publicKey = $this->getConfigValue('public_key', 'WOMPI_PUBLIC_KEY');
        $this->privateKey = $this->getConfigValue('private_key', 'WOMPI_PRIVATE_KEY');
        $this->integritySecret = $this->getConfigValue('integrity_secret', 'WOMPI_INTEGRITY_SECRET');
        $this->eventSecret = $this->getConfigValue('event_secret', 'WOMPI_EVENT_SECRET');
        $this->isSandbox = $this->getConfigValue('mode', 'WOMPI_MODE') === 'sandbox';

        Log::info('Wompi Service Initialization', [
            'public_key_length' => strlen($this->publicKey),
            'public_key_preview' => substr($this->publicKey, 0, 20).'...',
            'private_key_set' => ! empty($this->privateKey),
            'integrity_secret_set' => ! empty($this->integritySecret),
            'event_secret_set' => ! empty($this->eventSecret),
            'is_sandbox' => $this->isSandbox,
        ]);

        if (empty($this->publicKey)) {
            throw new \Exception('Wompi public key is not configured.');
        }

        if (empty($this->integritySecret)) {
            throw new \Exception('Wompi integrity secret is not configured.');
        }

        if (empty($this->eventSecret)) {
            Log::warning('Wompi Event Secret not configured. Webhook validation will not work.');
        }

        if (! $this->validatePublicKeyFormat($this->publicKey)) {
            throw new \Exception('Wompi public key format is invalid. Should start with pub_test_ or pub_prod_');
        }

        $this->baseUrl = $this->isSandbox
            ? 'https://sandbox.wompi.co/v1'
            : 'https://production.wompi.co/v1';
    }

    private function getConfigValue(string $settingKey, string $envKey): string
    {
        $adminValue = Setting::get("ecommerce_payment.wompi.{$settingKey}");

        if (! empty($adminValue)) {
            return $adminValue;
        }

        return env($envKey, '');
    }

    public function withData(array $data): self
    {
        $this->data = $data;

        return $this;
    }

    public function generateIntegritySignature(int $amountInCents, string $currency = 'COP'): string
    {
        $reference = $this->data['reference'];
        $concatenatedString = $reference.$amountInCents.$currency.$this->integritySecret;
        $signature = hash('sha256', $concatenatedString);

        Log::info('Wompi Signature Generation', [
            'reference' => $reference,
            'amount_in_cents' => $amountInCents,
            'currency' => $currency,
            'signature' => $signature,
        ]);

        return $signature;
    }

    public function verifyWebhookSignature(array $payload, string $signature): bool
    {
        if (empty($this->eventSecret)) {
            Log::warning('Cannot verify webhook signature: Event Secret not configured');

            return false;
        }

        if (! isset($payload['data']['transaction'])) {
            return false;
        }

        $transaction = $payload['data']['transaction'];

        $concatenatedString = implode('', [
            $transaction['id'] ?? '',
            $transaction['status'] ?? '',
            $transaction['amount_in_cents'] ?? '',
            $transaction['currency'] ?? '',
            $payload['signature']['checksum'] ?? '',
        ]);

        $expectedSignature = hash_hmac('sha256', $concatenatedString, $this->eventSecret);

        return hash_equals($expectedSignature, $signature);
    }

    public function getWidgetPageContent(): string
    {
        try {
            $widgetData = $this->prepareWidgetData();

            Log::info('Wompi Widget Data', [
                'widget_data' => $widgetData,
                'public_key_valid' => ! empty($widgetData['public_key']),
                'signature_generated' => ! empty($widgetData['signature_integrity']),
            ]);

            return view('ecommerce-payment::widget', [
                'widgetData' => $widgetData,
                'originalAmount' => $this->data['amount'],
                'originalCurrency' => $this->data['currency'] ?? 'USD',
            ])->render();
        } catch (\Exception $e) {
            Log::error('Error in Wompi Widget', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $this->data,
            ]);

            return '<div class="alert alert-danger">Error al cargar el widget de Wompi: '.e($e->getMessage()).'</div>';
        }
    }

    public function getTransaction(string $transactionId): array
    {
        $result = $this->makeApiRequest('GET', "/transactions/{$transactionId}");
        if (isset($result['_error']) && $result['_error']) {
            throw new \RuntimeException('Wompi transaction query failed: '.($result['message'] ?? 'Unknown error'));
        }

        return $result;
    }

    public function refundTransaction(string $transactionId, int $amountInCents, string $reason = ''): array
    {
        $result = $this->makeApiRequest('POST', "/transactions/{$transactionId}/refunds", [
            'amount_in_cents' => $amountInCents,
            'reason' => $reason,
        ]);
        if (isset($result['_error']) && $result['_error']) {
            throw new \RuntimeException('Wompi refund failed: '.($result['message'] ?? 'Unknown error'));
        }

        return $result;
    }

    public function makeApiRequest(string $method, string $endpoint, array $data = []): array
    {
        try {
            $client = new Client([
                'base_uri' => $this->baseUrl,
                'timeout' => 30,
                'headers' => [
                    'Authorization' => 'Bearer '.$this->privateKey,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
            ]);

            $options = [];
            if ($method === 'POST' && ! empty($data)) {
                $options['json'] = $data;
            }

            $response = $client->request($method, $endpoint, $options);

            return json_decode($response->getBody()->getContents(), true) ?? [];
        } catch (ClientException $e) {
            $response = $e->getResponse();
            $body = json_decode($response?->getBody()?->getContents() ?? '{}', true) ?? [];
            Log::error('Wompi API client error', [
                'endpoint' => $endpoint,
                'status' => $response?->getStatusCode(),
                'body' => $body,
            ]);

            return array_merge($body, ['_error' => true, '_status' => $response?->getStatusCode()]);
        } catch (ServerException $e) {
            $response = $e->getResponse();
            Log::error('Wompi API server error', [
                'endpoint' => $endpoint,
                'status' => $response?->getStatusCode(),
            ]);

            return ['_error' => true, '_status' => $response?->getStatusCode(), 'message' => 'Wompi server error'];
        } catch (\Exception $e) {
            Log::error('Wompi API request failed', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

            return ['_error' => true, 'message' => $e->getMessage()];
        }
    }

    private function prepareWidgetData(): array
    {
        $originalAmount = $this->data['amount'];
        $originalCurrency = $this->data['currency'] ?? 'USD';

        $currency = 'COP';
        if ($originalCurrency !== 'COP') {
            $exchangeRate = match ($originalCurrency) {
                'USD' => 4000,
                'EUR' => 4300,
                default => 4000
            };
            $convertedAmount = $originalAmount * $exchangeRate;
        } else {
            $convertedAmount = $originalAmount;
        }

        $amountInCents = (int) ($convertedAmount * 100);

        if ($this->isSandbox && $amountInCents > 50000000) {
            $amountInCents = 50000000;
            Log::warning('Wompi: Amount reduced for sandbox limits', [
                'reduced_amount_cents' => $amountInCents,
            ]);
        }

        $customerData = $this->prepareCustomerData();

        if (empty($this->data['reference'])) {
            throw new \Exception('Reference is empty');
        }

        $expirationTime = $this->generateExpirationTime();
        $signature = $this->generateIntegritySignature($amountInCents, $currency);

        if (empty($signature)) {
            throw new \Exception('Failed to generate integrity signature');
        }

        $widgetData = [
            'public_key' => $this->publicKey,
            'currency' => $currency,
            'amount_in_cents' => $amountInCents,
            'reference' => $this->data['reference'],
            'signature_integrity' => $signature,
            'expiration_time' => $expirationTime,
            'redirect_url' => $this->data['redirect_url'],
            'customer_data' => $customerData,
            'customer_email' => $this->data['customer_email'],
            'customer_name' => $this->data['customer_name'] ?? '',
            'customer_phone' => $this->data['customer_phone'] ?? '',
            'is_sandbox' => $this->isSandbox,
            'widget_url' => $this->getWidgetUrl(),
        ];

        $shippingData = $this->prepareShippingAddress();
        if ($shippingData) {
            $widgetData['shipping_address'] = $shippingData;
        }

        return $widgetData;
    }

    public function getWidgetUrl(): string
    {
        return 'https://checkout.wompi.co/widget.js';
    }

    private function prepareCustomerData(): array
    {
        $customerData = [
            'email' => $this->data['customer_email'],
            'full_name' => $this->data['customer_name'] ?? '',
        ];

        $phoneData = $this->parsePhoneNumber($this->data['customer_phone'] ?? '');
        if ($phoneData) {
            $customerData['phone_number'] = $phoneData['number'];
            $customerData['phone_number_prefix'] = $phoneData['prefix'];
        } else {
            $customerData['phone_number'] = '3001234567';
            $customerData['phone_number_prefix'] = '+57';
        }

        return $customerData;
    }

    private function generateExpirationTime(int $hoursFromNow = 24): string
    {
        return now()->addHours($hoursFromNow)->format('Y-m-d\TH:i:s.000\Z');
    }

    private function parsePhoneNumber(string $phone): ?array
    {
        if (empty($phone)) {
            return null;
        }

        $cleanPhone = preg_replace('/[^\d+]/', '', $phone);

        if (empty($cleanPhone)) {
            return null;
        }

        $patterns = [
            '/^\+57(\d{10})$/' => ['+57', '$1'],
            '/^57(\d{10})$/' => ['+57', '$1'],
            '/^(\d{10})$/' => ['+57', '$1'],
            '/^\+1(\d{10})$/' => ['+1', '$1'],
            '/^\+(\d{1,4})(\d{6,10})$/' => ['+$1', '$2'],
        ];

        foreach ($patterns as $pattern => $replacement) {
            if (preg_match($pattern, $cleanPhone)) {
                $prefix = str_replace(['$1', '$2'], [
                    preg_replace($pattern, '$1', $cleanPhone),
                    preg_replace($pattern, '$2', $cleanPhone),
                ], $replacement[0]);

                $number = str_replace(['$1', '$2'], [
                    preg_replace($pattern, '$1', $cleanPhone),
                    preg_replace($pattern, '$2', $cleanPhone),
                ], $replacement[1]);

                return [
                    'prefix' => $prefix,
                    'number' => $number,
                ];
            }
        }

        if (strlen($cleanPhone) === 10) {
            return [
                'prefix' => '+57',
                'number' => $cleanPhone,
            ];
        }

        return null;
    }

    private function prepareShippingAddress(): ?array
    {
        $addressLine1 = trim($this->data['shipping_address'] ?? '');
        $city = trim($this->data['shipping_city'] ?? '');
        $region = trim($this->data['shipping_region'] ?? '');
        $phone = trim($this->data['shipping_phone'] ?? '');

        if (empty($addressLine1) || empty($city) || empty($region)) {
            return null;
        }

        $phoneData = $this->parsePhoneNumber($phone);

        $shippingAddress = [
            'address_line_1' => $addressLine1,
            'city' => $city,
            'region' => $region,
            'country' => 'CO',
        ];

        if ($phoneData) {
            $shippingAddress['phone_number'] = $phoneData['number'];
            $shippingAddress['phone_number_prefix'] = $phoneData['prefix'];
        } else {
            $shippingAddress['phone_number'] = '3001234567';
            $shippingAddress['phone_number_prefix'] = '+57';
        }

        return $shippingAddress;
    }

    private function validatePublicKeyFormat(string $publicKey): bool
    {
        $validPrefixes = ['pub_test_', 'pub_sandbox_', 'pub_prod_', 'pub_live_'];

        foreach ($validPrefixes as $prefix) {
            if (str_starts_with($publicKey, $prefix)) {
                return true;
            }
        }

        return false;
    }

    public function validateConfiguration(): array
    {
        $errors = [];
        $warnings = [];

        if (empty($this->publicKey)) {
            $errors[] = 'Public Key no configurada';
        } elseif (! $this->validatePublicKeyFormat($this->publicKey)) {
            $errors[] = 'Public Key tiene formato inválido';
        }

        if (empty($this->privateKey)) {
            $errors[] = 'Private Key no configurada';
        }

        if (empty($this->integritySecret)) {
            $errors[] = 'Integrity Secret no configurado';
        }

        if (empty($this->eventSecret)) {
            $errors[] = 'Event Secret no configurado';
        }

        $publicKeyMode = str_contains($this->publicKey, 'test') ? 'sandbox' : 'production';
        $configMode = $this->isSandbox ? 'sandbox' : 'production';

        if ($publicKeyMode !== $configMode) {
            $warnings[] = "Public Key es de {$publicKeyMode} pero el modo está configurado como {$configMode}";
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }
}
