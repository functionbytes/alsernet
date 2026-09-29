<?php

namespace Modules\Core\Services;

/**
 * HTTP Client Service
 *
 * Provides HTTP client functionality for fetching remote content safely
 * with SSL verification handling.
 */
class HttpClientService
{
    /**
     * Fetch content from a URL with SSL-safe handling
     *
     * Uses cURL to fetch content from a URL with TLS verification enabled.
     *
     * @param  string  $url  The URL to fetch from
     * @return string The response content
     *
     * @throws \Exception
     */
    public static function getContentSslSafe($url)
    {
        if (! preg_match('/^https{0,1}:\/\//', $url)) {
            throw new \Exception('url_get_contents_ssl_safe() requires a URL as input. Received: '.$url);
        }

        $circuit = new CircuitBreaker('http-client', 5, 60);

        if (! $circuit->isAvailable()) {
            throw new \Exception('HTTP client circuit breaker is open — external request blocked');
        }

        $client = curl_init();
        curl_setopt_array($client, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            // 29-sep-2026: sin uso conocido, pero se deja seguro por si alguien lo
            // reutiliza: verificación TLS activa, solo http/https, sin redirecciones.
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
        ]);

        $result = curl_exec($client);
        $error = curl_error($client);
        curl_close($client);

        if ($result === false) {
            $circuit->recordFailure();
            throw new \Exception('cURL error: '.$error);
        }

        $circuit->recordSuccess();

        return $result;
    }
}

// Global helper function for backward compatibility
if (! function_exists('url_get_contents_ssl_safe')) {
    /**
     * Fetch content from a URL with SSL-safe handling (Helper wrapper)
     *
     * @param  string  $url  The URL to fetch from
     * @return string The response content
     *
     * @throws \Exception
     */
    function url_get_contents_ssl_safe($url)
    {
        return HttpClientService::getContentSslSafe($url);
    }
}
