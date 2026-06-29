<?php

namespace Modules\HelpdeskChatFlow\Services;

use Illuminate\Support\Facades\Log;

class CustomerIdentityResolver
{
    public function __construct(
        private readonly ?object $erpContext,
        private readonly ?object $psContext,
    ) {}

    /**
     * Resolve customer identity from free-text input (email, phone, or NIF/CIF).
     * Returns a normalized customer array or null if not found.
     *
     * @param  array<string>  $sources  Which sources to search: 'erp', 'ps'
     * @return array<string,mixed>|null
     */
    public function resolve(string $input, array $sources = ['erp', 'ps']): ?array
    {
        $input = trim($input);

        if (str_contains($input, '@')) {
            return $this->byEmail($input, $sources);
        }

        $digits = preg_replace('/\D/', '', $input);
        if (strlen($digits) >= 9) {
            return $this->byPhone($digits, $sources);
        }

        if (strlen($input) >= 5) {
            return $this->byNif($input, $sources);
        }

        return null;
    }

    /** @return array<string,mixed>|null */
    private function byEmail(string $email, array $sources): ?array
    {
        $result = null;

        if (in_array('erp', $sources) && $this->erpContext) {
            try {
                $ctx = $this->erpContext->getCustomerContext($email);
                if ($ctx['customer']['found'] ?? false) {
                    $result = $this->fromErp($ctx['customer']);
                }
            } catch (\Throwable $e) {
                Log::warning('ChatFlow ERP lookup failed', ['email' => $email, 'error' => $e->getMessage()]);
            }
        }

        if (in_array('ps', $sources) && $this->psContext) {
            try {
                $ctx = $this->psContext->getCustomerContext($email);
                if ($ctx['customer']['found'] ?? false) {
                    $ps = $this->fromPs($ctx['customer'], $email);
                    if ($result === null) {
                        $result = $ps;
                    } else {
                        $result['ps_id'] = $ps['ps_id'] ?? null;
                        $result['source'] = 'both';
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('ChatFlow PS lookup failed', ['email' => $email, 'error' => $e->getMessage()]);
            }
        }

        return $result;
    }

    /** @return array<string,mixed>|null */
    private function byPhone(string $digits, array $sources): ?array
    {
        if (! in_array('erp', $sources) || ! $this->erpContext) {
            return null;
        }

        try {
            $customers = $this->erpContext->searchCustomers($digits, 'phone');
            $first = $customers[0] ?? null;

            if (! $first) {
                return null;
            }

            if (! empty($first['email'])) {
                return $this->byEmail($first['email'], $sources) ?? $this->fromSearchResult($first, 'erp');
            }
        } catch (\Throwable $e) {
            Log::warning('ChatFlow ERP phone lookup failed', ['digits' => $digits, 'error' => $e->getMessage()]);
        }

        return null;
    }

    /** @return array<string,mixed>|null */
    private function byNif(string $nif, array $sources): ?array
    {
        if (! in_array('erp', $sources) || ! $this->erpContext) {
            return null;
        }

        try {
            $customers = $this->erpContext->searchCustomers(strtoupper($nif), 'nif');
            $first = $customers[0] ?? null;

            if (! $first) {
                return null;
            }

            if (! empty($first['email'])) {
                return $this->byEmail($first['email'], $sources) ?? $this->fromSearchResult($first, 'erp');
            }
        } catch (\Throwable $e) {
            Log::warning('ChatFlow ERP NIF lookup failed', ['nif' => $nif, 'error' => $e->getMessage()]);
        }

        return null;
    }

    /** @return array<string,mixed> */
    private function fromErp(array $c): array
    {
        return [
            'name' => $c['name'] ?? trim(($c['nombre'] ?? '').' '.($c['apellidos'] ?? '')),
            'email' => $c['email'] ?? null,
            'nif' => $c['nif'] ?? $c['cif'] ?? null,
            'erp_id' => $c['id'] ?? $c['idcliente'] ?? null,
            'ps_id' => null,
            'source' => 'erp',
        ];
    }

    /** @return array<string,mixed> */
    private function fromPs(array $c, string $email): array
    {
        return [
            'name' => trim(($c['firstname'] ?? $c['first_name'] ?? '').' '.($c['lastname'] ?? $c['last_name'] ?? '')),
            'email' => $c['email'] ?? $email,
            'nif' => null,
            'erp_id' => null,
            'ps_id' => $c['id'] ?? $c['id_customer'] ?? null,
            'source' => 'ps',
        ];
    }

    /** @return array<string,mixed> */
    private function fromSearchResult(array $r, string $source): array
    {
        return [
            'name' => trim(($r['label'] ?? '').' '.($r['surnames'] ?? '')),
            'email' => $r['email'] ?? null,
            'nif' => $r['cif'] ?? null,
            'erp_id' => $r['id'] ?? null,
            'ps_id' => null,
            'source' => $source,
        ];
    }
}
