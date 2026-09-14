<?php

namespace Modules\Media\Drivers\Cloud;

use Illuminate\Support\Facades\Http;
use Modules\Media\Contracts\CloudImportDriver;

class OneDriveDriver implements CloudImportDriver
{
    public function __construct(
        private readonly string $clientId,
        private readonly string $clientSecret,
        private readonly string $redirectUri
    ) {}

    public function authenticate(string $userId): string
    {
        return 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize?'.http_build_query([
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'scope' => 'Files.Read offline_access',
            'state' => $userId,
        ]);
    }

    public function handleCallback(string $code, string $userId): array
    {
        $response = Http::asForm()->post('https://login.microsoftonline.com/common/oauth2/v2.0/token', [
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
        $endpoint = $folder
            ? "https://graph.microsoft.com/v1.0/me/drive/items/{$folder}/children"
            : 'https://graph.microsoft.com/v1.0/me/drive/root/children';

        $response = Http::withToken($accessToken)
            ->get($endpoint, ['$select' => 'id,name,size,file,folder']);

        $items = array_filter(
            $response->json('value', []),
            fn ($i) => isset($i['file'])
        );

        return array_values(array_map(fn ($i) => [
            'name' => $i['name'],
            'path' => $i['id'],
            'size' => (int) ($i['size'] ?? 0),
            'mime_type' => $i['file']['mimeType'] ?? 'application/octet-stream',
        ], $items));
    }

    public function downloadFile(string $accessToken, string $remotePath, string $localPath): bool
    {
        $response = Http::withToken($accessToken)
            ->sink($localPath)
            ->get("https://graph.microsoft.com/v1.0/me/drive/items/{$remotePath}/content");

        return $response->successful();
    }
}
