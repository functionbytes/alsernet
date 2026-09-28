<?php

namespace Modules\Erp\Tests\Feature\Api;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Modules\Erp\Models\ErpEndpoint;
use Modules\Erp\Models\ErpEndpointLog;
use Modules\Erp\Models\ErpEndpointToken;
use Tests\TestCase;

class PublicEndpointProxyTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mysql', 'mariadb', 'helpdesk'];

    public function test_proxy_does_not_forward_caller_credentials_and_keeps_endpoint_auth(): void
    {
        Http::fake(['example.com/*' => Http::response(['ok' => true], 200)]);

        $endpoint = ErpEndpoint::factory()->create([
            'url' => 'https://example.com/api',
            'method' => 'GET',
            'is_active' => true,
            'headers' => ['X-Api-Key' => 'endpoint-secret'],
            'query_params' => null,
        ]);
        $token = ErpEndpointToken::create([
            'endpoint_id' => $endpoint->id,
            'token' => Str::random(48),
            'name' => 'test',
            'is_active' => true,
        ]);

        $this->withHeaders([
            'Authorization' => 'Bearer caller-token',
            'Cookie' => 'session=abc',
            'X-Api-Key' => 'caller-override',
            'X-Custom' => 'kept',
        ])->getJson("/api/erp/public/{$token->token}/{$endpoint->slug}")->assertOk();

        Http::assertSent(function (ClientRequest $request) {
            return ! $request->hasHeader('Authorization')
                && ! $request->hasHeader('Cookie')
                && $request->header('X-Api-Key') === ['endpoint-secret']
                && $request->header('x-custom') === ['kept'];
        });

        $log = ErpEndpointLog::where('endpoint_id', $endpoint->id)->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertSame($token->id, $log->token_id);
        $this->assertSame('[REDACTED]', $log->request_headers['X-Api-Key']);
    }
}
