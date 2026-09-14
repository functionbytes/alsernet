<?php

namespace Modules\Document\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentStorageConfigurationHistory extends Model
{
    protected $table = 'document_storage_config_histories';

    protected $fillable = [
        'old_disk_name',
        'new_disk_name',
        'changed_by',
        'driver',
        'reason',
        'action',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function scopeForDisk($query, string $diskName)
    {
        return $query->where(function ($q) use ($diskName) {
            $q->where('old_disk_name', $diskName)
                ->orWhere('new_disk_name', $diskName);
        });
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('changed_by', $userId);
    }

    public function getActionLabelAttribute(): string
    {
        return match ($this->action) {
            'create' => 'Creada',
            'update' => 'Actualizada',
            'delete' => 'Eliminada',
            default => ucfirst($this->action),
        };
    }

    public function getChangeDescriptionAttribute(): string
    {
        if ($this->action === 'create') {
            return "Disco '{$this->new_disk_name}' configurado por {$this->user?->firstname}";
        }

        if ($this->action === 'delete') {
            return "Disco '{$this->old_disk_name}' eliminado";
        }

        return "Cambio de '{$this->old_disk_name}' a '{$this->new_disk_name}'";
    }
}
