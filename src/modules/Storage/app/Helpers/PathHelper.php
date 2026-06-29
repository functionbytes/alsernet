<?php

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
    function generatePublicPath(string $absPath, bool $withHost = false): string
    {
        if (empty(trim($absPath))) {
            throw new Exception('Empty path');
        }

        $excludeBase = storage_path();

        if (! str_starts_with($absPath, $excludeBase)) {
            throw new Exception(sprintf("File '%s' cannot be made public, only files under storage/ folder can", $absPath));
        }

        $relativePath = substr($absPath, strlen($excludeBase) + 1);

        if ($relativePath === false) {
            throw new Exception("Invalid path {$absPath}");
        }

        $dirname = dirname($relativePath);
        $basename = basename($relativePath);
        $encodedDirname = rtrim(strtr(base64_encode($dirname), '+/', '-_'), '=');

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
    function getAppSubdirectory(): ?string
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
    function getAppHost(): string
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
    function join_paths(mixed ...$paths): string
    {
        $segments = [];
        foreach ($paths as $path) {
            if ($path === null || $path === '') {
                continue;
            }
            if (preg_match('#https?://#i', $path)) {
                throw new Exception('Path contains a URL! Use `join_url` instead. Error for '.implode('/', $paths));
            }

            $segments[] = $path;
        }

        return preg_replace('#/+#', '/', implode('/', $segments));
    }
}
