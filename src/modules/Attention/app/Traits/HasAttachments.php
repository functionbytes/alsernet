<?php

namespace Modules\Attention\Traits;

use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Trait para gestión simplificada de archivos adjuntos
 * Usa Spatie MediaLibrary bajo el capó
 */
trait HasAttachments
{
    /**
     * Register media collections for the model
     */
    public function registerMediaCollections(): void
    {
        $config = config('attention.attachments', [
            'max_size' => 10 * 1024 * 1024,
            'allowed_mimes' => [
                'application/pdf',
                'image/jpeg',
                'image/png',
                'image/jpg',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ],
        ]);

        $this->addMediaCollection('documents')
            ->acceptsMimeTypes($config['allowed_mimes']);
    }

    /**
     * Get all document attachments
     */
    public function getDocuments()
    {
        return $this->getMedia('documents');
    }

    /**
     * Get documents as array with URLs and metadata
     */
    public function getDocumentsArray(): array
    {
        return $this->getMedia('documents')->map(function (Media $media) {
            return [
                'id' => $media->id,
                'uuid' => $media->uuid,
                'name' => $media->name,
                'file_name' => $media->file_name,
                'size' => $media->size,
                'human_size' => $media->human_readable_size,
                'mime_type' => $media->mime_type,
                'url' => $media->getUrl(),
                'extension' => $media->extension,
                'created_at' => $media->created_at->format('d/m/Y H:i'),
            ];
        })->toArray();
    }

    /**
     * Add a document attachment
     *
     * @param  UploadedFile|string  $file
     * @param  string|null  $name  Custom name for the file
     */
    public function addDocument($file, ?string $name = null): Media
    {
        $fileName = $name ?? (is_string($file) ? basename($file) : $file->getClientOriginalName());

        // Sanitizar nombre de archivo
        $sanitizedName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $fileName);

        return $this->addMedia($file)
            ->usingName($fileName)
            ->usingFileName($sanitizedName)
            ->toMediaCollection('documents');
    }

    /**
     * Remove a document attachment by ID
     */
    public function removeDocument(int $mediaId): bool
    {
        $media = $this->media()
            ->where('id', $mediaId)
            ->where('collection_name', 'documents')
            ->first();

        if ($media) {
            $media->delete();

            return true;
        }

        return false;
    }

    /**
     * Clear all document attachments
     */
    public function clearDocuments(): void
    {
        $this->clearMediaCollection('documents');
    }

    /**
     * Check if has any documents
     */
    public function hasDocuments(): bool
    {
        return $this->getMedia('documents')->isNotEmpty();
    }

    /**
     * Count documents
     */
    public function documentsCount(): int
    {
        return $this->getMedia('documents')->count();
    }

    /**
     * Get first document
     */
    public function getFirstDocument(): ?Media
    {
        return $this->getFirstMedia('documents');
    }

    /**
     * Get first document URL
     */
    public function getFirstDocumentUrl(): ?string
    {
        $media = $this->getFirstMedia('documents');

        return $media ? $media->getUrl() : null;
    }

    /**
     * Check if a specific document exists by ID
     */
    public function hasDocument(int $mediaId): bool
    {
        return $this->media()
            ->where('id', $mediaId)
            ->where('collection_name', 'documents')
            ->exists();
    }

    /**
     * Get document by ID
     */
    public function getDocument(int $mediaId): ?Media
    {
        return $this->media()
            ->where('id', $mediaId)
            ->where('collection_name', 'documents')
            ->first();
    }
}
