<?php

namespace Modules\CampaignSendingServers\Library;

use Exception;

/**
 * Genera un par RSA 2048 bits para DKIM y construye el registro DNS TXT
 * que el usuario debe publicar en su zona.
 *
 * El selector por defecto es "mail" — se puede cambiar al crear el dominio.
 * El registro DNS resultante se publica en `<selector>._domainkey.<dominio>`
 * con valor `v=DKIM1; k=rsa; p=<base64-public-key>`.
 *
 * Requiere la extensión PHP `openssl` (estándar en stack Laravel).
 */
class DkimGenerator
{
    /**
     * Genera el par RSA y devuelve {public_key, private_key, selector}.
     *
     * @return array{public_key: string, private_key: string, selector: string}
     */
    public static function generate(string $selector = 'mail'): array
    {
        if (! function_exists('openssl_pkey_new')) {
            throw new Exception('La extensión openssl es necesaria para generar claves DKIM.');
        }

        $resource = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        if ($resource === false) {
            throw new Exception('openssl_pkey_new falló: '.openssl_error_string());
        }

        // Private key en PEM
        openssl_pkey_export($resource, $privatePem);

        // Public key — extraer y limpiar BEGIN/END/headers
        $details = openssl_pkey_get_details($resource);
        $publicPem = $details['key'] ?? '';
        $publicBase64 = self::stripPemHeaders($publicPem);

        return [
            'public_key' => $publicBase64,
            'private_key' => $privatePem,
            'selector' => $selector,
        ];
    }

    /**
     * Construye el registro DNS TXT que el usuario debe publicar.
     */
    public static function dnsRecord(string $publicKeyBase64): string
    {
        return 'v=DKIM1; k=rsa; p='.$publicKeyBase64;
    }

    /**
     * Construye el FQDN del registro: `<selector>._domainkey.<domain>`.
     */
    public static function dnsName(string $selector, string $domain): string
    {
        return "{$selector}._domainkey.{$domain}";
    }

    protected static function stripPemHeaders(string $pem): string
    {
        $lines = explode("\n", trim($pem));
        // Quita BEGIN/END y une el cuerpo base64
        $body = array_filter($lines, fn ($l) => ! str_starts_with($l, '-----'));

        return preg_replace('/\s+/', '', implode('', $body));
    }
}
