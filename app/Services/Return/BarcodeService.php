<?php

namespace App\Services\Return;

use App\Models\Return\ReturnBarcode;
use App\Models\Return\ReturnRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class BarcodeService
{
    /**
     * Generar códigos de barras para todos los productos de una devolución
     */
    public function generateForReturn(ReturnRequest $returnRequest): Collection
    {
        $barcodes = collect();

        foreach ($returnRequest->products as $product) {
            // Verificar si ya existe un código para este producto
            $existingBarcode = ReturnBarcode::where('return_request_id', $returnRequest->id)
                ->where('return_product_id', $product->id)
                ->first();

            if (!$existingBarcode) {
                $barcode = ReturnBarcode::createForReturnProduct(
                    $returnRequest->id,
                    $product->id
                );
                $barcodes->push($barcode);
            } else {
                $barcodes->push($existingBarcode);
            }
        }

        return $barcodes;
    }

    /**
     * Generar PDF con todas las etiquetas
     */
    public function generateLabelsPDF(ReturnRequest $returnRequest): string
    {
        $barcodes = $this->generateForReturn($returnRequest);

        $data = [
            'return' => $returnRequest,
            'barcodes' => $barcodes,
            'customer' => $returnRequest->customer,
            'order' => $returnRequest->order
        ];

        $pdf = PDF::loadView('returns.labels.barcode-sheet', $data);
        $pdf->setPaper('A4', 'portrait');

        // Guardar PDF
        $filename = "return_{$returnRequest->id}_labels_" . time() . ".pdf";
        $path = "returns/labels/{$returnRequest->id}";
        Storage::makeDirectory($path);

        $fullPath = "{$path}/{$filename}";
        Storage::put($fullPath, $pdf->output());

        // Registrar documento
        \App\Models\Return\ReturnDocument::create([
            'return_request_id' => $returnRequest->id,
            'document_type' => 'barcode_sheet',
            'file_path' => $fullPath,
            'file_name' => $filename,
            'file_size' => Storage::size($fullPath),
            'generated_at' => now()
        ]);

        return $fullPath;
    }

    /**
     * Escanear código de barras
     */
    public function scanBarcode(string $barcodeNumber, $userId): array
    {
        $barcode = ReturnBarcode::where('barcode_number', $barcodeNumber)->first();

        if (!$barcode) {
            return [
                'success' => false,
                'message' => 'Código de barras no encontrado',
                'code' => 'NOT_FOUND'
            ];
        }

        if ($barcode->isScanned()) {
            return [
                'success' => false,
                'message' => 'Este código ya fue escaneado',
                'code' => 'ALREADY_SCANNED',
                'scanned_at' => $barcode->scanned_at
            ];
        }

        // Escanear
        $barcode->scan($userId);

        // Validar automáticamente
        $isValid = $barcode->validate();

        return [
            'success' => $isValid,
            'message' => $isValid ? 'Código validado correctamente' : 'Código rechazado',
            'barcode' => $barcode,
            'product' => $barcode->returnProduct,
            'return' => $barcode->returnRequest
        ];
    }

    /**
     * Obtener estadísticas de escaneo
     */
    public function getScanStats(ReturnRequest $returnRequest): array
    {
        $barcodes = ReturnBarcode::where('return_request_id', $returnRequest->id)->get();

        return [
            'total' => $barcodes->count(),
            'generated' => $barcodes->where('status', ReturnBarcode::STATUS_GENERATED)->count(),
            'printed' => $barcodes->where('status', ReturnBarcode::STATUS_PRINTED)->count(),
            'scanned' => $barcodes->where('status', ReturnBarcode::STATUS_SCANNED)->count(),
            'validated' => $barcodes->where('status', ReturnBarcode::STATUS_VALIDATED)->count(),
            'rejected' => $barcodes->where('status', ReturnBarcode::STATUS_REJECTED)->count(),
            'pending' => $barcodes->whereIn('status', [
                ReturnBarcode::STATUS_GENERATED,
                ReturnBarcode::STATUS_PRINTED
            ])->count(),
            'completion_percentage' => $barcodes->count() > 0
                ? round(($barcodes->where('status', ReturnBarcode::STATUS_VALIDATED)->count() / $barcodes->count()) * 100, 2)
                : 0
        ];
    }

    /**
     * Regenerar código de barras
     */
    public function regenerateBarcode(ReturnBarcode $barcode): ReturnBarcode
    {
        // Marcar el anterior como inválido
        $barcode->update(['status' => 'invalidated']);

        // Crear nuevo código
        return ReturnBarcode::createForReturnProduct(
            $barcode->return_request_id,
            $barcode->return_product_id,
            $barcode->barcode_type
        );
    }
}
