<?php

use app\Library\StringHelper;

/**
 * Storage Path and URL Helpers
 *
 * Provides functions for handling file paths, public URLs,
 * and application host/subdirectory detection.
 */
if (! function_exists('generatePublicPath')) {
    /**
     * Generate a public URL for a file stored in the storage directory
     *
     * @param  string  $absPath  The absolute path to the file
     * @param  bool  $withHost  Whether to include the full host URL
     * @return string The public URL for the file
     *
     * @throws Exception
     */
    function generatePublicPath($absPath, $withHost = false)
    {
        if (empty(trim($absPath))) {
            throw new Exception('Empty path');
        }

        $excludeBase = storage_path();
        $pos = strpos($absPath, $excludeBase);

        if ($pos === false) {
            throw new Exception(sprintf("File '%s' cannot be made public, only files under storage/ folder can", $absPath));
        }

        if ($pos != 0) {
            throw new Exception(sprintf("Invalid path '%s', cannot make it public", $absPath));
        }

        $relativePath = substr($absPath, strlen($excludeBase) + 1);

        if ($relativePath === false) {
            throw new Exception("Invalid path {$absPath}");
        }

        $dirname = dirname($relativePath);
        $basename = basename($relativePath);
        $encodedDirname = StringHelper::base64UrlEncode($dirname);

        $subdirectory = getAppSubdirectory();

        if (empty($subdirectory) || $withHost) {
            $url = route('public_assets', ['dirname' => $encodedDirname, 'basename' => rawurlencode($basename)], $withHost);
        } else {
            $subdirectory = join_paths('/', $subdirectory);
            $url = join_paths($subdirectory, route('public_assets', ['dirname' => $encodedDirname, 'basename' => $basename], $withHost));
        }

        return $url;
    }
}

if (! function_exists('getAppSubdirectory')) {
    /**
     * Get the subdirectory where the application is installed
     *
     * For example, if the app is at example.com/myapp, returns 'myapp'
     *
     * @return string|null The subdirectory or null if in root
     */
    function getAppSubdirectory()
    {
        $path = parse_url(config('app.url'), PHP_URL_PATH);

        if (is_null($path)) {
            return null;
        }

        $path = trim($path, '/');

        return empty($path) ? null : $path;
    }
}

if (! function_exists('getAppHost')) {
    /**
     * Get the full host URL of the application (scheme + host + port)
     *
     * For example: https://example.com or https://example.com:8080
     *
     * @return string The application host URL
     *
     * @throws Exception
     */
    function getAppHost()
    {
        $fullUrl = config('app.url');
        $meta = parse_url($fullUrl);

        if (! array_key_exists('scheme', $meta) || ! array_key_exists('host', $meta)) {
            throw new Exception('Invalid app.url setting');
        }

        $appHost = "{$meta['scheme']}://{$meta['host']}";

        if (array_key_exists('port', $meta)) {
            $appHost = "{$appHost}:{$meta['port']}";
        }

        return $appHost;
    }
}

if (! function_exists('join_paths')) {
    /**
     * Join path segments safely, ensuring no duplicate slashes
     *
     * Prevents mixing HTTP URLs with local paths.
     *
     * @return string The joined path
     *
     * @throws Exception
     */
    function join_paths()
    {
        $paths = [];
        foreach (func_get_args() as $arg) {
            if (is_null($arg)) {
                continue;
            }
            if (preg_match('/http:\/\//i', $arg)) {
                throw new \Exception('Path contains http://! Use `join_url` instead. Error for '.implode('/', func_get_args()));
            }

            if ($arg !== '') {
                $paths[] = $arg;
            }
        }

        return preg_replace('#/+#', '/', implode('/', $paths));
    }
}
