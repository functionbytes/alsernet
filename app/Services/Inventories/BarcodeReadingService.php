<?php

namespace App\Services\Inventories;

use App\Models\Product\Product;
use App\Models\Warehouse\WarehouseInventoryMovement;
use Illuminate\Support\Facades\Log;

class BarcodeReadingService
{
    /**
     * Validar si un código de barras existe en la base de datos
     *
     * @param string $barcode
     * @return bool
     */
    public function exists(string $barcode): bool
    {
        return Product::barcodeExits($barcode);
    }

    /**
     * Obtener un producto por código de barras
     *
     * @param string $barcode
     * @return Product|null
     */
    public function getProduct(string $barcode): ?Product
    {
        return Product::barcode($barcode);
    }

    /**
     * Validar formato de código de barras
     *
     * @param string $barcode
     * @return bool
     */
    public function isValidFormat(string $barcode): bool
    {
        // Validar que sea numérico y tenga longitud razonable (8-13 dígitos típico EAN)
        if (!is_numeric($barcode)) {
            return false;
        }

        $length = strlen($barcode);
        return $length >= 8 && $length <= 13;
    }

    /**
     * Procesar código de barras (validar formato + existencia)
     *
     * @param string $barcode
     * @return array
     */
    public function validate(string $barcode): array
    {
        $barcode = trim($barcode);

        // Validar formato
        if (!$this->isValidFormat($barcode)) {
            return [
                'success' => false,
                'message' => 'Formato de código de barras inválido',
                'code' => 'invalid_format',
                'barcode' => $barcode,
            ];
        }

        // Buscar en base de datos
        if (!$this->exists($barcode)) {
            return [
                'success' => false,
                'message' => 'Código de barras no encontrado en el sistema',
                'code' => 'not_found',
                'barcode' => $barcode,
            ];
        }

        // Obtener producto
        $product = $this->getProduct($barcode);

        if (!$product) {
            return [
                'success' => false,
                'message' => 'Error al obtener datos del producto',
                'code' => 'fetch_error',
                'barcode' => $barcode,
            ];
        }

        // Validar que el producto esté activo
        if (!$product->available) {
            return [
                'success' => false,
                'message' => 'El producto no está disponible',
                'code' => 'product_inactive',
                'barcode' => $barcode,
                'product' => $product,
            ];
        }

        // Log de lectura exitosa
        $this->logReading($barcode, $product, true, null);

        return [
            'success' => true,
            'message' => 'Código de barras válido',
            'barcode' => $barcode,
            'product' => [
                'id' => $product->id,
                'uid' => $product->uid,
                'title' => $product->title,
                'reference' => $product->reference,
                'barcode' => $product->barcode,
                'available' => $product->available,
            ],
        ];
    }

    /**
     * Decodificar código de barras (extrae información si es posible)
     *
     * @param string $barcode
     * @return array
     */
    public function decode(string $barcode): array
    {
        // Aquí se puede agregar lógica para decodificar códigos específicos
        // Por ahora retorna información básica

        return [
            'raw' => $barcode,
            'length' => strlen($barcode),
            'is_numeric' => is_numeric($barcode),
            'type' => $this->detectBarcodeType($barcode),
        ];
    }

    /**
     * Detectar tipo de código de barras
     *
     * @param string $barcode
     * @return string
     */
    public function detectBarcodeType(string $barcode): string
    {
        $length = strlen($barcode);

        if ($length === 8) {
            return 'EAN-8';
        } elseif ($length === 12) {
            return 'UPC-A';
        } elseif ($length === 13) {
            return 'EAN-13';
        } elseif ($length === 14) {
            return 'GTIN-14';
        } elseif (!is_numeric($barcode)) {
            return 'CODE-128';
        }

        return 'Unknown';
    }

    /**
     * Registrar lectura de código de barras para auditoría
     *
     * @param string $barcode
     * @param Product|null $product
     * @param bool $success
     * @param string|null $errorReason
     * @return void
     */
    public function logReading(
        string $barcode,
        ?Product $product = null,
        bool $success = false,
        ?string $errorReason = null
    ): void {
        try {
            Log::channel('barcode')->info('Barcode reading', [
                'barcode' => $barcode,
                'product_id' => $product?->id,
                'product_reference' => $product?->reference,
                'success' => $success,
                'error_reason' => $errorReason,
                'user_id' => auth()->id(),
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'timestamp' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error logging barcode reading', [
                'error' => $e->getMessage(),
                'barcode' => $barcode,
            ]);
        }
    }

    /**
     * Obtener estadísticas de lectura en un período
     *
     * @param int $days
     * @return array
     */
    public function getReadingStats(int $days = 30): array
    {
        $movements = WarehouseInventoryMovement::recent($days)
            ->where('movement_type', WarehouseInventoryMovement::TYPE_ADD)
            ->count();

        return [
            'period_days' => $days,
            'total_readings' => $movements,
            'average_per_day' => round($movements / $days, 2),
        ];
    }

    /**
     * Procesar múltiples códigos de barras (batch)
     *
     * @param array $barcodes
     * @return array
     */
    public function validateBatch(array $barcodes): array
    {
        $results = [];
        $successful = [];
        $failed = [];

        foreach ($barcodes as $barcode) {
            $result = $this->validate($barcode);
            $results[] = $result;

            if ($result['success']) {
                $successful[] = $result;
            } else {
                $failed[] = $result;
            }
        }

        return [
            'total' => count($barcodes),
            'successful' => count($successful),
            'failed' => count($failed),
            'success_rate' => count($barcodes) > 0
                ? round((count($successful) / count($barcodes)) * 100, 2)
                : 0,
            'results' => $results,
        ];
    }
}
