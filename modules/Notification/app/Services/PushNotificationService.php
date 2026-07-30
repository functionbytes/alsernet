<?php

namespace Modules\Notification\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Core\Services\CircuitBreaker;
use Modules\Notification\Enums\PushResult;

/**
 * Firebase Cloud Messaging v1 (HTTP API v1)
 *
 * Autenticación: OAuth2 via service account (JWT Bearer).
 * Credenciales: storage/app/firebase-credentials.json (descargado desde Firebase Console).
 *
 * Variables de entorno requeridas:
 *   FCM_PROJECT_ID          → ID del proyecto Firebase
 *   FCM_CREDENTIALS_PATH    → Ruta al JSON de la cuenta de servicio (opcional, default: storage/app/firebase-credentials.json)
 */
class PushNotificationService
{
    private const MESSAGING_SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';

    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    private const IID_BATCH_ADD = 'https://iid.googleapis.com/iid/v1:batchAdd';

    private const IID_BATCH_REMOVE = 'https://iid.googleapis.com/iid/v1:batchRemove';

    private CircuitBreaker $circuitBreaker;

    private string $projectId;

    private string $credentialsPath;

    public function __construct()
    {
        $this->projectId = config('services.fcm.project_id', '');
        $this->credentialsPath = config('services.fcm.credentials_path', storage_path('app/firebase-credentials.json'));
        $this->circuitBreaker = new CircuitBreaker(
            'fcm',
            config('notification.circuit_breaker.fcm.failure_threshold', 5),
            config('notification.circuit_breaker.fcm.recovery_seconds', 60)
        );
    }

    /**
     * Enviar notificación push a un token específico.
     */
    public function sendToToken(string $token, array $data, string $deviceType = 'android'): PushResult
    {
        if (! $this->circuitBreaker->isAvailable()) {
            Log::warning('FCM circuit breaker is open — sendToToken skipped');

            return PushResult::Failed;
        }

        try {
            $message = $this->buildMessage($data, $deviceType);
            $message['token'] = $token;

            $response = $this->post(['message' => $message]);

            if ($response->successful()) {
                $this->circuitBreaker->recordSuccess();

                return PushResult::Success;
            }

            if ($this->isInvalidTokenResponse($response)) {
                // FCM respondió correctamente; el token está muerto, no es indisponibilidad del servicio.
                $this->circuitBreaker->recordSuccess();

                return PushResult::InvalidToken;
            }

            $this->circuitBreaker->recordFailure();
            Log::error('FCM sendToToken failed', [
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            return PushResult::Failed;
        } catch (\Exception $e) {
            $this->circuitBreaker->recordFailure();
            Log::error('FCM sendToToken exception: '.$e->getMessage());

            return PushResult::Failed;
        }
    }

    /**
     * Enviar notificación a múltiples tokens (un request por token).
     *
     * @return array<string, PushResult>
     */
    public function sendToTokens(array $tokens, array $data): array
    {
        $results = [];

        foreach ($tokens as $token) {
            $results[$token] = $this->sendToToken($token, $data);
        }

        return $results;
    }

    /**
     * Determinar si la respuesta de FCM indica que el token ya no es válido
     * (desinstalado / no registrado) y debe desactivarse.
     */
    private function isInvalidTokenResponse(Response $response): bool
    {
        return $response->status() === 404
            || $response->json('error.details.0.errorCode') === 'UNREGISTERED';
    }

    /**
     * Enviar notificación a un topic FCM.
     */
    public function sendToTopic(string $topic, array $data): bool
    {
        if (! $this->circuitBreaker->isAvailable()) {
            Log::warning('FCM circuit breaker is open — sendToTopic skipped', ['topic' => $topic]);

            return false;
        }

        try {
            $message = $this->buildMessage($data);
            $message['topic'] = $topic;

            $response = $this->post(['message' => $message]);

            if ($response->successful()) {
                $this->circuitBreaker->recordSuccess();

                return true;
            }

            $this->circuitBreaker->recordFailure();
            Log::error('FCM sendToTopic failed', ['status' => $response->status(), 'body' => $response->json()]);

            return false;
        } catch (\Exception $e) {
            $this->circuitBreaker->recordFailure();
            Log::error('FCM sendToTopic exception: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Suscribir un token a un topic.
     */
    public function subscribeToTopic(string $token, string $topic): bool
    {
        return $this->manageTopic(self::IID_BATCH_ADD, [$token], $topic);
    }

    /**
     * Desuscribir un token de un topic.
     */
    public function unsubscribeFromTopic(string $token, string $topic): bool
    {
        return $this->manageTopic(self::IID_BATCH_REMOVE, [$token], $topic);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Construir el cuerpo del mensaje FCM v1 (sin el campo de destino).
     */
    private function buildMessage(array $data, string $deviceType = 'android'): array
    {
        $message = [
            'notification' => [
                'title' => $data['title'] ?? 'Notificación',
                'body' => $data['body'] ?? '',
            ],
            'data' => array_map('strval', $data['data'] ?? []),
        ];

        if ($deviceType === 'android') {
            $message['android'] = [
                'notification' => [
                    'icon' => $data['icon'] ?? 'default',
                    'sound' => 'default',
                ],
            ];
        }

        if ($deviceType === 'ios') {
            $message['apns'] = [
                'headers' => ['apns-priority' => '10'],
                'payload' => [
                    'aps' => [
                        'badge' => (int) ($data['badge'] ?? 1),
                        'sound' => 'default',
                    ],
                ],
            ];
        }

        return $message;
    }

    /**
     * Gestionar suscripciones a topics via IID batch API.
     *
     * @param  array<string>  $tokens
     */
    private function manageTopic(string $endpoint, array $tokens, string $topic): bool
    {
        try {
            $response = Http::withToken($this->getAccessToken())
                ->post($endpoint, [
                    'registration_tokens' => $tokens,
                    'to' => '/topics/'.$topic,
                ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('FCM topic management exception: '.$e->getMessage(), [
                'endpoint' => $endpoint,
                'topic' => $topic,
            ]);

            return false;
        }
    }

    /**
     * Enviar un request a la FCM v1 Messaging API.
     */
    private function post(array $payload): Response
    {
        $url = sprintf(
            'https://fcm.googleapis.com/v1/projects/%s/messages:send',
            $this->projectId
        );

        return Http::withToken($this->getAccessToken())
            ->post($url, $payload);
    }

    /**
     * Obtener (o generar y cachear) un OAuth2 access token para la service account.
     * Los tokens de Google son válidos 60 minutos; los cacheamos 55 para tener margen.
     */
    private function getAccessToken(): string
    {
        return Cache::remember('fcm_oauth2_token', 55 * 60, function () {
            $credentials = $this->loadCredentials();
            $jwt = $this->buildJwt($credentials);

            $response = Http::asForm()->post(self::TOKEN_URL, [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

            if (! $response->successful()) {
                throw new \RuntimeException('FCM OAuth2 token request failed: '.$response->body());
            }

            return $response->json('access_token');
        });
    }

    /**
     * Leer el JSON de credenciales de la service account.
     *
     * @return array<string, string>
     */
    private function loadCredentials(): array
    {
        if (! file_exists($this->credentialsPath)) {
            throw new \RuntimeException('Firebase credentials file not found: '.$this->credentialsPath);
        }

        $credentials = json_decode(file_get_contents($this->credentialsPath), true);

        if (! isset($credentials['client_email'], $credentials['private_key'])) {
            throw new \RuntimeException('Firebase credentials file is missing required fields.');
        }

        return $credentials;
    }

    /**
     * Construir y firmar el JWT para el intercambio OAuth2 (RS256).
     *
     * @param  array<string, string>  $credentials
     */
    private function buildJwt(array $credentials): string
    {
        $now = time();

        $header = $this->base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));

        $claims = $this->base64UrlEncode(json_encode([
            'iss' => $credentials['client_email'],
            'scope' => self::MESSAGING_SCOPE,
            'aud' => self::TOKEN_URL,
            'iat' => $now,
            'exp' => $now + 3600,
        ]));

        $unsigned = $header.'.'.$claims;

        openssl_sign($unsigned, $signature, $credentials['private_key'], 'sha256WithRSAEncryption');

        return $unsigned.'.'.$this->base64UrlEncode($signature);
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
