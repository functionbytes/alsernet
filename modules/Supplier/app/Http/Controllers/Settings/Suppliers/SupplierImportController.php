<?php

namespace Modules\Supplier\Http\Controllers\Settings\Suppliers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Modules\Supplier\Http\Requests\Import\SyncSupplierRequest;
use Modules\Supplier\Jobs\SyncCategoriesFromErpJob;
use Modules\Supplier\Jobs\SyncModelsJob;
use Modules\Supplier\Jobs\SyncProviderProductsJob;
use Modules\Supplier\Jobs\SyncProvidersFromErpJob;
use Modules\Supplier\Models\Sync\SyncBatch;
use Modules\Supplier\Services\Integrations\ErpProviderSyncService;

class SupplierImportController extends Controller
{
    /**
     * Show import index page
     */
    public function index(): View
    {
        return view('supplier::settings.views.suppliers.import.index');
    }

    /**
     * Show ERP import page
     */
    public function erp(): View
    {
        return view('supplier::settings.views.suppliers.import.erp');
    }

    /**
     * Sync all providers from ERP (async via batch)
     */
    public function syncFromErp(Request $request): JsonResponse
    {
        try {
            $batch = SyncBatch::create([
                'batch_name' => 'Sync all providers from ERP',
                'sync_type' => 'provider',
                'status' => 'pending',
                'priority' => 'normal',
                'batch_size' => 100,
                'total_items' => 0,
                'triggered_by' => 'manual',
                'trigger_details' => 'User-initiated bulk import from ERP page',
            ]);

            SyncProvidersFromErpJob::dispatch($batch)->onQueue('sync');

            Log::info('ERP provider sync dispatched from import page', ['batch_id' => $batch->id]);

            return response()->json([
                'success' => true,
                'message' => 'Sincronización de proveedores iniciada correctamente.',
                'batch_id' => $batch->id,
                'batch_uid' => $batch->uid,
            ]);
        } catch (\Exception $e) {
            Log::error('Error dispatching ERP provider sync', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Error al iniciar sincronización',
            ], 500);
        }
    }

    /**
     * Sync categories from ERP (async via batch)
     */
    public function syncCategoriesFromErp(Request $request): JsonResponse
    {
        try {
            $batch = SyncBatch::create([
                'batch_name' => 'Sync categories from ERP',
                'sync_type' => 'category',
                'status' => 'pending',
                'priority' => 'normal',
                'batch_size' => 100,
                'total_items' => 0,
                'triggered_by' => 'manual',
                'trigger_details' => 'User-initiated category sync from ERP page',
            ]);

            SyncCategoriesFromErpJob::dispatch($batch)->onQueue('sync');

            return response()->json([
                'success' => true,
                'message' => 'Sincronización de categorías iniciada correctamente.',
                'batch_id' => $batch->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Error dispatching ERP category sync', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Error al iniciar sincronización',
            ], 500);
        }
    }

    /**
     * Sync models from ERP (async via batch)
     */
    public function syncModelsFromErp(Request $request): JsonResponse
    {
        try {
            $batch = SyncBatch::create([
                'batch_name' => 'Sync models from ERP',
                'sync_type' => 'model',
                'status' => 'pending',
                'priority' => 'normal',
                'batch_size' => 100,
                'total_items' => 0,
                'triggered_by' => 'manual',
                'trigger_details' => 'User-initiated model sync from ERP page',
            ]);

            SyncModelsJob::dispatch($batch)->onQueue('sync');

            return response()->json([
                'success' => true,
                'message' => 'Sincronización de modelos iniciada correctamente.',
                'batch_id' => $batch->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Error dispatching ERP model sync', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Error al iniciar sincronización',
            ], 500);
        }
    }

    /**
     * Sync products from a specific ERP provider (async via batch)
     */
    public function syncProviderProductsFromErp(Request $request): JsonResponse
    {
        try {
            $erpProviderId = $request->input('erp_provider_id');

            $batchName = $erpProviderId
                ? "Sync products from Provider #{$erpProviderId}"
                : 'Sync products from all providers';

            $batch = SyncBatch::create([
                'batch_name' => $batchName,
                'sync_type' => 'product',
                'status' => 'pending',
                'priority' => 'normal',
                'batch_size' => 50,
                'total_items' => 0,
                'triggered_by' => 'manual',
                'trigger_details' => 'User-initiated product sync from ERP page',
                'filter_criteria' => $erpProviderId ? ['erp_provider_id' => $erpProviderId] : null,
            ]);

            SyncProviderProductsJob::dispatch($batch, $erpProviderId)->onQueue('sync');

            return response()->json([
                'success' => true,
                'message' => 'Sincronización de productos iniciada correctamente.',
                'batch_id' => $batch->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Error dispatching ERP product sync', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Error al iniciar sincronización',
            ], 500);
        }
    }

    /**
     * Sync a single provider from ERP by erp_id
     */
    public function sync(SyncSupplierRequest $request, ErpProviderSyncService $service): JsonResponse
    {
        try {
            $result = $service->syncProviderById((int) $request->validated('erp_id'));

            $supplier = $result['supplier'];
            $action = $result['created'] ? 'creado' : 'actualizado';

            return response()->json([
                'success' => true,
                'message' => "Proveedor {$supplier->label} {$action} correctamente",
                'created' => $result['created'],
                'supplier' => [
                    'uid' => $supplier->uid,
                    'erp_id' => $supplier->erp_id,
                    'label' => $supplier->label,
                    'code' => $supplier->code,
                    'available' => $supplier->available,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Error syncing supplier from ERP: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Error al sincronizar el proveedor',
            ], 500);
        }
    }
}
