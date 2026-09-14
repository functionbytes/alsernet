<?php

namespace Modules\Helpdesk\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Modules\Helpdesk\Jobs\DispatchWebhookJob;
use Modules\Helpdesk\Models\Webhook;
use Tests\TestCase;

class WebhookSsrfTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = [null, 'helpdesk'];

    private function webhook(string $url): Webhook
    {
        return Webhook::create([
            'name' => 'Test',
            'url' => $url,
            'integration_type' => 'generic',
            'events' => ['ticket.created'],
            'is_active' => true,
            'user_id' => User::factory()->create()->id,
        ]);
    }

    public function test_el_job_no_envia_a_una_ip_interna_metadata_cloud(): void
    {
        Http::fake();
        $webhook = $this->webhook('http://169.254.169.254/latest/meta-data/');

        (new DispatchWebhookJob($webhook->id, 'ticket.created', ['title' => 'x']))->handle();

        Http::assertNothingSent();
        $this->assertStringContainsString('SSRF', (string) $webhook->fresh()->last_error);
    }

    public function test_el_job_no_envia_a_loopback(): void
    {
        Http::fake();
        $webhook = $this->webhook('http://127.0.0.1:8080/hook');

        (new DispatchWebhookJob($webhook->id, 'ticket.created', ['title' => 'x']))->handle();

        Http::assertNothingSent();
    }

    public function test_el_job_si_envia_a_ip_publica(): void
    {
        Http::fake(['*' => Http::response('ok', 200)]);
        // IP pública literal (evita depender de resolución DNS en el entorno de test).
        $webhook = $this->webhook('http://8.8.8.8/hook');

        (new DispatchWebhookJob($webhook->id, 'ticket.created', ['title' => 'x']))->handle();

        Http::assertSent(fn ($request) => $request->url() === 'http://8.8.8.8/hook');
    }
}
