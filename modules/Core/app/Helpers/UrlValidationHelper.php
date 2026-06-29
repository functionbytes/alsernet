<?php

/**
 * URL Validation Helpers
 *
 * Provides utility functions for validating and checking URLs.
 */
if (! function_exists('is_non_web_link')) {
    /**
     * Check if a URL is a non-web protocol link
     *
     * Returns true for links like mailto:, tel:, #, etc.
     *
     * @param  string  $url  The URL to check
     * @return bool True if the URL is a non-web link
     */
    function is_non_web_link($url)
    {
        $preserved = ['#', 'mailto:', 'tel:', 'file:', 'ftp:', 'rss:', 'feed:', ':telnet', 'gopher:', 'ssh:', 'nntp:'];

        $matched = false;
        foreach ($preserved as $prefix) {
            if (strpos($url, $prefix) === 0) {
                $matched = true;
                break;
            }
        }

        return $matched;
    }
}
