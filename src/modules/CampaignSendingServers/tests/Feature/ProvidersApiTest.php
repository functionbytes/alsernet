<?php

namespace Modules\CampaignSendingServers\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Modules\CampaignSendingServers\Models\SendingServer;
use Symfony\Component\Mime\Email;
use Tests\TestCase;

/**
 * Verifica que cada SendingServer*Api construye el payload correcto al
 * enviar un Symfony\Mime\Email. Usa Http::fake() — no necesita red real.
 */
class ProvidersApiTest extends TestCase
{
    use RefreshDatabase;

    protected function makeEmail(): Email
    {
        return (new Email)
            ->from('sender@example.com', 'Sender')
            ->to('recipient@example.com')
            ->subject('Hola desde test')
            ->html('<p>Hola {{FIRST_NAME}}</p>')
            ->text('Hola FIRST_NAME');
    }

    public function test_sendgrid_api_builds_correct_request(): void
    {
        Http::fake([
            'api.sendgrid.com/*' => Http::response(['x' => 'ok'], 202, ['X-Message-Id' => 'sg-123']),
        ]);

        $server = SendingServer::create([
            'name' => 'SG',
            'type' => 'sendgrid-api',
            'api_key' => 'SG.test',
        ])->mapType();

        $result = $server->send($this->makeEmail());

        $this->assertEquals('sent', $result['status']);
        $this->assertEquals('sg-123', $result['runtime_message_id']);

        Http::assertSent(function ($req) {
            $body = $req->data();

            return str_contains($req->url(), 'api.sendgrid.com/v3/mail/send')
                && $req->hasHeader('Authorization', 'Bearer SG.test')
                && $body['from']['email'] === 'sender@example.com'
                && $body['personalizations'][0]['to'][0]['email'] === 'recipient@example.com'
                && $body['subject'] === 'Hola desde test';
        });
    }

    public function test_brevo_api_builds_correct_request(): void
    {
        Http::fake([
            'api.brevo.com/*' => Http::response(['messageId' => 'brevo-456'], 201),
        ]);

        $server = SendingServer::create([
            'name' => 'Brevo',
            'type' => 'brevo-api',
            'api_key' => 'xkeysib-test',
        ])->mapType();

        $result = $server->send($this->makeEmail());

        $this->assertEquals('sent', $result['status']);
        $this->assertEquals('brevo-456', $result['runtime_message_id']);

        Http::assertSent(fn ($req) => $req->hasHeader('api-key', 'xkeysib-test')
            && str_contains($req->url(), 'api.brevo.com'));
    }

    public function test_sparkpost_api_uses_eu_endpoint_when_region_is_eu(): void
    {
        Http::fake([
            'api.eu.sparkpost.com/*' => Http::response(['results' => ['id' => 'sp-eu-1']], 200),
        ]);

        $server = SendingServer::create([
            'name' => 'SP EU',
            'type' => 'sparkpost-api',
            'api_key' => 'sp-key',
            'aws_region' => 'eu',  // reusamos la columna
        ])->mapType();

        $server->send($this->makeEmail());

        Http::assertSent(fn ($req) => str_contains($req->url(), 'api.eu.sparkpost.com'));
    }

    public function test_mailgun_api_uses_basic_auth_with_api_user(): void
    {
        Http::fake([
            'api.mailgun.net/*' => Http::response(['id' => 'mg-789'], 200),
        ]);

        $server = SendingServer::create([
            'name' => 'MG',
            'type' => 'mailgun-api',
            'api_key' => 'mg-key',
            'domain' => 'mg.example.com',
        ])->mapType();

        $server->send($this->makeEmail());

        Http::assertSent(function ($req) {
            $auth = $req->headers()['Authorization'][0] ?? '';

            return str_contains($req->url(), 'mg.example.com/messages')
                && str_starts_with($auth, 'Basic ');
        });
    }

    public function test_failed_provider_response_throws(): void
    {
        Http::fake([
            'api.sendgrid.com/*' => Http::response(['errors' => 'forbidden'], 401),
        ]);

        $server = SendingServer::create(['name' => 'SG', 'type' => 'sendgrid-api', 'api_key' => 'bad'])->mapType();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/SendGrid API error: 401/');

        $server->send($this->makeEmail());
    }
}
