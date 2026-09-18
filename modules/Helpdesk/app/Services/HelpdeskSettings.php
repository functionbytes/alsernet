<?php

namespace Modules\Helpdesk\Services;

use Modules\Helpdesk\Models\Setting;

/**
 * Typed access to Helpdesk settings.
 *
 * Settings are persisted by the panel in helpdesk_settings while a number of
 * older call sites still read config/helpdesk.php directly. Keeping the
 * fallback and normalisation here prevents the UI and runtime from drifting
 * apart again.
 */
class HelpdeskSettings
{
    public function get(string $settingKey, string $configKey, mixed $default = null): mixed
    {
        $stored = Setting::get($settingKey, null);

        return $stored !== null ? $stored : config($configKey, $default);
    }

    public function attachmentMaxKilobytes(): int
    {
        $configuredMb = (int) $this->get(
            'uploading.max_file_size_mb',
            'helpdesk.attachments.max_size',
            25 * 1024,
        );

        // helpdesk.attachments.max_size is already expressed in KB when the
        // DB setting is absent. A persisted uploading value is expressed in MB.
        $stored = Setting::get('uploading.max_file_size_mb', null);
        if ($stored === null) {
            return max(1, $configuredMb);
        }

        return max(1, (int) $stored * 1024);
    }

    /**
     * @return array<int, string>
     */
    public function attachmentExtensions(): array
    {
        $value = $this->get(
            'uploading.allowed_extensions',
            'helpdesk.attachments.allowed_extensions',
            [],
        );

        if (is_string($value)) {
            $value = preg_split('/[,\s]+/', $value) ?: [];
        }

        if (! is_array($value)) {
            return [];
        }

        $extensions = array_map(static fn (mixed $extension): string => strtolower(ltrim(trim((string) $extension), '.')), $value);
        $extensions = array_filter($extensions, static fn (string $extension): bool => (bool) preg_match('/^[a-z0-9][a-z0-9_-]{0,9}$/', $extension));

        return array_values(array_unique($extensions));
    }

    /**
     * MIME signatures corresponding to the currently allowed extensions. The
     * configured list is retained for backwards compatibility, while common
     * extensions added from the Settings screen get their real signatures too.
     *
     * @return array<int, string>
     */
    public function attachmentMimeTypes(): array
    {
        $map = [
            'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
            'gif' => 'image/gif', 'webp' => 'image/webp', 'svg' => 'image/svg+xml',
            'bmp' => 'image/bmp', 'pdf' => 'application/pdf', 'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'txt' => 'text/plain', 'csv' => 'text/csv', 'xml' => 'application/xml',
            'json' => 'application/json', 'html' => 'text/html', 'zip' => 'application/zip',
            'rar' => 'application/vnd.rar', '7z' => 'application/x-7z-compressed',
            'tar' => 'application/x-tar', 'gz' => 'application/gzip', 'mp3' => 'audio/mpeg',
            'mp4' => 'video/mp4', 'mov' => 'video/quicktime', 'avi' => 'video/x-msvideo',
            'mkv' => 'video/x-matroska', 'ogg' => 'audio/ogg', 'wav' => 'audio/wav',
            'webm' => 'video/webm', 'm4a' => 'audio/mp4',
        ];

        $mapped = array_values(array_filter(array_map(
            static fn (string $extension): ?string => $map[$extension] ?? null,
            $this->attachmentExtensions(),
        )));

        return array_values(array_unique(array_merge(
            (array) config('helpdesk.attachments.allowed_mime_types', []),
            $mapped,
        )));
    }

    public function virusScanEnabled(): bool
    {
        return $this->toBool($this->get(
            'uploading.enable_virus_scan',
            'helpdesk.attachments.virus_scan.enabled',
            false,
        ));
    }

    public function quarantineEnabled(): bool
    {
        return $this->toBool($this->get('uploading.enable_quarantine', 'helpdesk.attachments.quarantine.enabled', true));
    }

    public function imageCompressionEnabled(): bool
    {
        return $this->toBool($this->get('uploading.enable_image_compression', 'helpdesk.attachments.image_compression.enabled', true));
    }

    public function imageMaxWidth(): int
    {
        return max(100, (int) $this->get('uploading.image_max_width', 'helpdesk.attachments.image_compression.max_width', 1920));
    }

    public function imageMaxHeight(): int
    {
        return max(100, (int) $this->get('uploading.image_max_height', 'helpdesk.attachments.image_compression.max_height', 1080));
    }

    public function imageQuality(): int
    {
        return min(100, max(10, (int) $this->get('uploading.image_quality', 'helpdesk.attachments.image_compression.quality', 85)));
    }

    private function toBool(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
