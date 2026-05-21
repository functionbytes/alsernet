<?php

namespace Modules\Remarketing\Helpers;

use Modules\Remarketing\Models\Customer;

class PersonalizationTokens
{
    /**
     * Replace {{token}} placeholders in a string.
     *
     * Customer-aware: if a Customer is passed, it auto-injects
     * common tokens (first_name, last_name, email, full_name).
     * Tokens are also matched with surrounding spaces: {{ token }}.
     * Unresolved tokens are stripped to avoid leaking placeholders.
     */
    public static function replace(string $template, array $tokens = [], ?Customer $customer = null): string
    {
        if ($customer) {
            $tokens = array_merge([
                'first_name' => $customer->first_name ?? '',
                'last_name' => $customer->last_name ?? '',
                'email' => $customer->email ?? '',
                'full_name' => trim(($customer->first_name ?? '').' '.($customer->last_name ?? '')),
            ], $tokens);
        }

        foreach ($tokens as $key => $value) {
            $value = ($value === null || $value === '') ? '' : (string) $value;
            $template = str_replace('{{'.$key.'}}', $value, $template);
            $template = str_replace('{{ '.$key.' }}', $value, $template);
        }

        // Strip any remaining unresolved tokens
        return (string) preg_replace('/\{\{\s*[a-z_][a-z0-9_]*\s*\}\}/i', '', $template);
    }
}
