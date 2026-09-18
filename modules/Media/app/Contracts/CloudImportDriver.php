<?php

namespace Modules\Media\Contracts;

interface CloudImportDriver
{
    /**
     * Return the OAuth authorization URL for the given user.
     */
    public function authenticate(string $userId): string;

    /**
     * Exchange the OAuth code for access tokens.
     *
     * @return array<string, mixed>
     */
    public function handleCallback(string $code, string $userId): array;

    /**
     * List files in the given remote folder (root if null).
     *
     * @return array<array{name: string, path: string, size: int, mime_type: string}>
     */
    public function listFiles(string $accessToken, ?string $folder = null): array;

    /**
     * Download a remote file to a local path.
     */
    public function downloadFile(string $accessToken, string $remotePath, string $localPath): bool;
}
