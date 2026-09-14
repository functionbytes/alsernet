<?php

namespace Modules\Document\Entities;

use Illuminate\Database\Eloquent\Model;

class DocumentConfiguration extends Model
{
    protected $table = 'document_configurations';

    protected $fillable = [
        'document_type',
        'document_type_label',
        'required_documents',
        'storage_disk',
        'storage_path',
        'storage_config',
        'is_storage_enabled',
    ];

    protected $casts = [
        'required_documents' => 'array',
        'storage_config' => 'array',
        'is_storage_enabled' => 'boolean',
    ];

    /**
     * Obtener configuración por tipo de documento
     */
    public static function getByType(string $documentType)
    {
        return self::where('document_type', $documentType)->first();
    }

    /**
     * Crear o actualizar configuración para un tipo
     */
    public static function createOrUpdate(string $documentType, array $requiredDocuments, ?string $label = null)
    {
        return self::updateOrCreate(
            ['document_type' => $documentType],
            [
                'required_documents' => $requiredDocuments,
                'document_type_label' => $label,
            ]
        );
    }

    /**
     * Get the storage disk name to use for this configuration
     * Return custom disk if enabled, otherwise returns default 'media'
     */
    public function getStorageDisk(): string
    {
        if ($this->is_storage_enabled && ! empty($this->storage_disk)) {
            return $this->storage_disk;
        }

        return config('filesystems.default', 'media');
    }

    /**
     * Get the storage path to use within the disk
     * Return custom path if configured, otherwise returns default
     */
    public function getStoragePath(): string
    {
        if ($this->is_storage_enabled && ! empty($this->storage_path)) {
            return $this->storage_path;
        }

        return 'documents';
    }

    /**
     * Check if custom storage is configured and enabled
     */
    public function hasCustomStorage(): bool
    {
        return $this->is_storage_enabled && ! empty($this->storage_disk);
    }

    /**
     * Get storage configuration summary for display
     */
    public function getStorageConfigSummary(): array
    {
        return [
            'enabled' => $this->is_storage_enabled,
            'disk' => $this->storage_disk ?? 'default',
            'path' => $this->storage_path ?? 'documents',
            'has_config' => ! empty($this->storage_config),
        ];
    }
}
