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

    protected $casts = [
        'config_files' => 'json',
        'supervisor_status' => 'json',
        'backed_up_at' => 'datetime',
        'restored_at' => 'datetime',
        'is_auto' => 'boolean',
    ];

    /**
     * Scope para obtener backups de un ambiente específico
     */
    public function scopeEnvironment($query, $environment)
    {
        return $query->where('environment', $environment);
    }

    /**
     * Scope para obtener backups manuales
     */
    public function scopeManual($query)
    {
        return $query->where('is_auto', false);
    }

    /**
     * Scope para obtener backups automáticos
     */
    public function scopeAuto($query)
    {
        return $query->where('is_auto', true);
    }

    /**
     * Obtener backups recientes
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('backed_up_at', '>=', now()->subDays($days))
            ->orderBy('backed_up_at', 'desc');
    }

    /**
     * Formato legible del tamaño del backup
     */
    public function getFormattedSizeAttribute()
    {
        if (! $this->backup_size) {
            return 'N/A';
        }

        $bytes = $this->backup_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2).' '.$units[$pow];
    }

    /**
     * Obtener tiempo relativo del backup
     */
    public function getRelativeTimeAttribute()
    {
        return $this->backed_up_at?->diffForHumans() ?? 'Never';
    }
}
