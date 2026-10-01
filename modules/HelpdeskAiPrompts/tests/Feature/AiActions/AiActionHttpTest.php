<?php

namespace Modules\HelpdeskAiPrompts\Tests\Feature\AiActions;

use Illuminate\Support\Facades\Http;
use Modules\HelpdeskAiPrompts\Models\AiAction;
use Modules\HelpdeskAiPrompts\Models\AiActionRun;
use Modules\HelpdeskAiPrompts\Services\Actions\ActionExecutor;

class AiActionHttpTest extends AiActionsTestCase
{
    private function go(array $args = ['q' => 'x']): array
    {
        return app(ActionExecutor::class)->run('consulta_externa', $args, $this->ctx(), 'test');
    }

    public function test_host_removed_from_allowlist_is_refused_at_runtime(): void
    {
        Http::fake();
        $this->http();
        config()->set('ai-actions.http_allowed_hosts', ['otro.example.com']);

        $this->assertFalse($this->go()['ok']);
        $this->assertSame('host_not_allowed', AiActionRun::query()->value('error'));
        Http::assertNothingSent();
    }

    public function test_host_resolving_to_a_private_ip_is_refused_at_runtime(): void
    {
        Http::fake();
        $this->http();

        foreach (['127.0.0.1', '10.1.2.3', '192.168.0.9', '169.254.169.254', '100.64.0.1', '::1', '::ffff:127.0.0.1'] as $ip) {
            $this->dns->map['api.example.com'] = [$ip];
            $this->assertFalse($this->go()['ok'], $ip);
        }
        Http::assertNothingSent();
    }

    public function test_a_single_private_ip_among_several_is_enough_to_refuse(): void
    {
        Http::fake();
        $this->http();
        $this->dns->map['api.example.com'] = ['93.184.216.34', '10.0.0.1'];

        $this->assertFalse($this->go()['ok']);
        Http::assertNothingSent();
    }

    public function test_redirects_are_not_followed(): void
    {
        Http::fake([
            'api.example.com/*' => Http::response('', 302, ['Location' => 'http://169.254.169.254/latest']),
            '169.254.169.254/*' => Http::response(['items' => [['name' => 'metadata']]]),
        ]);
        $this->http();

        $r = $this->go();

        $this->assertFalse($r['ok']);
        Http::assertSentCount(1);
        $this->assertStringNotContainsString('metadata', $r['content']);
    }

    public function test_plain_http_is_refused_outside_local(): void
    {
        Http::fake();
        $this->app['env'] = 'production';
        $this->http(['config' => ['method' => 'GET', 'url' => 'https://api.example.com/x']]);
        AiAction::query()->getConnection()->table('helpdesk_ai_actions')
            ->where('key', 'consulta_externa')->update(['config' => json_encode(['method' => 'GET', 'url' => 'http://api.example.com/x'])]);

        $this->assertFalse($this->go()['ok']);
        Http::assertNothingSent();
    }

    public function test_oversized_responses_are_refused(): void
    {
        Http::fake(['api.example.com/*' => Http::response(str_repeat('a', 262145))]);
        $this->http();

        $this->assertFalse($this->go()['ok']);
    }

    public function test_non_success_status_is_a_generic_error_for_the_ai(): void
    {
        Http::fake(['api.example.com/*' => Http::response('boom stacktrace /var/www', 500)]);
        $this->http();

        $r = $this->go();

        $this->assertSame('La acción no está disponible.', $r['content']);
    }

    public function test_bearer_secret_is_sent_in_the_header_only(): void
    {
        Http::fake(['api.example.com/*' => Http::response(['items' => [['name' => 'ok']]])]);
        $this->http([
            'config' => ['method' => 'GET', 'url' => 'https://api.example.com/x', 'auth' => ['type' => 'bearer', 'secret' => 'token']],
            'secrets' => ['token' => 'tok-123456'],
            'parameters' => [],
        ]);

        $r = $this->go([]);

        $this->assertTrue($r['ok']);
        Http::assertSent(fn ($req) => $req->header('Authorization') === ['Bearer tok-123456'] && ! str_contains($req->url(), 'tok-123456'));
        $this->assertStringNotContainsString('tok-123456', $r['content']);
    }

    public function test_arguments_are_url_encoded_and_cannot_change_the_host(): void
    {
        Http::fake(['api.example.com/*' => Http::response(['items' => []])]);
        $this->http();

        $this->go(['q' => 'a&b=c/../@evil.com']);

        Http::assertSent(function ($req) {
            return str_starts_with($req->url(), 'https://api.example.com/search?q=a%26b%3Dc%2F..%2F%40evil.com');
        });
    }

    public function test_post_sends_a_resolved_json_body(): void
    {
        Http::fake(['api.example.com/*' => Http::response(['items' => [['name' => 'ok']]])]);
        $this->http([
            'config' => ['method' => 'POST', 'url' => 'https://api.example.com/q', 'body' => ['term' => '{{args.q}}', 'opt' => '{{args.opt}}']],
            'parameters' => [
                ['name' => 'q', 'type' => 'string', 'description' => 'x', 'required' => true],
                ['name' => 'opt', 'type' => 'string', 'description' => 'x', 'required' => false],
            ],
        ]);

        $this->assertTrue($this->go()['ok']);
        Http::assertSent(fn ($req) => $req->method() === 'POST' && $req->data() === ['term' => 'x']);
    }
}
