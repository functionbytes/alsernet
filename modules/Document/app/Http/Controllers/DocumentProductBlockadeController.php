<?php

namespace Modules\Document\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
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
        $lastSync = Setting::get('product_blockades_last_sync');
        $syncCount = Setting::get('product_blockades_sync_count', 0);
        $totalBlockades = DocumentProductBlockade::count();

        // Get all active document types for label association
        $documentTypes = DocumentType::where('is_active', true)
            ->orderBy('label')
            ->get();

        // Get current labels from settings (the ones user has configured)
        $currentLabels = Setting::get('product_blockade_labels', '');

        // Get statistics by blockade type (label)
        $blockadesByType = DocumentProductBlockade::selectRaw('blockade_type, COUNT(*) as count')
            ->groupBy('blockade_type')
            ->get()
            ->keyBy('blockade_type');

        // Get unique products count
        $uniqueProducts = DocumentProductBlockade::selectRaw('COUNT(DISTINCT COALESCE(product_id, product_attribute_id)) as count')
            ->value('count') ?? 0;

        // Get recent blockades with product details
        $recentBlockades = DocumentProductBlockade::with('documentType')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('documents::settings.blockades.index', [
            'lastSync' => $lastSync ? \Carbon\Carbon::parse($lastSync)->diffForHumans() : 'Nunca',
            'syncCount' => (int) $syncCount,
            'totalBlockades' => $totalBlockades,
            'uniqueProducts' => $uniqueProducts,
            'currentLabels' => $currentLabels,
            'documentTypes' => $documentTypes,
            'blockadesByType' => $blockadesByType,
            'recentBlockades' => $recentBlockades,
        ]);
    }

    /**
     * Sync product blockades from external MySQL database
     */
    public function sync(Request $request): JsonResponse
    {
        try {
            $fresh = $request->input('fresh', false);

            // Execute artisan command
            $exitCode = Artisan::call('migrate:product-blockades', [
                '--fresh' => $fresh,
            ]);

            $output = Artisan::output();

            // Save last sync info
            Setting::set('product_blockades_last_sync', now());
            Setting::set('product_blockades_sync_count', (int) Setting::get('product_blockades_sync_count', 0) + 1);

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
            Log::error('Error syncing product blockades: '.$e->getMessage());

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
        $lastSync = Setting::get('product_blockades_last_sync');
        $syncCount = Setting::get('product_blockades_sync_count', 0);
        $totalBlockades = DocumentProductBlockade::count();

        return response()->json([
            'success' => true,
            'last_sync' => $lastSync ? \Carbon\Carbon::parse($lastSync)->diffForHumans() : 'Nunca',
            'sync_count' => (int) $syncCount,
            'total_blockades' => $totalBlockades,
        ]);
    }

    /**
     * Add a new product blockade label
     */
    public function addLabel(Request $request): JsonResponse
    {
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
            $currentLabels = Setting::get('product_blockade_labels', '');

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

            Setting::set('product_blockade_labels', $updatedLabels);

            return response()->json([
                'success' => true,
                'message' => 'Etiqueta "'.$newLabel.'" agregada exitosamente',
            ]);

        } catch (\Exception $e) {
            Log::error('Error adding blockade label: '.$e->getMessage());

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
            $currentLabels = Setting::get('product_blockade_labels', '');

            // Parse existing labels
            $labelsArray = array_filter(array_map('trim', explode(',', $currentLabels)));

            // Remove the label (case-insensitive)
            $labelsArray = array_filter($labelsArray, function ($label) use ($labelToDelete) {
                return strtoupper($label) !== $labelToDelete;
            });

            // Update settings
            $updatedLabels = implode(',', $labelsArray);
            Setting::set('product_blockade_labels', $updatedLabels);

            return response()->json([
                'success' => true,
                'message' => 'Etiqueta "'.$labelToDelete.'" eliminada exitosamente',
            ]);

        } catch (\Exception $e) {
            Log::error('Error deleting blockade label: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la etiqueta: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a product blockade
     */
    public function destroy(Request $request): JsonResponse
    {
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
            Log::error('Error deleting product blockade: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el bloqueo: '.$e->getMessage(),
            ], 500);
        }
    }
}
