<?php

namespace App\Services\Return;

use App\Models\Return\ReturnRequest;
use App\Models\Return\ReturnDocument;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentService
{
    protected $barcodeService;

    public function __construct(BarcodeService $barcodeService)
    {
        $this->barcodeService = $barcodeService;
    }

    /**
     * Generar todos los documentos para una devolución
     */
    public function generateAllDocuments(ReturnRequest $returnRequest): array
    {
        $documents = [];

        // 1. Etiqueta de envío
        $documents['shipping_label'] = $this->generateShippingLabel($returnRequest);

        // 2. Albarán de devolución
        $documents['return_slip'] = $this->generateReturnSlip($returnRequest);

        // 3. Hoja de códigos de barras
        $documents['barcode_sheet'] = $this->barcodeService->generateLabelsPDF($returnRequest);

        // 4. Recibo para el cliente
        $documents['customer_receipt'] = $this->generateCustomerReceipt($returnRequest);

        return $documents;
    }

    /**
     * Generar etiqueta de envío
     */
    public function generateShippingLabel(ReturnRequest $returnRequest): string
    {
        $data = [
            'return' => $returnRequest,
            'customer' => $returnRequest->customer,
            'order' => $returnRequest->order,
            'warehouse_address' => $this->getWarehouseAddress(),
            'tracking_number' => $this->generateTrackingNumber($returnRequest),
            'carrier' => $returnRequest->carrier ?? null
        ];

        $pdf = PDF::loadView('returns.documents.shipping-label', $data);
        $pdf->setPaper([0, 0, 288, 432], 'portrait'); // 10x15 cm para etiquetas

        return $this->saveDocument(
            $returnRequest->id,
            ReturnDocument::TYPE_SHIPPING_LABEL,
            $pdf->output(),
            'shipping_label_' . $returnRequest->id . '.pdf'
        );
    }

    /**
     * Generar albarán de devolución
     */
    public function generateReturnSlip(ReturnRequest $returnRequest): string
    {
        $data = [
            'return' => $returnRequest,
            'customer' => $returnRequest->customer,
            'order' => $returnRequest->order,
            'products' => $returnRequest->products,
            'return_number' => $returnRequest->getReturnNumber(),
            'instructions' => $this->getReturnInstructions($returnRequest)
        ];

        $pdf = PDF::loadView('returns.documents.return-slip', $data);
        $pdf->setPaper('A4', 'portrait');

        return $this->saveDocument(
            $returnRequest->id,
            ReturnDocument::TYPE_RETURN_SLIP,
            $pdf->output(),
            'return_slip_' . $returnRequest->id . '.pdf'
        );
    }

    /**
     * Generar recibo para el cliente
     */
    public function generateCustomerReceipt(ReturnRequest $returnRequest): string
    {
        $data = [
            'return' => $returnRequest,
            'customer' => $returnRequest->customer,
            'order' => $returnRequest->order,
            'products' => $returnRequest->products,
            'financial_summary' => $returnRequest->getFinancialSummary(),
            'estimated_refund_date' => now()->addDays(config('returns.refund_days', 14))
        ];

        $pdf = PDF::loadView('returns.documents.customer-receipt', $data);
        $pdf->setPaper('A4', 'portrait');

        return $this->saveDocument(
            $returnRequest->id,
            ReturnDocument::TYPE_CUSTOMER_RECEIPT,
            $pdf->output(),
            'customer_receipt_' . $returnRequest->id . '.pdf'
        );
    }

    /**
     * Generar manifiesto para el transportista
     */
    public function generateCarrierManifest(array $returnRequests, $carrierId): string
    {
        $data = [
            'returns' => $returnRequests,
            'carrier' => \App\Models\Carrier::find($carrierId),
            'manifest_number' => $this->generateManifestNumber(),
            'pickup_date' => now(),
            'total_packages' => collect($returnRequests)->sum(function($return) {
                return $return->products->count();
            })
        ];

        $pdf = PDF::loadView('returns.documents.carrier-manifest', $data);
        $pdf->setPaper('A4', 'portrait');

        // Guardar para cada devolución
        $path = null;
        foreach ($returnRequests as $return) {
            $path = $this->saveDocument(
                $return->id,
                ReturnDocument::TYPE_CARRIER_MANIFEST,
                $pdf->output(),
                'manifest_' . $data['manifest_number'] . '.pdf'
            );
        }

        return $path;
    }

    /**
     * Guardar documento
     */
    protected function saveDocument($returnRequestId, $type, $content, $fileName): string
    {
        $path = "returns/documents/{$returnRequestId}/{$type}";
        Storage::makeDirectory($path);

        $fullPath = "{$path}/{$fileName}";
        Storage::put($fullPath, $content);

        ReturnDocument::create([
            'return_request_id' => $returnRequestId,
            'document_type' => $type,
            'file_path' => $fullPath,
            'file_name' => $fileName,
            'file_size' => strlen($content),
            'generated_at' => now(),
            'metadata' => [
                'generated_by' => auth()->id(),
                'ip_address' => request()->ip()
            ]
        ]);

        return $fullPath;
    }

    /**
     * Obtener dirección del almacén
     */
    protected function getWarehouseAddress(): array
    {
        return [
            'company' => config('company.name'),
            'department' => 'Departamento de Devoluciones',
            'address' => config('warehouse.address'),
            'city' => config('warehouse.city'),
            'postal_code' => config('warehouse.postal_code'),
            'country' => config('warehouse.country'),
            'phone' => config('warehouse.phone')
        ];
    }

    /**
     * Generar número de seguimiento
     */
    protected function generateTrackingNumber(ReturnRequest $returnRequest): string
    {
        return 'RET' . str_pad($returnRequest->id, 8, '0', STR_PAD_LEFT) . strtoupper(Str::random(4));
    }

    /**
     * Generar número de manifiesto
     */
    protected function generateManifestNumber(): string
    {
        return 'MAN' . date('Ymd') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }

    /**
     * Obtener instrucciones de devolución
     */
    protected function getReturnInstructions(ReturnRequest $returnRequest): array
    {
        $instructions = [
            'Imprima este albarán y la hoja de códigos de barras',
            'Pegue un código de barras en cada producto a devolver',
            'Incluya este albarán dentro del paquete',
            'Pegue la etiqueta de envío en el exterior del paquete'
        ];

        // Agregar instrucciones específicas según el método de recogida
        switch ($returnRequest->logistics_mode) {
            case 'home_pickup':
                $instructions[] = 'El transportista recogerá el paquete en la dirección indicada';
                break;
            case 'store_delivery':
                $instructions[] = 'Entregue el paquete en la tienda seleccionada';
                break;
            case 'inpost':
                $instructions[] = 'Deposite el paquete en el punto InPost indicado';
                break;
        }

        return $instructions;
    }

    /**
     * Combinar todos los PDFs en uno solo
     */
    public function combineDocuments(ReturnRequest $returnRequest): string
    {
        $documents = ReturnDocument::where('return_request_id', $returnRequest->id)
            ->whereIn('document_type', [
                ReturnDocument::TYPE_RETURN_SLIP,
                ReturnDocument::TYPE_BARCODE_SHEET,
                ReturnDocument::TYPE_CUSTOMER_RECEIPT
            ])
            ->get();

        // Aquí se implementaría la lógica para combinar PDFs
        // Por ejemplo, usando libraries como FPDI o similar

        $combinedPath = "returns/documents/{$returnRequest->id}/combined_documents.pdf";

        // ... lógica de combinación ...

        return $combinedPath;
    }
}
