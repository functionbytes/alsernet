<?php

namespace App\Models\Return;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Picqer\Barcode\BarcodeGeneratorPNG;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;

class ReturnBarcode extends Model
{
    protected $table = 'return_barcodes';

    protected $fillable = [
        'return_request_id',
        'return_product_id',
        'barcode_number',
        'barcode_type',
        'barcode_image_path',

        'status',
        'generated_at',
        'printed_at',
        'scanned_at',
        'scanned_by',
        'validation_notes'
    ];

    protected $casts = [
        'generated_at' => 'datetime',
        'printed_at' => 'datetime',
        'scanned_at' => 'datetime',
    ];

    // Constantes de estado
    const STATUS_GENERATED = 'generated';
    const STATUS_PRINTED = 'printed';
    const STATUS_SCANNED = 'scanned';
    const STATUS_VALIDATED = 'validated';
    const STATUS_REJECTED = 'rejected';

    // Tipos de código de barras
    const TYPE_CODE128 = 'CODE128';
    const TYPE_QR = 'QR';
    const TYPE_EAN13 = 'EAN13';

    // Relaciones
    public function returnRequest(): BelongsTo
    {
        return $this->belongsTo(ReturnRequest::class, 'return_request_id');
    }

    public function returnProduct(): BelongsTo
    {
        return $this->belongsTo(ReturnRequestProduct::class, 'return_product_id');
    }

    public function scanner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scanned_by');
    }

    // Scopes
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', [self::STATUS_GENERATED, self::STATUS_PRINTED]);
    }

    public function scopeProcessed($query)
    {
        return $query->whereIn('status', [self::STATUS_SCANNED, self::STATUS_VALIDATED]);
    }

    /**
     * Generar número único de código de barras
     */
    public static function generateBarcodeNumber($returnId, $productId): string
    {
        // Formato: RET-[RETURN_ID]-[PRODUCT_ID]-[RANDOM]-[CHECK]
        $prefix = 'RET';
        $returnPart = str_pad($returnId, 6, '0', STR_PAD_LEFT);
        $productPart = str_pad($productId, 4, '0', STR_PAD_LEFT);
        $randomPart = strtoupper(Str::random(4));

        $base = "{$prefix}{$returnPart}{$productPart}{$randomPart}";
        $checksum = self::calculateChecksum($base);

        return "{$base}{$checksum}";
    }

    /**
     * Calcular dígito de verificación
     */
    private static function calculateChecksum($code): string
    {
        $sum = 0;
        for ($i = 0; $i < strlen($code); $i++) {
            $sum += ord($code[$i]) * ($i + 1);
        }
        return strtoupper(dechex($sum % 256));
    }

    /**
     * Crear código de barras para un producto de devolución
     */
    public static function createForReturnProduct($returnRequestId, $returnProductId, $type = self::TYPE_CODE128)
    {
        $barcodeNumber = self::generateBarcodeNumber($returnRequestId, $returnProductId);

        $barcode = self::create([
            'return_request_id' => $returnRequestId,
            'return_product_id' => $returnProductId,
            'barcode_number' => $barcodeNumber,
            'barcode_type' => $type,
            'status' => self::STATUS_GENERATED,
            'generated_at' => now()
        ]);

        // Generar imagen del código
        $barcode->generateImage();

        return $barcode;
    }

    /**
     * Generar imagen del código de barras
     */
    public function generateImage(): bool
    {
        try {
            $path = "returns/barcodes/{$this->return_request_id}";
            Storage::makeDirectory($path);

            if ($this->barcode_type === self::TYPE_QR) {
                // Generar código QR
                $qrCode = QrCode::format('png')
                    ->size(300)
                    ->margin(10)
                    ->generate($this->barcode_number);

                $filename = "{$this->barcode_number}_qr.png";
                Storage::put("{$path}/{$filename}", $qrCode);
            } else {
                // Generar código de barras tradicional
                $generator = new BarcodeGeneratorPNG();
                $barcodeImage = $generator->getBarcode(
                    $this->barcode_number,
                    $generator::TYPE_CODE_128,
                    3, // Ancho
                    100 // Alto
                );

                $filename = "{$this->barcode_number}.png";
                Storage::put("{$path}/{$filename}", $barcodeImage);
            }

            $this->update(['barcode_image_path' => "{$path}/{$filename}"]);
            return true;

        } catch (\Exception $e) {
            \Log::error('Error generating barcode image', [
                'barcode_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Obtener URL de la imagen
     */
    public function getImageUrl(): ?string
    {
        if (!$this->barcode_image_path) {
            return null;
        }

        return Storage::url($this->barcode_image_path);
    }

    /**
     * Marcar como impreso
     */
    public function markAsPrinted(): bool
    {
        return $this->update([
            'status' => self::STATUS_PRINTED,
            'printed_at' => now()
        ]);
    }

    /**
     * Escanear código de barras
     */
    public function scan($userId, $notes = null): bool
    {
        return $this->update([
            'status' => self::STATUS_SCANNED,
            'scanned_at' => now(),
            'scanned_by' => $userId,
            'validation_notes' => $notes
        ]);
    }

    /**
     * Validar código de barras
     */
    public function validate($notes = null): bool
    {
        // Verificar que el producto corresponde
        if (!$this->returnProduct || !$this->returnRequest) {
            $this->update([
                'status' => self::STATUS_REJECTED,
                'validation_notes' => 'Producto o solicitud no encontrada'
            ]);
            return false;
        }

        // Verificar checksum
        $baseCode = substr($this->barcode_number, 0, -2);
        $checksum = substr($this->barcode_number, -2);

        if (self::calculateChecksum($baseCode) !== $checksum) {
            $this->update([
                'status' => self::STATUS_REJECTED,
                'validation_notes' => 'Checksum inválido'
            ]);
            return false;
        }

        // Marcar como validado
        $this->update([
            'status' => self::STATUS_VALIDATED,
            'validation_notes' => $notes ?? 'Validación exitosa'
        ]);

        // Actualizar el producto como recibido
        $this->returnProduct->update([
            'is_received' => true,
            'received_at' => now()
        ]);

        return true;
    }

    /**
     * Verificar si ya fue escaneado
     */
    public function isScanned(): bool
    {
        return in_array($this->status, [self::STATUS_SCANNED, self::STATUS_VALIDATED]);
    }

    /**
     * Obtener información para etiqueta
     */
    public function getLabelInfo(): array
    {
        return [
            'barcode_number' => $this->barcode_number,
            'return_number' => $this->returnRequest->getReturnNumber(),
            'product_name' => $this->returnProduct->product_name,
            'product_code' => $this->returnProduct->product_code,
            'quantity' => $this->returnProduct->quantity,
            'customer_name' => $this->returnRequest->customer_name,
            'order_number' => $this->returnRequest->order->order_number,
            'generated_date' => $this->generated_at->format('d/m/Y H:i')
        ];
    }
}
