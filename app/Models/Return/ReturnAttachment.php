<?php

namespace App\Models\Return;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ReturnAttachment extends Model
{
    protected $table = 'return_attachments';
    protected $primaryKey = 'id_return_attachment';

    protected $fillable = [
        'id_return_request', 'filename', 'original_name', 'mime_type', 'file_size', 'uploaded_by'
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo('App\Models\Return\ReturnRequest', 'id_return_request', 'id_return_request');
    }

    public function scopeByReturn($query, $returnId)
    {
        return $query->where('id_return_request', $returnId);
    }

    public function scopeByType($query, $mimeType)
    {
        return $query->where('mime_type', 'like', $mimeType . '%');
    }

    public function scopeImages($query)
    {
        return $query->where('mime_type', 'like', 'image/%');
    }

    public function scopeDocuments($query)
    {
        return $query->whereIn('mime_type', [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ]);
    }

    /**
     * Obtener la ruta completa del archivo
     */
    public function getFilePath(): string
    {
        return storage_path('app/returns/attachments/' . $this->filename);
    }

    /**
     * Obtener la URL pública del archivo (si está en disco público)
     */
    public function getPublicUrl(): ?string
    {
        if (Storage::disk('public')->exists('returns/attachments/' . $this->filename)) {
            return Storage::disk('public')->url('returns/attachments/' . $this->filename);
        }
        return null;
    }

    /**
     * Verificar si el archivo existe físicamente
     */
    public function fileExists(): bool
    {
        return Storage::exists('returns/attachments/' . $this->filename);
    }

    /**
     * Obtener el tamaño del archivo formateado
     */
    public function getFormattedSize(): string
    {
        $bytes = $this->file_size;

        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }

    /**
     * Verificar si es una imagen
     */
    public function isImage(): bool
    {
        return strpos($this->mime_type, 'image/') === 0;
    }

    /**
     * Verificar si es un PDF
     */
    public function isPdf(): bool
    {
        return $this->mime_type === 'application/pdf';
    }

    /**
     * Verificar si es un documento
     */
    public function isDocument(): bool
    {
        $documentTypes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'text/plain'
        ];

        return in_array($this->mime_type, $documentTypes);
    }

    /**
     * Obtener el icono CSS apropiado según el tipo de archivo
     */
    public function getFileIcon(): string
    {
        if ($this->isImage()) {
            return 'ti ti-photo';
        } elseif ($this->isPdf()) {
            return 'ti ti-file-type-pdf';
        } elseif ($this->isDocument()) {
            return 'ti ti-file-text';
        } else {
            return 'ti ti-file';
        }
    }

    /**
     * Eliminar el archivo físico y el registro
     */
    public function deleteFile(): bool
    {
        $deleted = true;

        // Eliminar archivo físico si existe
        if ($this->fileExists()) {
            $deleted = Storage::delete('returns/attachments/' . $this->filename);
        }

        // Eliminar registro de la base de datos
        if ($deleted) {
            $this->delete();
        }

        return $deleted;
    }

    /**
     * Validar si el tipo de archivo está permitido
     */
    public static function isAllowedFileType($mimeType): bool
    {
        $allowedTypes = config('returns.validation.allowed_file_types', [
            'image/jpeg', 'image/png', 'image/gif',
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ]);

        return in_array($mimeType, $allowedTypes);
    }

    /**
     * Validar si el tamaño del archivo está dentro del límite
     */
    public static function isAllowedFileSize($fileSize): bool
    {
        $maxSize = config('returns.validation.max_file_size', 5120) * 1024; // KB a bytes
        return $fileSize <= $maxSize;
    }
}
