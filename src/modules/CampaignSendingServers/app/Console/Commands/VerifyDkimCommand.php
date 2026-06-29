<?php

namespace Modules\CampaignSendingServers\Console\Commands;

use Illuminate\Console\Command;
use Modules\CampaignSendingServers\Library\DkimGenerator;
use Modules\CampaignSendingServers\Library\DnsResolver;
use Modules\CampaignSendingServers\Models\SendingDomain;

class VerifyDkimCommand extends Command
{
    protected $signature = 'campaign-sending-servers:verify-dkim
                            {--uid= : Verificar sólo este dominio}
                            {--all : Re-verificar también los ya verificados}';

    protected $description = 'Verifica que los SendingDomain tengan publicado correctamente el TXT DKIM en DNS.';

    public function handle(): int
    {
        $query = SendingDomain::query()->where('signing_enabled', true);
        if ($this->option('uid')) {
            $query->where('uid', $this->option('uid'));
        } elseif (! $this->option('all')) {
            $query->where('status', '!=', SendingDomain::STATUS_VERIFIED);
        }

        $domains = $query->get();
        $this->info("Verificando DKIM en {$domains->count()} dominio(s)…");

        foreach ($domains as $domain) {
            if (empty($domain->dkim_public_key) || empty($domain->dkim_selector)) {
                $this->warn("- {$domain->name}: sin clave generada. Genera con `dkim:generate-keys`.");
                $domain->update(['status' => SendingDomain::STATUS_FAILED]);

                continue;
            }

            $fqdn = DkimGenerator::dnsName($domain->dkim_selector, $domain->name);
            $expected = DkimGenerator::dnsRecord($domain->dkim_public_key);

            $found = false;
            foreach (DnsResolver::txt($fqdn) as $txt) {
                // Comparación tolerante a espacios y a clave separada en chunks
                if ($this->normalize($txt) === $this->normalize($expected)) {
                    $found = true;
                    break;
                }
            }

            if ($found) {
                $domain->update([
                    'status' => SendingDomain::STATUS_VERIFIED,
                    'verified_at' => now(),
                ]);
                $this->line("✓ {$domain->name} ({$fqdn})");
            } else {
                $domain->update(['status' => SendingDomain::STATUS_FAILED]);
                $this->line("✗ {$domain->name} ({$fqdn}) — registro TXT no coincide");
            }
        }

        return self::SUCCESS;
    }

    protected function normalize(string $txt): string
    {
        return preg_replace('/\s+/', '', $txt);
    }
}
