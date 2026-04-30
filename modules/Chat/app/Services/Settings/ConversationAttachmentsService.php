<?php

namespace Modules\Chat\Services\Settings;

use Spatie\Activitylog\Models\Activity;

/**
 * Handles business logic for the conversation-attachments settings section:
 * disk metadata listing, per-disk storage stats, and the audit history log.
 */
class ConversationAttachmentsService
{
    private const EXCLUDED_DISKS = ['local', 'public', 'ftp', 'sftp', 'network_shared'];

    /**
     * Build display metadata for every configured filesystem disk,
     * excluding internal/system disks.
     *
     * @return array<string, array{driver: string, root: string, url: string, label: string, description: string}>
     */
    public function buildDisksMeta(): array
    {
        $disksConfig = config('filesystems.disks', []);
        $meta = [];

        foreach ($disksConfig as $name => $cfg) {
            if (in_array($name, self::EXCLUDED_DISKS, strict: true)) {
                continue;
            }

            $driver = $cfg['driver'] ?? 'unknown';
            $root = $cfg['root'] ?? 'N/A';
            $url = $cfg['url'] ?? 'N/A';

            $meta[$name] = [
                'driver' => $driver,
                'root' => $root,
                'url' => $url,
                'label' => ucfirst($name).' ('.$driver.')',
                'description' => match ($driver) {
                    'local' => 'Almacenamiento local en: '.$root,
                    's3' => 'Amazon S3: '.($cfg['bucket'] ?? 'No configurado'),
                    default => 'Driver: '.$driver,
                },
            ];
        }

        return $meta;
    }

    /**
     * Return disk space statistics for a local-driver disk.
     * Returns null values for non-local or unreachable disks.
     *
     * @return array{total: string, used: string, free: string, percent: float|null}
     */
    public function diskStats(string $disk): array
    {
        $cfg = config('filesystems.disks.'.$disk);
        $empty = ['total' => 'N/A', 'used' => 'N/A', 'free' => 'N/A', 'percent' => null];

        if (! $cfg || ($cfg['driver'] ?? '') !== 'local') {
            return $empty;
        }

        $root = $cfg['root'] ?? null;

        if (! $root || ! is_dir($root)) {
            return $empty;
        }

        $total = disk_total_space($root);
        $free = disk_free_space($root);
        $used = $total - $free;

        return [
            'total' => $this->formatBytes($total),
            'used' => $this->formatBytes($used),
            'free' => $this->formatBytes($free),
            'percent' => round(($used / $total) * 100, 2),
        ];
    }

    /**
     * Fetch the 10 most recent attachment-settings change records.
     *
     * @return array<int, array{user: string, old_disk: string|null, new_disk: string|null, created_at_relative: string}>
     */
    public function recentHistory(): array
    {
        return Activity::query()
            ->where('description', 'Configuración de adjuntos actualizada')
            ->latest()
            ->limit(10)
            ->get()
            ->map(function (Activity $log): array {
                $causer = $log->causer;

                return [
                    'user' => $causer ? ($causer->name ?? $causer->email) : 'Sistema',
                    'old_disk' => $log->properties['old_disk'] ?? null,
                    'new_disk' => $log->properties['new_disk'] ?? null,
                    'created_at_relative' => $log->created_at->diffForHumans(),
                ];
            })
            ->all();
    }

    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = (int) floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision).' '.$units[$pow];
    }
}
