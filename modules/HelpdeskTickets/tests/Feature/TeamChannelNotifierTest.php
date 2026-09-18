<?php

namespace Modules\HelpdeskTickets\Tests\Feature;

use Illuminate\Support\Facades\Http;
use Modules\Core\Models\Setting;
use Modules\HelpdeskTickets\Services\TeamChannelNotifier;
use Modules\HelpdeskTickets\Tests\Concerns\SharesHelpdeskPdo;
use Tests\TestCase;

/**
 * Modal 31 "Notificaciones": integración real de Slack/Teams — un webhook de
 * equipo por plataforma (Setting cifrado), OFF por defecto sin URL configurada.
 */
class TeamChannelNotifierTest extends TestCase
{
    use SharesHelpdeskPdo;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::set('tickets.slack_webhook_url', '');
        Setting::set('tickets.teams_webhook_url', '');
    }

    public function test_sin_webhooks_configurados_no_hace_ninguna_peticion(): void
    {
        Http::fake();

        $result = app(TeamChannelNotifier::class)->notify('hola');

        $this->assertNull($result['slack']);
        $this->assertNull($result['teams']);
        Http::assertNothingSent();
    }

    public function test_manda_el_formato_correcto_a_cada_plataforma_configurada(): void
    {
        Http::fake(['*' => Http::response('', 200)]);
        Setting::setEncrypted('tickets.slack_webhook_url', 'https://1.1.1.1/slack-hook');
        Setting::setEncrypted('tickets.teams_webhook_url', 'https://1.1.1.1/teams-hook');

        $result = app(TeamChannelNotifier::class)->notify('SLA incumplido');

        $this->assertTrue($result['slack']);
        $this->assertTrue($result['teams']);

        Http::assertSent(fn ($request) => $request->url() === 'https://1.1.1.1/slack-hook'
            && $request['text'] === 'SLA incumplido');

        Http::assertSent(fn ($request) => $request->url() === 'https://1.1.1.1/teams-hook'
            && $request['@type'] === 'MessageCard'
            && $request['text'] === 'SLA incumplido');
    }

    public function test_un_webhook_caido_no_bloquea_al_otro(): void
    {
        Setting::setEncrypted('tickets.slack_webhook_url', 'https://1.1.1.1/slack-hook');
        Setting::setEncrypted('tickets.teams_webhook_url', 'https://1.1.1.1/teams-hook');

        Http::fake([
            'https://1.1.1.1/slack-hook' => Http::response('', 500),
            'https://1.1.1.1/teams-hook' => Http::response('', 200),
        ]);

        $result = app(TeamChannelNotifier::class)->notify('x');

        $this->assertFalse($result['slack']);
        $this->assertTrue($result['teams']);
    }

    public function test_slack_configured_refleja_el_setting_cifrado(): void
    {
        $notifier = app(TeamChannelNotifier::class);
        $this->assertFalse($notifier->slackConfigured());

        Setting::setEncrypted('tickets.slack_webhook_url', 'https://1.1.1.1/slack-hook');

        $this->assertTrue($notifier->slackConfigured());
        $this->assertFalse($notifier->teamsConfigured());
    }
}
