<?php

namespace Modules\HelpdeskAiPrompts\Services\Actions;

/**
 * Resuelve un host a sus IPs (A y AAAA). Aislado en una clase para poder
 * sustituirlo en tests y para fijar la IP validada al hacer la petición.
 */
class HostResolver
{
    /**
     * @return array<int, string>
     */
    public function resolve(string $host): array
    {
        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return [$host];
        }

        $ips = gethostbynamel($host) ?: [];

        foreach (@dns_get_record($host, DNS_AAAA) ?: [] as $record) {
            if (! empty($record['ipv6'])) {
                $ips[] = $record['ipv6'];
            }
        }

        return array_values(array_unique($ips));
    }
}
