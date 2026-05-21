<?php

namespace Modules\Remarketing\Services;

class DeliverabilityCheckerService
{
    /**
     * Check SPF, DKIM (rmk._domainkey selector), and DMARC for a given domain.
     *
     * @return array{spf: bool, dkim: bool, dmarc: bool, details: array<string, mixed>}
     */
    public function check(string $domain): array
    {
        $domain = strtolower(trim($domain));

        // Skip DNS lookups for localhost or IP addresses
        if ($domain === 'localhost' || filter_var($domain, FILTER_VALIDATE_IP)) {
            return [
                'spf' => false,
                'dkim' => false,
                'dmarc' => false,
                'details' => [
                    'spf' => ['found' => false, 'record' => null],
                    'dkim' => ['found' => false, 'record' => null, 'selector' => 'rmk'],
                    'dmarc' => ['found' => false, 'record' => null],
                ],
            ];
        }

        $spfResult = $this->checkSpf($domain);
        $dkimResult = $this->checkDkim($domain);
        $dmarcResult = $this->checkDmarc($domain);

        return [
            'spf' => $spfResult['found'],
            'dkim' => $dkimResult['found'],
            'dmarc' => $dmarcResult['found'],
            'details' => [
                'spf' => $spfResult,
                'dkim' => $dkimResult,
                'dmarc' => $dmarcResult,
            ],
        ];
    }

    /**
     * Verify SPF record exists on the domain.
     */
    private function checkSpf(string $domain): array
    {
        $records = @dns_get_record($domain, DNS_TXT) ?: [];

        foreach ($records as $record) {
            $txt = $record['txt'] ?? '';
            if (stripos($txt, 'v=spf1') !== false) {
                return ['found' => true, 'record' => $txt];
            }
        }

        return ['found' => false, 'record' => null];
    }

    /**
     * Verify DKIM record for the rmk._domainkey selector.
     */
    private function checkDkim(string $domain): array
    {
        $dkimHost = 'rmk._domainkey.'.$domain;
        $records = @dns_get_record($dkimHost, DNS_TXT) ?: [];

        foreach ($records as $record) {
            $txt = $record['txt'] ?? '';
            if (stripos($txt, 'v=DKIM1') !== false || stripos($txt, 'k=rsa') !== false) {
                return ['found' => true, 'record' => $txt, 'selector' => 'rmk'];
            }
        }

        return ['found' => false, 'record' => null, 'selector' => 'rmk'];
    }

    /**
     * Verify DMARC record exists on _dmarc.domain.
     */
    private function checkDmarc(string $domain): array
    {
        $dmarcHost = '_dmarc.'.$domain;
        $records = @dns_get_record($dmarcHost, DNS_TXT) ?: [];

        foreach ($records as $record) {
            $txt = $record['txt'] ?? '';
            if (stripos($txt, 'v=DMARC1') !== false) {
                return ['found' => true, 'record' => $txt];
            }
        }

        return ['found' => false, 'record' => null];
    }
}
