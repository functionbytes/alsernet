<?php

namespace Modules\Mailrelay\Services\EmailValidation\Validators;

use Illuminate\Support\Facades\Cache;
use Modules\Mailrelay\Services\EmailValidation\ValidatorInterface;

class DnsValidator implements ValidatorInterface
{
    private int $cacheTtl = 86400; // 24 hours

    public function validate(string $email): array
    {
        $domain = $this->extractDomain($email);

        if (! $domain) {
            return [
                'valid' => false,
                'score' => 0,
                'details' => ['error' => 'Invalid email format'],
                'message' => 'Cannot extract domain from email',
            ];
        }

        // Check cache
        $cacheKey = "dns_validation:{$domain}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($domain) {
            return $this->performDnsCheck($domain);
        });
    }

    public function getName(): string
    {
        return 'dns';
    }

    private function extractDomain(string $email): ?string
    {
        if (! str_contains($email, '@')) {
            return null;
        }

        $parts = explode('@', $email);

        return end($parts);
    }

    private function performDnsCheck(string $domain): array
    {
        // Check for A record
        $hasARecord = checkdnsrr($domain, 'A');

        // Check for MX record
        $mxRecords = [];
        $hasMxRecord = getmxrr($domain, $mxRecords);

        $isValid = $hasARecord || $hasMxRecord;
        $score = $isValid ? 100 : 0;

        return [
            'valid' => $isValid,
            'score' => $score,
            'details' => [
                'domain' => $domain,
                'has_a_record' => $hasARecord,
                'has_mx_record' => $hasMxRecord,
                'mx_records' => $mxRecords,
            ],
            'message' => $isValid ? 'Domain has valid DNS records' : 'Domain has no valid DNS records',
        ];
    }
}
