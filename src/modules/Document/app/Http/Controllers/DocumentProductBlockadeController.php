<?php

namespace Modules\Document\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Modules\Core\Models\Setting;
use Modules\Document\Entities\DocumentProductBlockade;
use Modules\Document\Entities\DocumentType;

class DocumentProductBlockadeController extends Controller
{
    /**
     * Display product blockades configuration page
     */
    public function index(Request $request)
    {
        $lastSync = Setting::get('documents.product_blockades_last_sync');
        $syncCount = Setting::get('documents.product_blockades_sync_count', 0);
        $totalBlockades = DocumentProductBlockade::count();

        $documentTypes = DocumentType::where('is_active', true)
            ->orderBy('label')
            ->get();

        $currentLabels = Setting::get('documents.product_blockade_labels', '');

        $blockadesByType = DocumentProductBlockade::selectRaw('blockade_type, COUNT(*) as count')
            ->groupBy('blockade_type')
            ->get()
            ->keyBy('blockade_type');

        $uniqueProducts = DocumentProductBlockade::selectRaw('COUNT(DISTINCT COALESCE(product_id, product_attribute_id)) as count')
            ->value('count') ?? 0;

        // Paginated blockades list with search
        $search = $request->get('search');
        $typeFilter = $request->get('type');

        $blockades = DocumentProductBlockade::with('documentType')
            ->when($search, function ($q) use ($search) {
                $q->where(function ($q2) use ($search) {
                    $q2->where('product_id', $search)
                        ->orWhere('product_attribute_id', $search)
                        ->orWhere('source_id', $search)
                        ->orWhere('blockade_type', 'like', "%{$search}%");
                });
            })
            ->when($typeFilter, fn ($q) => $q->where('blockade_type', $typeFilter))
            ->orderBy('id', 'desc')
            ->paginate(50)
            ->withQueryString();

        $blockadeTypes = DocumentProductBlockade::selectRaw('DISTINCT blockade_type')
            ->orderBy('blockade_type')
            ->pluck('blockade_type');

        return view('documents::settings.blockades.index', [
            'lastSync' => $lastSync ? Carbon::parse($lastSync)->diffForHumans() : 'Nunca',
            'syncCount' => (int) $syncCount,
            'totalBlockades' => $totalBlockades,
            'uniqueProducts' => $uniqueProducts,
            'currentLabels' => $currentLabels,
            'documentTypes' => $documentTypes,
            'blockadesByType' => $blockadesByType,
            'blockadeTypes' => $blockadeTypes,
        ]);
    }

    /**
     * Display product blockades list page
     */
    public function products(Request $request)
    {
        $totalBlockades = DocumentProductBlockade::count();

        // Paginated blockades list with search
        $search = $request->get('search');
        $typeFilter = $request->get('type');

        $blockades = DocumentProductBlockade::with('documentType')
            ->when($search, function ($q) use ($search) {
                $q->where(function ($q2) use ($search) {
                    $q2->where('product_id', $search)
                        ->orWhere('product_attribute_id', $search)
                        ->orWhere('source_id', $search)
                        ->orWhere('blockade_type', 'like', "%{$search}%");
                });
            })
            ->when($typeFilter, fn ($q) => $q->where('blockade_type', $typeFilter))
            ->orderBy('id', 'desc')
            ->paginate(50)
            ->withQueryString();

        $blockadeTypes = DocumentProductBlockade::selectRaw('DISTINCT blockade_type')
            ->orderBy('blockade_type')
            ->pluck('blockade_type');

        return view('documents::settings.blockades.products', [
            'totalBlockades' => $totalBlockades,
            'blockades' => $blockades,
            'blockadeTypes' => $blockadeTypes,
            'search' => $search,
            'typeFilter' => $typeFilter,
        ]);
    }

    /**
     * Sync product blockades from external MySQL database
     */
    public function sync(Request $request): JsonResponse
    {
        if (Gate::denies('sync-document-blockades')) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        try {
            $fresh = $request->input('fresh', false);

            // Execute artisan command
            $exitCode = Artisan::call('migrate:product-blockades', [
                '--fresh' => $fresh,
            ]);

            $output = Artisan::output();

            // Save last sync info
            Setting::set('documents.product_blockades_last_sync', now());
            Setting::set('documents.product_blockades_sync_count', (int) Setting::get('documents.product_blockades_sync_count', 0) + 1);

            return response()->json([
                'success' => $exitCode === 0,
                'message' => $exitCode === 0
                    ? 'Sincronización de bloqueos completada exitosamente'
                    : 'Sincronización completada con errores',
                'output' => $output,
                'last_sync' => now()->format('Y-m-d H:i:s'),
                'total_blockades' => DocumentProductBlockade::count(),
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Error al sincronizar bloqueos: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get product blockades sync status
     */
    public function status(): JsonResponse
    {
        $lastSync = Setting::get('documents.product_blockades_last_sync');
        $syncCount = Setting::get('documents.product_blockades_sync_count', 0);
        $totalBlockades = DocumentProductBlockade::count();

        return response()->json([
            'success' => true,
            'last_sync' => $lastSync ? Carbon::parse($lastSync)->diffForHumans() : 'Nunca',
            'sync_count' => (int) $syncCount,
            'total_blockades' => $totalBlockades,
        ]);
    }

    /**
     * Add a new product blockade label
     */
    public function addLabel(Request $request): JsonResponse
    {
        if (Gate::denies('manage-document-blockades')) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        $validator = Validator::make($request->all(), [
            'label' => 'required|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Etiqueta inválida',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $newLabel = strtoupper(trim($request->label));
            $currentLabels = Setting::get('documents.product_blockade_labels', '');

            // Parse existing labels
            $labelsArray = array_filter(array_map('trim', explode(',', $currentLabels)));

            // Check if label already exists (case-insensitive)
            $labelExists = false;
            foreach ($labelsArray as $existingLabel) {
                if (strtoupper($existingLabel) === $newLabel) {
                    $labelExists = true;
                    break;
                }
            }

            if ($labelExists) {
                return response()->json([
                    'success' => false,
                    'message' => 'La etiqueta "'.$newLabel.'" ya existe',
                ], 422);
            }

            // Add new label
            $labelsArray[] = $newLabel;
            $updatedLabels = implode(',', $labelsArray);

            Setting::set('documents.product_blockade_labels', $updatedLabels);

            return response()->json([
                'success' => true,
                'message' => 'Etiqueta "'.$newLabel.'" agregada exitosamente',
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Error al agregar la etiqueta: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a product blockade label
     */
    public function deleteLabel(Request $request): JsonResponse
    {
        if (Gate::denies('manage-document-blockades')) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        $validator = Validator::make($request->all(), [
            'label' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Etiqueta inválida',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $labelToDelete = strtoupper(trim($request->label));
            $currentLabels = Setting::get('documents.product_blockade_labels', '');

            // Parse existing labels
            $labelsArray = array_filter(array_map('trim', explode(',', $currentLabels)));

            // Remove the label (case-insensitive)
            $labelsArray = array_filter($labelsArray, function ($label) use ($labelToDelete) {
                return strtoupper($label) !== $labelToDelete;
            });

            // Update settings
            $updatedLabels = implode(',', $labelsArray);
            Setting::set('documents.product_blockade_labels', $updatedLabels);

            return response()->json([
                'success' => true,
                'message' => 'Etiqueta "'.$labelToDelete.'" eliminada exitosamente',
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la etiqueta: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Sync a specific product from PrestaShop
     */
    public function syncProduct(Request $request): JsonResponse
    {
        if (Gate::denies('sync-document-blockades')) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        $validator = Validator::make($request->all(), [
            'source_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Source ID inválido',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $searchId = $request->input('source_id');
            $mapping = $this->loadDocumentTypeMapping();

            if (empty($mapping)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay etiquetas asociadas configuradas en los tipos de documento',
                ], 422);
            }

            $migratedCount = 0;
            $skippedCount = 0;
            $foundCount = 0;

            // Search in combinations table by id_origen, id_product or id_product_attribute
            $combinations = DB::connection('prestashop')->table('aalv_combinaciones_import')
                ->leftJoin('aalv_product_attribute as apa', 'apa.id_product_attribute', '=', 'aalv_combinaciones_import.id_product_attribute')
                ->select(
                    'aalv_combinaciones_import.id_origen',
                    'aalv_combinaciones_import.id_product_attribute',
                    'aalv_combinaciones_import.etiqueta',
                    'apa.id_product'
                )
                ->where('aalv_combinaciones_import.id_origen', $searchId)
                ->orWhere('aalv_combinaciones_import.id_product_attribute', $searchId)
                ->orWhere('apa.id_product', $searchId)
                ->get();

            foreach ($combinations as $combination) {
                $foundCount++;
                $result = $this->processSingleProduct($combination, $mapping, 'combination');
                if ($result === 'migrated') {
                    $migratedCount++;
                } else {
                    $skippedCount++;
                }
            }

            // Search in simple products table by id_origen or id_product
            $products = DB::connection('prestashop')->table('aalv_combinacionunica_import')
                ->select('id_origen', 'id_product', 'etiqueta')
                ->where('id_origen', $searchId)
                ->orWhere('id_product', $searchId)
                ->get();

            foreach ($products as $product) {
                $foundCount++;
                $result = $this->processSingleProduct($product, $mapping, 'product');
                if ($result === 'migrated') {
                    $migratedCount++;
                } else {
                    $skippedCount++;
                }
            }

            if ($foundCount === 0) {
                return response()->json([
                    'success' => false,
                    'message' => "No se encontró ningún producto con ID {$searchId} en PrestaShop. Intenta con source_id, product_id o product_attribute_id.",
                ], 404);
            }

            // Update sync stats
            Setting::set('documents.product_blockades_last_sync', now()->toDateTimeString());
            Setting::set('documents.product_blockades_sync_count', (int) Setting::get('documents.product_blockades_sync_count', 0) + 1);

            return response()->json([
                'success' => true,
                'message' => "Sincronización completada. Encontrados: {$foundCount}, Migrados: {$migratedCount}, Omitidos (duplicados): {$skippedCount}",
                'migrated' => $migratedCount,
                'skipped' => $skippedCount,
            ]);

        } catch (\Exception $e) {
            Log::error('Error sincronizando producto específico: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al sincronizar el producto: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Load document type mapping from database
     */
    private function loadDocumentTypeMapping(): array
    {
        $mapping = [];
        $documentTypes = DocumentType::all();

        foreach ($documentTypes as $docType) {
            if (! empty($docType->associated_labels) && is_array($docType->associated_labels)) {
                foreach ($docType->associated_labels as $label) {
                    $normalizedLabel = strtolower(trim($label));
                    $mapping[$normalizedLabel] = $docType->id;
                }
            }
        }

        return $mapping;
    }

    /**
     * Process a single product from PrestaShop
     */
    private function processSingleProduct(\stdClass $product, array $mapping, string $type): string
    {
        $sourceId = $product->id_origen;
        $etiqueta = $product->etiqueta ?? '';

        // Determine which labels match this product's etiqueta field
        $matchedLabel = null;
        $documentTypeId = null;

        foreach ($mapping as $label => $docTypeId) {
            if (str_contains(strtolower($etiqueta), strtolower($label))) {
                $matchedLabel = $label;
                $documentTypeId = $docTypeId;
                break;
            }
        }

        if (! $matchedLabel || ! $documentTypeId) {
            return 'skipped';
        }

        $data = [
            'source_id' => $sourceId,
            'blockade_type' => $matchedLabel,
            'document_type_id' => $documentTypeId,
        ];

        if ($type === 'product') {
            $data['product_id'] = $product->id_product;
            $data['product_attribute_id'] = null;
        } else {
            $data['product_id'] = $product->id_product ?? null;
            $data['product_attribute_id'] = $product->id_product_attribute;

            if (! $data['product_id']) {
                return 'skipped';
            }
        }

        // Check for existing blockade
        $exists = DocumentProductBlockade::where('product_id', $data['product_id'])
            ->where('product_attribute_id', $data['product_attribute_id'])
            ->where('document_type_id', $data['document_type_id'])
            ->where('source_id', $data['source_id'])
            ->exists();

        if ($exists) {
            return 'skipped';
        }

        DocumentProductBlockade::create($data);

        return 'migrated';
    }

    /**
     * Delete a product blockade
     */
    public function destroy(Request $request): JsonResponse
    {
        if (Gate::denies('manage-document-blockades')) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|exists:document_product_blockades,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'ID inválido',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $blockade = DocumentProductBlockade::findOrFail($request->id);
            $blockade->delete();

            return response()->json([
                'success' => true,
                'message' => 'Bloqueo eliminado exitosamente',
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el bloqueo: '.$e->getMessage(),
            ], 500);
        }
    }
}
