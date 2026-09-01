<?php

namespace Modules\HelpdeskEmailLog\Tests\Unit\Support;

use Illuminate\Support\Carbon;
use Modules\HelpdeskEmailLog\Support\EngagementBotHeuristics;
use PHPUnit\Framework\TestCase;

/**
 * isLikelyBot() alimenta un badge que un agente lee como "esta apertura/clic
 * probablemente no es del cliente" — un falso positivo aquí opacaría una
 * interacción humana real (p. ej. marcando Outlook real como bot), así que
 * se prueba explícitamente que los User-Agent de clientes de correo
 * normales NO se marcan, solo los escáneres/proxies que se identifican a sí
 * mismos.
 */
class EngagementBotHeuristicsTest extends TestCase
{
    public function test_known_scanner_user_agents_are_flagged(): void
    {
        $sentAt = Carbon::parse('2026-09-01 12:00:00');
        $hitAt = Carbon::parse('2026-09-01 12:05:00'); // 5 min después, no es la señal de timing

        foreach ([
            'Mozilla/5.0 GoogleImageProxy',
            'YahooMailProxy; https://help.yahoo.com/kb/yahoo-mail-proxy',
            'Mimecast-SEG',
            'ProofpointScanner/1.0',
            'Barracuda Sentinel',
            'facebookexternalhit/1.1',
            'Slackbot-LinkExpanding 1.0',
            'WhatsApp/2.23',
            'curl/8.7.1',
            'python-requests/2.31.0',
        ] as $userAgent) {
            $this->assertTrue(
                EngagementBotHeuristics::isLikelyBot($userAgent, $sentAt, $hitAt),
                "Se esperaba que '{$userAgent}' se marcara como probable bot."
            );
        }
    }

    public function test_real_mail_client_user_agents_are_not_flagged(): void
    {
        $sentAt = Carbon::parse('2026-09-01 12:00:00');
        $hitAt = Carbon::parse('2026-09-01 12:05:00');

        // A propósito NO incluye "Outlook"/"Microsoft Office": las usa tanto
        // un escáner como un cliente de Outlook real (ver docblock de la
        // clase) — marcarlos daría falsos positivos masivos.
        foreach ([
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1',
            'Microsoft Office/16.0 (Windows NT 10.0; Microsoft Outlook 16.0.16827; Pro)',
        ] as $userAgent) {
            $this->assertFalse(
                EngagementBotHeuristics::isLikelyBot($userAgent, $sentAt, $hitAt),
                "No se esperaba que '{$userAgent}' se marcara como probable bot."
            );
        }
    }

    public function test_a_hit_within_two_seconds_of_sending_is_flagged_regardless_of_user_agent(): void
    {
        $sentAt = Carbon::parse('2026-09-01 12:00:00.000');
        $hitAt = Carbon::parse('2026-09-01 12:00:01.500');

        $this->assertTrue(EngagementBotHeuristics::isLikelyBot('Mozilla/5.0 Chrome/120', $sentAt, $hitAt));
    }

    public function test_a_hit_well_after_sending_with_a_normal_user_agent_is_not_flagged(): void
    {
        $sentAt = Carbon::parse('2026-09-01 12:00:00');
        $hitAt = Carbon::parse('2026-09-01 12:00:10');

        $this->assertFalse(EngagementBotHeuristics::isLikelyBot('Mozilla/5.0 Chrome/120', $sentAt, $hitAt));
    }

    public function test_missing_sent_at_or_hit_at_only_relies_on_user_agent(): void
    {
        $this->assertFalse(EngagementBotHeuristics::isLikelyBot('Mozilla/5.0 Chrome/120', null, null));
        $this->assertTrue(EngagementBotHeuristics::isLikelyBot('GoogleImageProxy', null, null));
    }

    public function test_null_user_agent_does_not_error(): void
    {
        $this->assertFalse(EngagementBotHeuristics::isLikelyBot(null, Carbon::now(), Carbon::now()->addMinute()));
    }
}
