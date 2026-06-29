<?php

namespace Modules\Campaign\Services;

/**
 * Valida registros DNS SPF, DKIM y DMARC para un dominio.
 */
class DeliverabilityTester
{
    public function check(string $domain): array
    {
        $results = [
            'domain' => $domain,
            'spf' => $this->checkSpf($domain),
            'dkim' => null, // requiere selector conocido
            'dmarc' => $this->checkDmarc($domain),
            'mx' => $this->checkMx($domain),
        ];

        $results['score'] = $this->score($results);

        return $results;
    }

    public function checkDkim(string $domain, string $selector = 'default'): array
    {
        $record = dns_get_record("{$selector}._domainkey.{$domain}", DNS_TXT);
        $found = ! empty($record) && collect($record)->contains(fn ($r) => str_contains($r['txt'] ?? '', 'v=DKIM1'));

        return [
            'present' => $found,
            'selector' => $selector,
            'record' => $found ? $record[0]['txt'] : null,
        ];
    }

    protected function checkSpf(string $domain): array
    {
        $records = dns_get_record($domain, DNS_TXT);
        $found = false;
        $value = null;

        foreach ($records ?: [] as $record) {
            $txt = $record['txt'] ?? '';
            if (str_starts_with($txt, 'v=spf1')) {
                $found = true;
                $value = $txt;
                break;
            }
        }

        return [
            'present' => $found,
            'record' => $value,
            'valid' => $found && str_contains($value ?? '', '~all') || str_contains($value ?? '', '-all'),
        ];
    }

    protected function checkDmarc(string $domain): array
    {
        $records = dns_get_record('_dmarc.'.$domain, DNS_TXT);
        $found = false;
        $value = null;

        foreach ($records ?: [] as $record) {
            $txt = $record['txt'] ?? '';
            if (str_starts_with($txt, 'v=DMARC1')) {
                $found = true;
                $value = $txt;
                break;
            }
        }

        return [
            'present' => $found,
            'record' => $value,
            'policy' => $found ? $this->parseDmarcPolicy($value) : null,
        ];
    }

    protected function checkMx(string $domain): array
    {
        $records = dns_get_record($domain, DNS_MX);

        return [
            'present' => ! empty($records),
            'records' => collect($records ?? [])->pluck('target')->filter()->values()->all(),
        ];
    }

    protected function parseDmarcPolicy(?string $value): ?string
    {
        if (! $value) {
            return null;
        }
        preg_match('/p=(\w+)/', $value, $m);

        return $m[1] ?? null;
    }

    protected function score(array $results): int
    {
        $score = 0;
        if ($results['spf']['present'] ?? false) {
            $score += 30;
        }
        if ($results['dmarc']['present'] ?? false) {
            $score += 30;
        }
        if ($results['mx']['present'] ?? false) {
            $score += 20;
        }
        if ($results['dkim']['present'] ?? false) {
            $score += 20;
        }

        return $score;
    }
}
