<?php

namespace Modules\Backup\Models;

use Illuminate\Database\Eloquent\Model;

class SupervisorBackup extends Model
{
    protected $fillable = [
        'name',
        'description',
        'environment',
        'config_files',
        'supervisor_status',
        'backup_size',
        'backed_up_at',
        'restored_at',
        'restored_by',
        'is_auto',
    ];

    protected function casts(): array
    {
        return [
            'config_files' => 'json',
            'supervisor_status' => 'json',
            'backed_up_at' => 'datetime',
            'restored_at' => 'datetime',
            'is_auto' => 'boolean',
        ];
    }

    public function scopeEnvironment($query, string $environment): mixed
    {
        return $query->where('environment', $environment);
    }

    public function scopeManual($query): mixed
    {
        return $query->where('is_auto', false);
    }

    public function scopeAuto($query): mixed
    {
        return $query->where('is_auto', true);
    }

    public function scopeRecent($query, int $days = 30): mixed
    {
        return $query->where('backed_up_at', '>=', now()->subDays($days))
            ->orderBy('backed_up_at', 'desc');
    }

    public function getFormattedSizeAttribute(): string
    {
        if (! $this->backup_size) {
            return 'N/A';
        }

        $bytes = max((int) $this->backup_size, 0);
        $units = ['B', 'KB', 'MB', 'GB'];
        $pow = $bytes ? (int) floor(log($bytes) / log(1024)) : 0;
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2).' '.$units[$pow];
    }

    public function getRelativeTimeAttribute(): string
    {
        return $this->backed_up_at?->diffForHumans() ?? 'Never';
    }
}
