<?php

namespace Modules\Chat\Services;

use Illuminate\Support\Facades\DB;

class AttachmentConfigService
{
    /** @var array<string, mixed>|null */
    private ?array $config = null;

    /**
     * Get the attachment configuration from settings.
     *
     * @return array{enabled: bool, maxFileSizeMb: int, maxAttachments: int, allowedTypes: string[]}
     */
    public function getConfig(): array
    {
        if ($this->config !== null) {
            return $this->config;
        }

        $raw = DB::table('settings')
            ->where('key', 'chat.conversation_attachments')
            ->value('value');

        $stored = $raw ? json_decode($raw, true) : [];

        $this->config = [
            'enabled' => (bool) ($stored['enabled'] ?? true),
            'maxFileSizeMb' => (int) ($stored['max_file_size_mb'] ?? 10),
            'maxAttachments' => (int) ($stored['max_attachments'] ?? 5),
            'allowedTypes' => $stored['allowed_types'] ?? [
                'image/jpeg',
                'image/png',
                'image/gif',
                'image/webp',
                'application/pdf',
            ],
        ];

        return $this->config;
    }

    /**
     * Get the MIME type to icon/color mapping.
     *
     * @return array<string, array{0: string, 1: string}>
     */
    public function getMimeIconMap(): array
    {
        return [
            'application/pdf' => ['fas fa-file-pdf',     '#dc3545'],
            'application/msword' => ['fas fa-file-word',    '#0d6efd'],
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => ['fas fa-file-word',    '#0d6efd'],
            'application/vnd.ms-excel' => ['fas fa-file-excel',   '#198754'],
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => ['fas fa-file-excel',   '#198754'],
            'text/plain' => ['fas fa-file-alt',     '#6c757d'],
            'text/csv' => ['fas fa-file-csv',     '#198754'],
            'application/zip' => ['fas fa-file-archive', '#fd7e14'],
            'application/x-rar-compressed' => ['fas fa-file-archive', '#fd7e14'],
            'audio/mpeg' => ['fas fa-file-audio',   '#6f42c1'],
            'audio/ogg' => ['fas fa-file-audio',   '#6f42c1'],
            'video/mp4' => ['fas fa-file-video',   '#0dcaf0'],
            'video/webm' => ['fas fa-file-video',   '#0dcaf0'],
        ];
    }

    /**
     * Get the MIME type to file extension mapping for <input accept>.
     *
     * @return array<string, string>
     */
    public function getMimeToFileExtension(): array
    {
        return [
            'image/jpeg' => '.jpg,.jpeg',
            'image/png' => '.png',
            'image/gif' => '.gif',
            'image/webp' => '.webp',
            'application/pdf' => '.pdf',
            'application/msword' => '.doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => '.docx',
            'application/vnd.ms-excel' => '.xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => '.xlsx',
            'text/plain' => '.txt',
            'text/csv' => '.csv',
            'application/zip' => '.zip',
            'audio/mpeg' => '.mp3',
            'video/mp4' => '.mp4',
        ];
    }

    /**
     * Build the accept attribute string for <input accept="...">.
     */
    public function getAcceptAttribute(): string
    {
        $extensionMap = $this->getMimeToFileExtension();
        $allowedTypes = $this->getConfig()['allowedTypes'];

        return implode(',', array_map(
            fn (string $mime) => $extensionMap[$mime] ?? $mime,
            $allowedTypes
        ));
    }
}
