<?php

namespace App\Models\Return;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ReturnDocument extends Model
{
    protected $table = 'return_documents';

    protected $fillable = [
        'return_request_id',
        'document_type',
        'file_path',
        'file_name',
        'mime_type',
        'file_size',
        'metadata',
        'generated_at',
        'downloaded_at',
        'download_count'
    ];

    protected $casts = [
        'metadata' => 'array',
        'generated_at' => 'datetime',
        'downloaded_at' => 'datetime',
        'file_size' => 'integer',
        'download_count' => 'integer'
    ];

    // Tipos de documento
    const TYPE_SHIPPING_LABEL = 'shipping_label';
    const TYPE_RETURN_SLIP = 'return_slip';
    const TYPE_BARCODE_SHEET = 'barcode_sheet';
    const TYPE_CUSTOMER_RECEIPT = 'customer_receipt';
    const TYPE_CARRIER_MANIFEST = 'carrier_manifest';

    // Relaciones
    public function returnRequest(): BelongsTo
    {
        return $this->belongsTo(ReturnRequest::class, 'return_request_id');
    }

    // Scopes
    public function scopeByType($query, $type)
    {
        return $query->where('document_type', $type);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('generated_at', '>=', now()->subDays($days));
    }

    /**
     * Obtener nombre del tipo de documento
     */
    public function getTypeName(): string
    {
        $types = [
            self::TYPE_SHIPPING_LABEL => 'Etiqueta de Envío',
            self::TYPE_RETURN_SLIP => 'Albarán de Devolución',
            self::TYPE_BARCODE_SHEET => 'Hoja de Códigos de Barras',
            self::TYPE_CUSTOMER_RECEIPT => 'Recibo del Cliente',
            self::TYPE_CARRIER_MANIFEST => 'Manifiesto del Transportista'
        ];

        return $types[$this->document_type] ?? 'Documento';
    }

    /**
     * Obtener URL de descarga
     */
    public function getDownloadUrl(): string
    {
        return route('returns.documents.download', [
            'document' => $this->id,
            'token' => $this->generateDownloadToken()
        ]);
    }

    /**
     * Generar token temporal para descarga
     */
    private function generateDownloadToken(): string
    {
        return encrypt([
            'document_id' => $this->id,
            'expires_at' => now()->addHours(24)->timestamp
        ]);
    }

    /**
     * Verificar si el archivo existe
     */
    public function fileExists(): bool
    {
        return Storage::exists($this->file_path);
    }

    /**
     * Obtener contenido del archivo
     */
    public function getFileContent()
    {
        if (!$this->fileExists()) {
            throw new \Exception('El archivo no existe');
        }

        return Storage::get($this->file_path);
    }

    /**
     * Registrar descarga
     */
    public function recordDownload(): void
    {
        $this->increment('download_count');
        $this->update(['downloaded_at' => now()]);
    }

    /**
     * Obtener tamaño formateado
     */
    public function getFormattedSize(): string
    {
        $bytes = $this->file_size;

        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }

    /**
     * Eliminar documento y archivo
     */
    public function deleteWithFile(): bool
    {
        if ($this->fileExists()) {
            Storage::delete($this->file_path);
        }

        return $this->delete();
    }
}
