<?php

namespace Modules\Media\Drivers\Cloud;

use Illuminate\Support\Facades\Http;
use Modules\Media\Contracts\CloudImportDriver;

class GoogleDriveDriver implements CloudImportDriver
{
    public function __construct(
        private readonly string $clientId,
        private readonly string $clientSecret,
        private readonly string $redirectUri
    ) {}

    public function authenticate(string $userId): string
    {
        return 'https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query([
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'scope' => 'https://www.googleapis.com/auth/drive.readonly',
            'access_type' => 'offline',
            'state' => $userId,
        ]);
    }

    public function handleCallback(string $code, string $userId): array
    {
        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
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
        $query = $folder
            ? "'{$folder}' in parents and trashed = false"
            : "'root' in parents and trashed = false";

        $response = Http::withToken($accessToken)
            ->get('https://www.googleapis.com/drive/v3/files', [
                'q' => $query,
                'fields' => 'files(id,name,size,mimeType)',
                'pageSize' => 100,
            ]);

        $files = array_filter(
            $response->json('files', []),
            fn ($f) => ($f['mimeType'] ?? '') !== 'application/vnd.google-apps.folder'
        );

        return array_values(array_map(fn ($f) => [
            'name' => $f['name'],
            'path' => $f['id'],
            'size' => (int) ($f['size'] ?? 0),
            'mime_type' => $f['mimeType'] ?? 'application/octet-stream',
        ], $files));
    }

    public function downloadFile(string $accessToken, string $remotePath, string $localPath): bool
    {
        $response = Http::withToken($accessToken)
            ->sink($localPath)
            ->get("https://www.googleapis.com/drive/v3/files/{$remotePath}", [
                'alt' => 'media',
            ]);

        return $response->successful();
    }
}
