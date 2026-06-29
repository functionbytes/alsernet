<?php

namespace Modules\CampaignSendingServers\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Modules\CampaignSendingServers\Library\DnsResolver;
use Modules\CampaignSendingServers\Models\TrackingDomain;

/**
 * Verifica los TrackingDomain pendientes consultando DNS real.
 *
 * Estrategias:
 *   - cname: el dominio debe tener un CNAME apuntando al host de la app.
 *   - host:  el dominio debe tener un registro A con la IP del servidor.
 *   - caddy: TLS gestionado por Caddy AutoSSL — sólo verificar accesibilidad HTTP.
 */
class VerifyTrackingDomainsCommand extends Command
{
    protected $signature = 'campaign-sending-servers:verify-tracking-domains
                            {--uid= : Verificar sólo el dominio con este uid}
                            {--all : Re-verificar también los ya verificados}';

    protected $description = 'Verifica los tracking domains via DNS (CNAME / A / Caddy).';

    public function handle(): int
    {
        $query = TrackingDomain::query();
        if ($this->option('uid')) {
            $query->where('uid', $this->option('uid'));
        } elseif (! $this->option('all')) {
            $query->where('status', '!=', TrackingDomain::STATUS_VERIFIED);
        }

        $domains = $query->get();
        $this->info("Verificando {$domains->count()} dominio(s)…");

        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'localhost';
        $appIp = $this->resolveAppIp($appHost);
        $expectedTarget = strtolower($appHost);

        foreach ($domains as $domain) {
            $ok = match ($domain->verification_method) {
                TrackingDomain::METHOD_CNAME => $this->verifyCname($domain->name, $expectedTarget),
                TrackingDomain::METHOD_HOST => $this->verifyHost($domain->name, $appIp),
                TrackingDomain::METHOD_CADDY => $this->verifyCaddy($domain->name),
                default => false,
            };

            if ($ok) {
                $domain->update([
                    'status' => TrackingDomain::STATUS_VERIFIED,
                    'verified_at' => now(),
                ]);
                $this->line("✓ {$domain->name}");
            } else {
                $domain->update([
                    'status' => TrackingDomain::STATUS_FAILED,
                ]);
                $this->line("✗ {$domain->name}");
            }
        }

        return self::SUCCESS;
    }

    protected function verifyCname(string $name, string $expectedTarget): bool
    {
        $cnames = DnsResolver::cname($name);
        foreach ($cnames as $target) {
            if ($target === $expectedTarget || str_ends_with($target, '.'.$expectedTarget)) {
                return true;
            }
        }

        return false;
    }

    protected function verifyHost(string $name, ?string $expectedIp): bool
    {
        if (! $expectedIp) {
            return false;
        }
        $ips = DnsResolver::a($name);

        return in_array($expectedIp, $ips, true);
    }

    protected function verifyCaddy(string $name): bool
    {
        try {
            $response = Http::timeout(5)
                ->head("https://{$name}/healthz");

            return $response->status() < 500; // accesible vía Caddy
        } catch (\Throwable) {
            return false;
        }
    }

    protected function resolveAppIp(string $host): ?string
    {
        $records = DnsResolver::a($host);

        return $records[0] ?? null;
    }
}
