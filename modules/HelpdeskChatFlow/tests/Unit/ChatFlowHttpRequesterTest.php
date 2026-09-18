<?php

namespace Modules\HelpdeskChatFlow\Tests\Unit;

use Illuminate\Support\Facades\Http;
use Modules\HelpdeskChatFlow\Services\ChatFlowHttpRequester;
use Tests\TestCase;

class ChatFlowHttpRequesterTest extends TestCase
{
    private ChatFlowHttpRequester $requester;

    protected function setUp(): void
    {
        parent::setUp();
        $this->requester = new ChatFlowHttpRequester;
        config()->set('helpdeskchatflow.http.block_private_hosts', true);
    }

    public function test_extracts_value_from_json_response_by_path(): void
    {
        Http::fake([
            '8.8.8.8/*' => Http::response(['data' => ['estado' => 'enviado']], 200),
        ]);

        $result = $this->requester->send([
            'method' => 'GET',
            'url' => 'http://8.8.8.8/order',
            'response_path' => 'data.estado',
        ], []);

        $this->assertTrue($result['ok']);
        $this->assertSame(200, $result['status']);
        $this->assertSame('enviado', $result['value']);
    }

    public function test_interpolates_context_variables_in_url(): void
    {
        Http::fake(['8.8.8.8/*' => Http::response(['ok' => true], 200)]);

        $this->requester->send([
            'method' => 'GET',
            'url' => 'http://8.8.8.8/order/{{numero_pedido}}',
        ], ['numero_pedido' => '12345']);

        Http::assertSent(fn ($request) => str_contains($request->url(), '/order/12345'));
    }

    public function test_blocks_private_and_loopback_hosts(): void
    {
        Http::fake();

        $result = $this->requester->send([
            'method' => 'GET',
            'url' => 'http://127.0.0.1/internal',
        ], []);

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('privado', $result['error']);
        Http::assertNothingSent();
    }

    public function test_rejects_non_http_schemes(): void
    {
        Http::fake();

        $result = $this->requester->send([
            'method' => 'GET',
            'url' => 'file:///etc/passwd',
        ], []);

        $this->assertFalse($result['ok']);
        Http::assertNothingSent();
    }

    public function test_reports_http_error_status(): void
    {
        Http::fake(['8.8.8.8/*' => Http::response(['error' => 'nope'], 500)]);

        $result = $this->requester->send([
            'method' => 'GET',
            'url' => 'http://8.8.8.8/boom',
        ], []);

        $this->assertFalse($result['ok']);
        $this->assertSame(500, $result['status']);
    }
}
