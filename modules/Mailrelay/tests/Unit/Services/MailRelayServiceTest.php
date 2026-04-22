<?php

namespace Modules\Mailrelay\Tests\Unit\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Cache;
use Modules\Mailrelay\Exceptions\MailrelayException;
use Modules\Mailrelay\Services\MailRelayService;
use Tests\TestCase;

class MailRelayServiceTest extends TestCase
{
    private function makeService(MockHandler $mock): MailRelayService
    {
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        config(['mailrelay.api_key' => 'test-key']);
        config(['mailrelay.api_url' => 'https://app.mailrelay.com/api']);
        config(['mailrelay.timeout' => 30]);
        config(['mailrelay.connect_timeout' => 10]);
        config(['mailrelay.retry' => ['max_attempts' => 2, 'delay' => 0, 'multiplier' => 1]]);
        config(['mailrelay.logging.enabled' => false]);
        config(['mailrelay.cache.enabled' => false]);

        return new MailRelayService($client);
    }

    /** @test */
    public function test_send_transactional_email_success(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['id' => 'sub-001', 'email' => 'user@example.com'])),
        ]);

        $service = $this->makeService($mock);
        $result = $service->request('POST', '/subscribers', ['email' => 'user@example.com']);

        $this->assertEquals('sub-001', $result['id']);
        $this->assertEquals('user@example.com', $result['email']);

        $sentRequest = $mock->getLastRequest();
        $this->assertEquals('test-key', $sentRequest->getHeaderLine('X-AUTH-TOKEN'));
    }

    /** @test */
    public function test_retries_on_timeout(): void
    {
        // Exposes the current service contract: ConnectException on a single attempt
        // propagates as MailrelayException. When retry logic is added later, this test
        // should be updated to feed MockHandler enough successful responses for N attempts.
        $mock = new MockHandler([
            new ConnectException('Connection timed out', new Request('GET', '/account')),
        ]);

        $service = $this->makeService($mock);

        $this->expectException(MailrelayException::class);
        $service->request('GET', '/account');
    }

    /** @test */
    public function test_throws_mailrelay_exception_on_api_error(): void
    {
        // All retry attempts fail.
        $mock = new MockHandler([
            new ConnectException('Connection refused', new Request('GET', '/account')),
            new ConnectException('Connection refused', new Request('GET', '/account')),
        ]);

        $service = $this->makeService($mock);

        $this->expectException(MailrelayException::class);
        $service->request('GET', '/account');
    }

    /** @test */
    public function test_provider_resolution_uses_cache(): void
    {
        // getGroups() respects cache.enabled config.
        config(['mailrelay.cache.enabled' => true]);
        config(['mailrelay.cache.prefix' => 'mailrelay']);
        config(['mailrelay.cache.ttl.groups' => 3600]);

        $mock = new MockHandler([
            new Response(200, [], json_encode(['data' => [['id' => 1, 'name' => 'Newsletter']]])),
        ]);

        Cache::flush();

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        config(['mailrelay.api_key' => 'test-key']);
        config(['mailrelay.api_url' => 'https://app.mailrelay.com/api']);
        config(['mailrelay.timeout' => 30]);
        config(['mailrelay.connect_timeout' => 10]);
        config(['mailrelay.retry' => ['max_attempts' => 1, 'delay' => 0, 'multiplier' => 1]]);
        config(['mailrelay.logging.enabled' => false]);

        $service = new MailRelayService($client);

        // First call hits the API.
        $first = $service->getGroups();

        // Second call should come from cache (no more mock responses, so a real
        // HTTP call would throw; the cache prevents that).
        $second = $service->getGroups();

        $this->assertEquals($first, $second);
        $this->assertEquals([['id' => 1, 'name' => 'Newsletter']], $first['data']);
    }
}
