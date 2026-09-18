<?php

namespace Modules\Media\Drivers\Cloud;

use Illuminate\Support\Facades\Http;
use Modules\Media\Contracts\CloudImportDriver;

class DropboxDriver implements CloudImportDriver
{
    public function __construct(
        private readonly string $clientId,
        private readonly string $clientSecret,
        private readonly string $redirectUri
    ) {}

    public function authenticate(string $userId): string
    {
        return 'https://www.dropbox.com/oauth2/authorize?'.http_build_query([
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'state' => $userId,
        ]);
    }

    public function handleCallback(string $code, string $userId): array
    {
        $response = Http::asForm()->post('https://api.dropboxapi.com/oauth2/token', [
            'code' => $code,
            'grant_type' => 'authorization_code',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $this->redirectUri,
        ]);

        return $response->json() ?? [];
    }

    public function listFiles(string $accessToken, ?string $folder = null): array
    {
        $response = Http::withToken($accessToken)
            ->post('https://api.dropboxapi.com/2/files/list_folder', [
                'path' => $folder ?? '',
                'recursive' => false,
            ]);

        $entries = $response->json('entries', []);
        $files = array_filter($entries, fn ($e) => ($e['.tag'] ?? '') === 'file');

        return array_values(array_map(fn ($e) => [
            'name' => $e['name'],
            'path' => $e['path_lower'],
            'size' => $e['size'] ?? 0,
            'mime_type' => mime_content_type($e['path_lower']) ?: 'application/octet-stream',
        ], $files));
    }

    public function downloadFile(string $accessToken, string $remotePath, string $localPath): bool
    {
        $response = Http::withToken($accessToken)
            ->withHeaders([
                'Dropbox-API-Arg' => json_encode(['path' => $remotePath]),
            ])
            ->sink($localPath)
            ->post('https://content.dropboxapi.com/2/files/download');

        return $response->successful();
    }
}
