<?php

namespace Modules\Mailrelay\Services;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Modules\Mailrelay\Entities\MailrelayEndpoint;
use Modules\Mailrelay\Entities\MailrelayEndpointLog;
use Modules\Mailrelay\Exceptions\ProviderException;

class EndpointExecutionService
{
    public function __construct(
        protected TemplateRendererService $rendererService,
        protected ProviderManager $providerManager,
    ) {}

    /**
     * Execute an endpoint request.
     */
    public function execute(MailrelayEndpoint $endpoint, Request $request): Response
    {
        $startTime = microtime(true);
        $requestData = $request->all();
        $responseStatus = 200;
        $responseBody = '';
        $errorMessage = null;

        try {
            // Validate API key
            $apiKey = $request->header('X-API-Key') ?? $request->input('api_key');
            if (! $this->validateApiKey($apiKey, $endpoint)) {
                $responseStatus = 401;
                $errorMessage = 'Invalid API key';
                throw new \Exception($errorMessage);
            }

            // Check IP restrictions
            $clientIp = $request->ip();
            if (! $endpoint->isIpAllowed($clientIp)) {
                $responseStatus = 403;
                $errorMessage = 'IP address not allowed';
                throw new \Exception($errorMessage);
            }

            // Check rate limit
            if (! $this->checkRateLimit($endpoint, $clientIp)) {
                $responseStatus = 429;
                $errorMessage = 'Rate limit exceeded';
                throw new \Exception($errorMessage);
            }

            // Get the template
            if (! $endpoint->template) {
                $responseStatus = 500;
                $errorMessage = 'No template configured for this endpoint';
                throw new \Exception($errorMessage);
            }

            // Render the template
            $content = $this->rendererService->renderForSending($endpoint->template, $requestData);
            $subject = $this->rendererService->renderSubject($endpoint->template, $requestData);

            // Send the email (integrate with your mail service)
            $this->sendEmail($requestData['to'] ?? null, $subject, $content);

            // Success response
            $responseBody = json_encode([
                'success' => true,
                'message' => 'Email sent successfully',
                'execution_time' => round(microtime(true) - $startTime, 3),
            ]);

            // Update last used timestamp
            $endpoint->touchLastUsed();

            // Call webhook if configured
            if ($endpoint->webhook_url) {
                $this->callWebhook($endpoint->webhook_url, [
                    'endpoint_key' => $endpoint->endpoint_key,
                    'status' => 'success',
                    'timestamp' => now()->toIso8601String(),
                ]);
            }
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            $responseBody = json_encode([
                'success' => false,
                'error' => $errorMessage,
            ]);
        } finally {
            // Log the request
            $this->logRequest($endpoint, $request, $responseStatus, $responseBody, $errorMessage);
        }

        return response($responseBody, $responseStatus)
            ->header('Content-Type', 'application/json');
    }

    /**
     * Validate the API key.
     */
    public function validateApiKey(?string $apiKey, MailrelayEndpoint $endpoint): bool
    {
        if (! $apiKey) {
            return false;
        }

        return $endpoint->verifyApiKey($apiKey);
    }

    /**
     * Check if the request is within the rate limit.
     */
    public function checkRateLimit(MailrelayEndpoint $endpoint, string $ip): bool
    {
        $cacheKey = "mailrelay:endpoint:{$endpoint->id}:ratelimit:{$ip}";
        $requests = Cache::get($cacheKey, 0);

        if ($requests >= $endpoint->rate_limit) {
            return false;
        }

        // Increment request count (expires after 1 minute)
        Cache::put($cacheKey, $requests + 1, 60);

        return true;
    }

    /**
     * Log the request to the database.
     */
    public function logRequest(
        MailrelayEndpoint $endpoint,
        Request $request,
        int $responseStatus,
        string $responseBody,
        ?string $errorMessage
    ): void {
        MailrelayEndpointLog::create([
            'mailrelay_endpoint_id' => $endpoint->id,
            'request_ip' => $request->ip(),
            'request_method' => $request->method(),
            'request_payload' => $request->all(),
            'response_status' => $responseStatus,
            'response_body' => $responseBody,
            'error_message' => $errorMessage,
            'executed_at' => now(),
        ]);
    }

    /**
     * Send the email via the default mail provider.
     *
     * @throws ProviderException
     */
    protected function sendEmail(?string $to, string $subject, string $content): void
    {
        if (! $to) {
            throw ProviderException::recipientRequired();
        }

        if (! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            throw ProviderException::invalidRecipient($to);
        }

        $provider = $this->providerManager->default();
        $result = $provider->send($to, $subject, $content);

        if (! $result['success']) {
            throw ProviderException::sendingFailed($to, $result['error'] ?? 'Unknown error');
        }
    }

    /**
     * Call the webhook URL with the payload.
     */
    protected function callWebhook(string $url, array $payload): void
    {
        try {
            Http::timeout(5)->post($url, $payload);
        } catch (\Exception $e) {
            // Silently fail webhook calls
            logger()->warning('Webhook call failed', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get endpoint execution statistics.
     */
    public function getStatistics(MailrelayEndpoint $endpoint, int $days = 30): array
    {
        $startDate = now()->subDays($days);

        $logs = $endpoint->logs()
            ->where('executed_at', '>=', $startDate)
            ->get();

        $total = $logs->count();
        $successful = $logs->where('response_status', '>=', 200)->where('response_status', '<', 300)->count();
        $failed = $total - $successful;

        return [
            'total_requests' => $total,
            'successful_requests' => $successful,
            'failed_requests' => $failed,
            'success_rate' => $total > 0 ? round(($successful / $total) * 100, 2) : 0,
            'last_used_at' => $endpoint->last_used_at?->toIso8601String(),
        ];
    }
}
