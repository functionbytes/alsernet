<?php

namespace Modules\HelpdeskEmailLog\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Lookup de SPF/DKIM/DMARC por DNS — sin dependencia externa (dns_get_record
 * es PHP puro) y sin necesitar ningún proveedor con API conectado.
 *
 * SPF y DMARC son registros TXT públicos, directos de consultar. DKIM en
 * cambio vive bajo un selector arbitrario (selector._domainkey.dominio) que
 * NO es autodescubrible sin más — se prueban los selectores configurados
 * para el dominio más una lista de comunes (config
 * helpdeskemaillog.reputation_common_dkim_selectors), pero un selector no
 * encontrado se reporta como "no verificable" (unknown), nunca como
 * "ausente" (fail) — evita un falso negativo que induzca a pensar que DKIM
 * no está configurado cuando en realidad solo se desconoce el selector real.
 *
 * dns_get_record() aislado en su propio método protegido para que los tests
 * puedan sobreescribirlo con datos enlatados sin depender de DNS real.
 */
class DomainAuthenticationChecker
{
    /**
     * @return array{spf: array{status: string, record: ?string}, dmarc: array{status: string, record: ?string, policy: ?string}, dkim: array{status: string, selector: ?string, record: ?string}}
     */
    public function check(string $domain, array $dkimSelectors = []): array
    {
        $hours = (int) config('helpdeskemaillog.reputation_dns_cache_hours', 24);

        return Cache::remember(
            "helpdeskemaillog:domain-auth:{$domain}",
            now()->addHours($hours),
            fn () => [
                'spf' => $this->checkSpf($domain),
                'dmarc' => $this->checkDmarc($domain),
                'dkim' => $this->checkDkim($domain, $dkimSelectors),
            ],
        );
    }

    /**
     * @return array{status: string, record: ?string}
     */
    private function checkSpf(string $domain): array
    {
        foreach ($this->lookupTxt($domain) as $record) {
            if (str_starts_with($record, 'v=spf1')) {
                return ['status' => 'pass', 'record' => $record];
            }
        }

        return ['status' => 'missing', 'record' => null];
    }

    /**
     * @return array{status: string, record: ?string, policy: ?string}
     */
    private function checkDmarc(string $domain): array
    {
        foreach ($this->lookupTxt('_dmarc.'.$domain) as $record) {
            if (str_starts_with($record, 'v=DMARC1')) {
                preg_match('/p=([a-z]+)/i', $record, $m);
                $policy = $m[1] ?? null;

                return [
                    'status' => $policy === 'none' ? 'warning' : 'pass',
                    'record' => $record,
                    'policy' => $policy,
                ];
            }
        }

        return ['status' => 'missing', 'record' => null, 'policy' => null];
    }

    /**
     * @param  list<string>  $configuredSelectors
     * @return array{status: string, selector: ?string, record: ?string}
     */
    private function checkDkim(string $domain, array $configuredSelectors): array
    {
        $common = (array) config('helpdeskemaillog.reputation_common_dkim_selectors', []);
        $selectors = array_values(array_unique([...$configuredSelectors, ...$common]));

        foreach ($selectors as $selector) {
            $records = $this->lookupTxt("{$selector}._domainkey.{$domain}");

            foreach ($records as $record) {
                if (str_contains($record, 'v=DKIM1') || str_contains($record, 'p=')) {
                    return ['status' => 'pass', 'selector' => $selector, 'record' => $record];
                }
            }
        }

        // Ningún selector probado tuvo respuesta — "no verificable", no "fail":
        // el dominio puede tener DKIM real bajo un selector que no está en
        // esta lista.
        return ['status' => 'unknown', 'selector' => null, 'record' => null];
    }

    /**
     * @return list<string>
     */
    protected function lookupTxt(string $host): array
    {
        $records = @dns_get_record($host, DNS_TXT);

        if (! is_array($records)) {
            return [];
        }

        return collect($records)
            ->map(fn (array $r) => $r['txt'] ?? '')
            ->filter()
            ->values()
            ->all();
    }
}
