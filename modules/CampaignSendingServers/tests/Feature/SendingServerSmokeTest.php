<?php

namespace Modules\CampaignSendingServers\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\CampaignSendingServers\Library\Exception\RateLimitExceeded;
use Modules\CampaignSendingServers\Library\InMemoryRateTracker;
use Modules\CampaignSendingServers\Library\RateLimit;
use Modules\CampaignSendingServers\Library\RouletteWheel;
use Modules\CampaignSendingServers\Models\Blacklist;
use Modules\CampaignSendingServers\Models\SendingServer;
use Modules\CampaignSendingServers\Models\SendingServerSendmail;
use Modules\CampaignSendingServers\Models\SendingServerSmtp;
use Tests\TestCase;

/**
 * Smoke test del módulo CampaignSendingServers.
 *
 * Verifica:
 *   - migrate:fresh corre limpio (RefreshDatabase implícito)
 *   - SendingServer persiste con credenciales cifradas
 *   - mapType() devuelve la subclase correcta
 *   - RouletteWheel acepta servidores y los selecciona
 *   - Blacklist rechaza emails ya listados
 */
class SendingServerSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_smtp_server_with_encrypted_password(): void
    {
        $server = SendingServer::create([
            'name' => 'Test SMTP',
            'type' => SendingServer::TYPE_SMTP,
            'host' => 'smtp.example.com',
            'smtp_port' => 587,
            'smtp_protocol' => 'tls',
            'smtp_username' => 'user@example.com',
            'smtp_password' => 'super-secret',
            'default_from_email' => 'noreply@example.com',
        ]);

        $this->assertDatabaseHas('campaign_sending_servers', [
            'name' => 'Test SMTP',
            'type' => 'smtp',
        ]);

        // La columna en BD debe estar cifrada (no contener el plain)
        $raw = \DB::table('campaign_sending_servers')
            ->where('id', $server->id)
            ->value('smtp_password');
        $this->assertNotEquals('super-secret', $raw, 'La password debe estar cifrada en BD');

        // El cast debe descifrarla en el modelo
        $this->assertEquals('super-secret', $server->fresh()->smtp_password);
    }

    public function test_map_type_returns_correct_subclass(): void
    {
        $server = SendingServer::create([
            'name' => 'Test SMTP',
            'type' => SendingServer::TYPE_SMTP,
            'host' => 'smtp.example.com',
            'smtp_port' => 587,
            'smtp_username' => 'u',
            'smtp_password' => 'p',
        ]);

        $this->assertInstanceOf(SendingServerSmtp::class, $server->mapType());

        $sendmail = SendingServer::create([
            'name' => 'Sendmail Local',
            'type' => SendingServer::TYPE_SENDMAIL,
            'sendmail_path' => '/usr/sbin/sendmail',
        ]);

        $this->assertInstanceOf(SendingServerSendmail::class, $sendmail->mapType());
    }

    public function test_roulette_wheel_picks_a_server(): void
    {
        $a = SendingServer::create(['name' => 'A', 'type' => SendingServer::TYPE_SMTP, 'host' => 'a', 'smtp_port' => 587, 'smtp_username' => 'u', 'smtp_password' => 'p']);
        $b = SendingServer::create(['name' => 'B', 'type' => SendingServer::TYPE_SMTP, 'host' => 'b', 'smtp_port' => 587, 'smtp_username' => 'u', 'smtp_password' => 'p']);

        $wheel = new RouletteWheel;
        $wheel->add($a, 1.0);
        $wheel->add($b, 1.0);

        $this->assertEquals(2, $wheel->count());
        $picked = $wheel->select();
        $this->assertNotNull($picked);
    }

    public function test_blacklist_lookup(): void
    {
        Blacklist::create([
            'email' => 'spam@example.com',
            'reason' => 'manual',
            'source' => Blacklist::SOURCE_MANUAL,
        ]);

        $this->assertTrue(Blacklist::isBlacklisted('spam@example.com'));
        $this->assertFalse(Blacklist::isBlacklisted('clean@example.com'));
    }

    public function test_rate_limit_exceeded_exception_exists(): void
    {
        $this->expectException(RateLimitExceeded::class);

        // Construye un tracker que falla siempre (limit = 0)
        $limit = new RateLimit(0, 1, 'minute', 'sin envíos permitidos');
        $tracker = new InMemoryRateTracker(
            'test-rate-key-'.uniqid(),
            [$limit],
        );

        // El tercer count() debería disparar la excepción al exceder.
        for ($i = 0; $i < 3; $i++) {
            $tracker->count(now());
        }
    }
}
