<?php

namespace Modules\Analytics\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class AnalyticsCredentialRule implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Check if value is empty
        if (empty($value)) {
            return;
        }

        // Decode JSON
        $credentials = json_decode($value, true);

        // Check for JSON errors
        if (json_last_error() !== JSON_ERROR_NONE) {
            $fail('Las credenciales deben estar en formato JSON válido.');

            return;
        }

        // Check if it's an array
        if (! is_array($credentials)) {
            $fail('Las credenciales deben ser un objeto JSON.');

            return;
        }

        // Required fields for Google Service Account credentials
        $requiredFields = [
            'type',
            'project_id',
            'private_key_id',
            'private_key',
            'client_email',
            'client_id',
            'auth_uri',
            'token_uri',
        ];

        // Check required fields
        foreach ($requiredFields as $field) {
            if (! isset($credentials[$field]) || empty($credentials[$field])) {
                $fail("Las credenciales deben contener el campo '{$field}'.");

                return;
            }
        }

        // Validate type
        if ($credentials['type'] !== 'service_account') {
            $fail('El tipo de credenciales debe ser "service_account".');

            return;
        }

        // Validate email format
        if (! filter_var($credentials['client_email'], FILTER_VALIDATE_EMAIL)) {
            $fail('El campo "client_email" debe ser un email válido.');

            return;
        }

        // Validate private key format
        if (! str_contains($credentials['private_key'], '-----BEGIN PRIVATE KEY-----')) {
            $fail('El campo "private_key" no parece ser una clave privada válida.');

            return;
        }

        // Validate URIs
        $uris = ['auth_uri', 'token_uri'];
        foreach ($uris as $uri) {
            if (! filter_var($credentials[$uri], FILTER_VALIDATE_URL)) {
                $fail("El campo '{$uri}' debe ser una URL válida.");

                return;
            }
        }
    }
}
