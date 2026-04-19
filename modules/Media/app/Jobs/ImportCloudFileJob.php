<?php

namespace Modules\Media\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Modules\Media\Models\MediaSetting;
use Modules\Media\Services\CloudImportFactory;
use Modules\Media\Services\MediaFileService;

class ImportCloudFileJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 300;

    public int $backoff = 30;

    public function __construct(
        private readonly string $provider,
        private readonly string $remotePath,
        private readonly int $userId,
        private readonly ?int $folderId
    ) {
        $this->onQueue('media-heavy');
    }

    public function handle(MediaFileService $service): void
    {
        $driver = CloudImportFactory::make($this->provider);

        if (! $driver) {
            return;
        }

        $setting = MediaSetting::query()
            ->where('user_id', $this->userId)
            ->where('key', 'cloud_token_'.$this->provider)
            ->first();

        if (! $setting) {
            return;
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'cloud_imp_');

        try {
            $success = $driver->downloadFile(
                (string) ($setting->value['access_token'] ?? ''),
                $this->remotePath,
                $tempPath
            );

            if (! $success) {
                return;
            }

            $filename = basename($this->remotePath);
            $uploadedFile = new UploadedFile($tempPath, $filename, null, null, true);

            $service->upload($uploadedFile, $this->folderId, 'media', $this->userId);
        } finally {
            @unlink($tempPath);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ImportCloudFileJob failed', [
            'provider' => $this->provider,
            'remote_path' => $this->remotePath,
            'user_id' => $this->userId,
            'error' => $exception->getMessage(),
        ]);
    }
}
