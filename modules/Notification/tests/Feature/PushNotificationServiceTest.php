<?php

namespace Modules\Notification\Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Modules\Notification\Enums\PushResult;
use Modules\Notification\Services\PushNotificationService;
use Tests\TestCase;

class PushNotificationServiceTest extends TestCase
{
    private PushNotificationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.fcm.project_id', 'test-project');

        // Pre-cache the OAuth2 token so the service never reads the credentials file.
        Cache::put('fcm_oauth2_token', 'fake-access-token', now()->addMinutes(10));

        // Ensure the FCM circuit breaker starts closed.
        Cache::forget('circuit_breaker:fcm:failures');
        Cache::forget('circuit_breaker:fcm:opened_at');

        $this->service = new PushNotificationService;
    }

    public function test_send_to_token_returns_success_on_2xx(): void
    {
        Http::fake([
            'fcm.googleapis.com/*' => Http::response(['name' => 'projects/test/messages/1'], 200),
        ]);

        $result = $this->service->sendToToken('device-token', ['title' => 'Hola', 'body' => 'Mundo']);

        $this->assertSame(PushResult::Success, $result);
    }

    public function test_send_to_token_returns_invalid_token_on_404(): void
    {
        Http::fake([
            'fcm.googleapis.com/*' => Http::response([
                'error' => [
                    'code' => 404,
                    'status' => 'NOT_FOUND',
                    'details' => [['errorCode' => 'UNREGISTERED']],
                ],
            ], 404),
        ]);

        $result = $this->service->sendToToken('dead-token', ['title' => 'x']);

        $this->assertSame(PushResult::InvalidToken, $result);
    }

    public function test_send_to_token_returns_invalid_token_on_unregistered_error_code(): void
    {
        Http::fake([
            'fcm.googleapis.com/*' => Http::response([
                'error' => ['details' => [['errorCode' => 'UNREGISTERED']]],
            ], 400),
        ]);

        $result = $this->service->sendToToken('dead-token', ['title' => 'x']);

        $this->assertSame(PushResult::InvalidToken, $result);
    }

    public function test_send_to_token_returns_failed_on_server_error(): void
    {
        Http::fake([
            'fcm.googleapis.com/*' => Http::response(['error' => ['status' => 'INTERNAL']], 500),
        ]);

        $result = $this->service->sendToToken('token', ['title' => 'x']);

        $this->assertSame(PushResult::Failed, $result);
    }

    public function test_send_to_token_skipped_when_circuit_breaker_open(): void
    {
        Http::fake([
            'fcm.googleapis.com/*' => Http::response(['error' => ['status' => 'INTERNAL']], 500),
        ]);

        // Five failures open the breaker (default threshold = 5).
        for ($i = 0; $i < 5; $i++) {
            $this->service->sendToToken('token', ['title' => 'x']);
        }

        Http::fake([
            'fcm.googleapis.com/*' => Http::response(['name' => 'ok'], 200),
        ]);

        // With the breaker open the request is rejected before hitting FCM.
        $result = $this->service->sendToToken('token', ['title' => 'x']);

        $this->assertSame(PushResult::Failed, $result);
    }
}
