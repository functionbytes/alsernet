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
     * Uses cURL to fetch content from a URL, allowing SSL verification to be disabled.
     * This is useful for development environments or when dealing with self-signed certificates.
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

        $client = curl_init();
        curl_setopt_array($client, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $result = curl_exec($client);
        curl_close($client);

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
        return \Modules\Core\Services\HttpClientService::getContentSslSafe($url);
    }
}
