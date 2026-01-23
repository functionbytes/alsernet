<?php

namespace Modules\Document\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Document\Entities\Document;
use Modules\Document\Entities\DocumentProduct;
use Modules\Prestashop\Entities\Orders\Order as PrestashopOrder;
use Modules\Supplier\Services\Integrations\ErpService;

/**
 * Handles document synchronization with external systems (ERP, PrestaShop)
 * Extracted from DocumentsController for better separation of concerns
 */
class DocumentSyncController extends Controller
{
    /**
     * Show import index page
     */
    public function importIndex()
    {
        if (! auth()->user()->canDocument('route-sync-documents') &&
            ! auth()->user()->canDocument('route-sync-erp-documents') &&
            ! auth()->user()->canDocument('route-sync-api-documents')) {
            return response()->view('documents::documents.access-denied', [
                'message' => 'No tienes permiso para importar documentos.',
                'action' => 'route-sync-documents',
            ], 403);
        }

        return view('documents::documents.import.index');
    }

    /**
     * Show API import page
     */
    public function importApi()
    {
        if (! auth()->user()->canDocument('route-sync-documents') &&
            ! auth()->user()->canDocument('route-sync-api-documents')) {
            return response()->view('documents::documents.access-denied', [
                'message' => 'No tienes permiso para importar desde PrestaShop.',
                'action' => 'route-sync-api-documents',
            ], 403);
        }

        return view('documents::documents.import.api');
    }

    /**
     * Show ERP import page
     */
    public function importErp()
    {
        if (! auth()->user()->canDocument('route-sync-documents') &&
            ! auth()->user()->canDocument('route-sync-erp-documents')) {
            return response()->view('documents::documents.access-denied', [
                'message' => 'No tienes permiso para importar desde Gestión.',
                'action' => 'route-sync-erp-documents',
            ], 403);
        }

        return view('documents::documents.import.erp');
    }

    /**
     * Sync documents from ERP
     */
    public function syncFromErp(Request $request): JsonResponse
    {
        if (! auth()->user()->canDocument('route-sync-documents') &&
            ! auth()->user()->canDocument('route-sync-erp-documents')) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para sincronizar desde ERP.',
            ], 403);
        }

        try {
            $request->validate([
                'order_ids' => 'nullable|array',
                'order_ids.*' => 'string',
                'source_id' => 'nullable|integer',
                'load_id' => 'nullable|integer',
                'sync_id' => 'nullable|integer',
            ]);

            $orderIds = $request->input('order_ids', []);
            $sourceId = $request->input('source_id');
            $loadId = $request->input('load_id');
            $syncId = $request->input('sync_id');

            $erpService = new ErpService;
            $imported = 0;
            $failed = 0;
            $errors = [];

            // Si se proporcionan IDs específicos, importar solo esos
            if (! empty($orderIds)) {
                foreach ($orderIds as $orderId) {
                    try {
                        $orderData = $erpService->getOrder($orderId);

                        if ($orderData) {
                            $document = $this->createDocumentFromErpData(
                                $orderId,
                                $orderData,
                                $sourceId,
                                $loadId,
                                $syncId
                            );

                            if ($document) {
                                $imported++;
                            } else {
                                $failed++;
                                $errors[] = "Error creando documento para orden {$orderId}";
                            }
                        } else {
                            $failed++;
                            $errors[] = "Orden {$orderId} no encontrada en ERP";
                        }
                    } catch (\Exception $e) {
                        $failed++;
                        $errors[] = "Error importando orden {$orderId}: ".$e->getMessage();
                    }
                }
            } else {
                // Importar todas las órdenes disponibles
                $allOrders = $erpService->getAllOrders();

                foreach ($allOrders as $orderData) {
                    try {
                        $orderId = $orderData['id'] ?? $orderData['order_id'] ?? null;

                        if (! $orderId) {
                            $failed++;

                            continue;
                        }

                        $document = $this->createDocumentFromErpData(
                            $orderId,
                            $orderData,
                            $sourceId,
                            $loadId,
                            $syncId
                        );

                        if ($document) {
                            $imported++;
                        } else {
                            $failed++;
                        }
                    } catch (\Exception $e) {
                        $failed++;
                        $errors[] = $e->getMessage();
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Importación completada: {$imported} exitosos, {$failed} fallidos",
                'imported' => $imported,
                'failed' => $failed,
                'errors' => $errors,
            ]);

        } catch (\Exception $e) {
            Log::error('Error syncing from ERP', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al sincronizar desde ERP: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get available orders from ERP
     */
    public function getAvailableOrders(Request $request): JsonResponse
    {
        if (! auth()->user()->canDocument('route-sync-documents')) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para obtener órdenes disponibles.',
            ], 403);
        }

        try {
            $erpService = new ErpService;
            $orders = $erpService->getAllOrders();

            // Filtrar órdenes que ya tienen documento
            $existingOrderIds = Document::whereNotNull('order_reference')
                ->pluck('order_reference')
                ->toArray();

            $availableOrders = collect($orders)->filter(function ($order) use ($existingOrderIds) {
                $orderId = $order['id'] ?? $order['order_id'] ?? null;

                return $orderId && ! in_array($orderId, $existingOrderIds);
            })->values();

            return response()->json([
                'success' => true,
                'orders' => $availableOrders,
                'total' => $availableOrders->count(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener órdenes: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Sync all documents with their orders
     */
    public function syncAllDocuments(): JsonResponse
    {
        if (! auth()->user()->canDocument('route-sync-documents')) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para sincronizar documentos.',
            ], 403);
        }

        try {
            $synced = 0;
            $failed = 0;
            $errors = [];

            $documents = Document::whereNotNull('order_reference')->get();

            foreach ($documents as $document) {
                try {
                    $order = PrestashopOrder::where('reference', $document->order_reference)->first();

                    if ($order) {
                        if ($this->syncDocumentWithOrder($document, $order)) {
                            $synced++;
                        } else {
                            $failed++;
                            $errors[] = "Error sincronizando documento {$document->uid}";
                        }
                    } else {
                        $failed++;
                        $errors[] = "Orden no encontrada para documento {$document->uid}";
                    }
                } catch (\Exception $e) {
                    $failed++;
                    $errors[] = "Error: {$e->getMessage()}";
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Sincronización completada: {$synced} exitosos, {$failed} fallidos",
                'synced' => $synced,
                'failed' => $failed,
                'errors' => $errors,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al sincronizar documentos: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Sync document by order ID
     */
    public function syncByOrderId(Request $request): JsonResponse
    {
        if (! auth()->user()->canDocument('route-sync-documents')) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para sincronizar este documento.',
            ], 403);
        }

        try {
            $request->validate([
                'order_id' => 'required|string',
            ]);

            $orderId = $request->input('order_id');

            $order = PrestashopOrder::where('reference', $orderId)
                ->orWhere('id_order', $orderId)
                ->first();

            if (! $order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Orden no encontrada',
                ], 404);
            }

            $document = Document::where('order_reference', $order->reference)->first();

            if (! $document) {
                return response()->json([
                    'success' => false,
                    'message' => 'Documento no encontrado para esta orden',
                ], 404);
            }

            if ($this->syncDocumentWithOrder($document, $order)) {
                return response()->json([
                    'success' => true,
                    'message' => 'Documento sincronizado correctamente',
                    'document' => $document->fresh(),
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al sincronizar documento',
                ], 500);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Sync all document fields from orders
     */
    public function syncAllDocumentFields(Request $request): JsonResponse
    {
        if (! auth()->user()->canDocument('route-sync-documents')) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para sincronizar campos de documentos.',
            ], 403);
        }

        try {
            $updated = 0;
            $failed = 0;

            $documents = Document::whereNotNull('order_reference')->get();

            foreach ($documents as $document) {
                try {
                    $order = PrestashopOrder::where('reference', $document->order_reference)->first();

                    if ($order && $this->syncDocumentWithOrder($document, $order)) {
                        $updated++;
                    } else {
                        $failed++;
                    }
                } catch (\Exception $e) {
                    $failed++;
                    Log::error("Error syncing document {$document->uid}", [
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Campos sincronizados: {$updated} actualizados, {$failed} fallidos",
                'updated' => $updated,
                'failed' => $failed,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create document from ERP data
     * Private helper method
     */
    private function createDocumentFromErpData(
        string $orderIdentifier,
        array $orderData,
        ?int $sourceId = null,
        ?int $loadId = null,
        ?int $syncId = null
    ): ?Document {
        try {
            // Check if document already exists
            $existingDocument = Document::where('order_reference', $orderIdentifier)->first();

            if ($existingDocument) {
                Log::info("Document already exists for order {$orderIdentifier}");

                return $existingDocument;
            }

            // Create new document
            $document = Document::create([
                'uid' => \Illuminate\Support\Str::uuid(),
                'order_reference' => $orderIdentifier,
                'order_date' => $orderData['date'] ?? now(),
                'customer_email' => $orderData['customer_email'] ?? null,
                'customer_name' => $orderData['customer_name'] ?? null,
                'customer_lastname' => $orderData['customer_lastname'] ?? null,
                'source_id' => $sourceId,
                'load_id' => $loadId,
                'sync_id' => $syncId,
                'status_id' => 1, // pending
            ]);

            // Create products
            if (isset($orderData['products'])) {
                $this->createDocumentProductsFromErpData($document, $orderData);
            }

            return $document;

        } catch (\Exception $e) {
            Log::error('Error creating document from ERP data', [
                'order' => $orderIdentifier,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Create document products from ERP data
     * Private helper method
     */
    private function createDocumentProductsFromErpData(Document $document, array $orderData): void
    {
        if (! isset($orderData['products']) || ! is_array($orderData['products'])) {
            return;
        }

        foreach ($orderData['products'] as $productData) {
            try {
                DocumentProduct::create([
                    'document_id' => $document->id,
                    'product_id' => $productData['id'] ?? null,
                    'product_name' => $productData['name'] ?? '',
                    'quantity' => $productData['quantity'] ?? 1,
                    'price' => $productData['price'] ?? 0,
                ]);
            } catch (\Exception $e) {
                Log::error('Error creating document product', [
                    'document_id' => $document->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Sync document with PrestaShop order
     * Private helper method
     */
    private function syncDocumentWithOrder(Document $document, PrestashopOrder $order): bool
    {
        try {
            $customer = $order->customer;

            if (! $customer) {
                return false;
            }

            $langId = null;

            // Map language from order
            if (isset($order->lang?->iso_code) && ! empty($order->lang?->iso_code)) {
                $isoCode = strtolower(trim($order->lang?->iso_code));
                $laravelLang = \App\Models\Lang::iso($isoCode);

                if ($laravelLang) {
                    $langId = $laravelLang->id;
                } else {
                    $defaultLang = \App\Models\Lang::iso('es');
                    $langId = $defaultLang ? $defaultLang->id : null;
                }
            }

            // Update document fields
            $document->lang_id = $langId;
            $document->order_reference = $order->reference ?? $document->order_reference;
            $document->order_date = $order->date_add ?? $document->order_date;
            $document->cart_id = $order->id_cart ?? $document->cart_id;
            $document->customer_id = $customer->id_customer;

            $deliveryAddress = $order->deliveryAddress;

            if ($deliveryAddress) {
                $document->customer_name = $deliveryAddress->firstname ?? $document->customer_name;
                $document->customer_lastname = $deliveryAddress->lastname ?? $document->customer_lastname;
            }

            $document->customer_email = $customer->email ?? $document->customer_email;
            $document->save();

            return true;

        } catch (\Exception $e) {
            Log::error('Error syncing document with order', [
                'document_id' => $document->id,
                'order_reference' => $order->reference ?? 'unknown',
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
