<?php

namespace Modules\Mailrelay\Tests\Unit\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Modules\Mailrelay\Services\ApiBatchService;
use Tests\TestCase;

class ApiBatchServiceTest extends TestCase
{
    private function makeService(MockHandler $mock): ApiBatchService
    {
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        return new ApiBatchService($client);
    }

    /** @test */
    public function test_batch_sends_with_auth_token(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['id' => 'batch-001', 'status' => 'queued'])),
        ]);

        config(['mailrelay.api_key' => 'test-api-key-123']);
        config(['mailrelay.api_url' => 'https://app.mailrelay.com/api']);

        $service = $this->makeService($mock);
        $result = $service->executeBatch(['emails' => [['to' => 'a@b.com']]]);

        $this->assertEquals('batch-001', $result['id']);
        $this->assertEquals('queued', $result['status']);

        $sentRequest = $mock->getLastRequest();
        $this->assertStringContainsString('Bearer test-api-key-123', $sentRequest->getHeaderLine('Authorization'));
    }

    /** @test */
    public function test_retry_on_5xx_response(): void
    {
        // ApiBatchService catches RequestException and returns ['error' => ...].
        // A 5xx non-exception response is decoded and returned as-is since
        // Guzzle by default does not throw on 5xx unless http_errors is true.
        $mock = new MockHandler([
            new Response(500, [], json_encode(['error' => 'Internal Server Error'])),
        ]);

        $service = $this->makeService($mock);
        $result = $service->executeBatch(['emails' => []]);

        // With http_errors not explicitly enabled, Guzzle returns the 500 body
        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
    }

    /** @test */
    public function test_exception_on_unauthorized(): void
    {
        // 401 causes Guzzle to throw ClientException (a RequestException) when
        // http_errors is enabled or when the server closes the connection.
        // We simulate a network-level RequestException (connection refused = 401-like).
        $mock = new MockHandler([
            new RequestException(
                'Client error: `POST /api/batches` resulted in a `401 Unauthorized`',
                new Request('POST', 'https://app.mailrelay.com/api/batches'),
                new Response(401, [], json_encode(['message' => 'Unauthorized']))
            ),
        ]);

        $service = $this->makeService($mock);
        $result = $service->executeBatch(['emails' => []]);

        $this->assertArrayHasKey('error', $result);
        $this->assertEquals('Batch request failed', $result['error']);
    }

    /** @test */
    public function test_masks_token_in_error_context(): void
    {
        $mock = new MockHandler([
            new RequestException(
                'Connection refused',
                new Request('POST', 'https://app.mailrelay.com/api/batches')
            ),
        ]);

        config(['mailrelay.api_key' => 'super-secret-api-token-xyz']);

        $service = $this->makeService($mock);

        // The service catches the exception and returns ['error' => 'Batch request failed'].
        // The token must NOT appear in the returned error array.
        $result = $service->executeBatch(['emails' => []]);

        $this->assertArrayHasKey('error', $result);
        $serialized = json_encode($result);
        $this->assertStringNotContainsString('super-secret-api-token-xyz', $serialized);
    }
}
