<?php

namespace Modules\Supplier\Http\Controllers\Settings\Suppliers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Supplier\Http\Requests\Source\BulkActionSourceRequest;
use Modules\Supplier\Http\Requests\Source\StoreSourceRequest;
use Modules\Supplier\Http\Requests\Source\UpdateSourceRequest;
use Modules\Supplier\Http\Requests\Source\UploadSourceFileRequest;
use Modules\Supplier\Jobs\ProcessSupplierExtractionJob;
use Modules\Supplier\Models\Extraction\ExtractionBatch;
use Modules\Supplier\Models\Source\Source;
use Modules\Supplier\Models\Source\SourceFile;
use Modules\Supplier\Models\Supplier\Supplier;
use Modules\Supplier\Models\Sync\SyncConflict;
use Modules\Supplier\Models\Sync\SyncFailure;
use Modules\Supplier\Services\Extraction\SourceDetectionService;
use Modules\Supplier\Services\ImageCompressionService;
use Modules\Supplier\Services\SourceConfigurationService;

class SupplierSourcesController extends Controller
{
    public function __construct(protected SourceConfigurationService $sourceConfigService) {}

    /**
     * Display sources list for supplier
     */
    public function index(Request $request, string $supplierUid): View
    {
        $supplier = Supplier::byUid($supplierUid)->firstOrFail();

        $query = $supplier->sources();

        // Search by label or description
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('label', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by source type
        if ($sourceType = $request->input('source_type')) {
            $query->where('source_type', $sourceType);
        }

        // Filter by active status
        if ($request->has('is_active') && $request->input('is_active') !== '') {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Filter by platform
        if ($platform = $request->input('platform')) {
            $allowed = ['web', 'api', 'ftp', 'upload', 'erp'];
            if (in_array($platform, $allowed, true)) {
                $query->where('platform', $platform);
            }
        }

        $perPage = (int) $request->input('per_page', 15);
        $perPage = in_array($perPage, [10, 20, 50, 100, 200]) ? $perPage : 15;

        // Order by priority asc (1 = highest priority first)
        $sources = $query->orderBy('priority', 'asc')
            ->orderBy('label', 'asc')
            ->paginate($perPage)
            ->withQueryString();

        $stats = [
            'total_failures'      => SyncFailure::where('supplier_id', $supplier->id)->count(),
            'retryable'           => SyncFailure::where('supplier_id', $supplier->id)->retryable()->whereIn('failure_status', ['pending', 'acknowledged'])->count(),
            'total_conflicts'     => SyncConflict::byEntityType('provider')->where('entity_id', $supplier->id)->count(),
            'unresolved_conflicts' => SyncConflict::byEntityType('provider')->where('entity_id', $supplier->id)->unresolved()->count(),
        ];

        $pageTitle = "Fuentes de {$supplier->label}";
        $breadcrumb = "Configuración / Proveedores / {$supplier->label} / Fuentes";

        return view('supplier::settings.views.sources.index', compact('supplier', 'sources', 'stats', 'pageTitle', 'breadcrumb'));
    }

    /**
     * Bulk action on sources (enable / disable / delete)
     */
    public function bulkAction(BulkActionSourceRequest $request, string $supplierUid): JsonResponse
    {
        $supplier = Supplier::byUid($supplierUid)->firstOrFail();
        $sources = $supplier->sources()->whereIn('id', $request->validated('ids'))->get();

        if ($sources->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'No se encontraron fuentes.'], 404);
        }

        $count = $sources->count();

        match ($request->action) {
            'enable' => $sources->each->update(['is_active' => true]),
            'disable' => $sources->each->update(['is_active' => false]),
            'delete' => $sources->each->delete(),
        };

        $messages = [
            'enable' => "Se activaron {$count} fuente(s).",
            'disable' => "Se desactivaron {$count} fuente(s).",
            'delete' => "Se eliminaron {$count} fuente(s).",
        ];

        return response()->json(['success' => true, 'message' => $messages[$request->action]]);
    }

    /**
     * Show create source form
     */
    public function create(string $supplierUid): View
    {
        $supplier = Supplier::byUid($supplierUid)->firstOrFail();
        $pageTitle = "Crear Fuente para {$supplier->label}";
        $breadcrumb = "Configuración / Proveedores / {$supplier->label} / Fuentes / Crear";

        return view('supplier::settings.views.sources.create', compact('supplier', 'pageTitle', 'breadcrumb'));
    }

    /**
     * Store new source
     */
    public function store(StoreSourceRequest $request, string $supplierUid): JsonResponse
    {
        try {
            $supplier = Supplier::byUid($supplierUid)->firstOrFail();

            $validated = $request->validated();
            $source = $supplier->sources()->create([
                'label' => $validated['label'] ?? $validated['name'] ?? null,
                'source_type' => $validated['source_type'] ?? $validated['type'] ?? null,
                'description' => $validated['description'] ?? null,
                'trust_level' => $validated['trust_level'] ?? 'medium',
                'usage_notes' => $validated['usage_notes'] ?? null,
                'priority' => $validated['priority'] ?? 10,
                'is_active' => $validated['is_active'] ?? true,
                'extraction_mode' => $validated['extraction_mode'] ?? 'ai',
            ]);

            // Create configuration if provided
            if (isset($validated['configuration']) && is_array($validated['configuration'])) {
                $this->saveSourceConfiguration($source, $validated['configuration']);
            }

            return response()->json([
                'success' => true,
                'message' => 'Fuente creada exitosamente',
                'source' => $source->load('configurations'),
            ]);

        } catch (\Exception $e) {
            Log::error('Error creating supplier source: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al crear la fuente',
            ], 500);
        }
    }

    /**
     * Show edit source form
     */
    public function edit(string $supplierUid, string $sourceUid): View
    {
        $supplier = Supplier::byUid($supplierUid)->firstOrFail();
        $source = $supplier->sources()->with('configurations')->where('uid', $sourceUid)->firstOrFail();
        $pageTitle = "Editar Fuente: {$source->label}";
        $breadcrumb = "Configuración / Proveedores / {$supplier->label} / Fuentes / Editar";

        $connectionConfig = $source->configurations->firstWhere('config_type', 'connection')?->config_data ?? [];

        return view('supplier::settings.views.sources.edit', compact('supplier', 'source', 'connectionConfig', 'pageTitle', 'breadcrumb'));
    }

    /**
     * Update source
     */
    public function update(UpdateSourceRequest $request, string $supplierUid, string $sourceUid): JsonResponse
    {
        try {
            $supplier = Supplier::byUid($supplierUid)->firstOrFail();
            $source = $supplier->sources()->where('uid', $sourceUid)->firstOrFail();

            $validated = $request->validated();
            $source->update([
                'label' => $validated['label'] ?? $validated['name'] ?? $source->label,
                'source_type' => $validated['source_type'] ?? $validated['type'] ?? $source->source_type,
                'description' => $validated['description'] ?? $source->description,
                'trust_level' => $validated['trust_level'] ?? $source->trust_level,
                'usage_notes' => $validated['usage_notes'] ?? $source->usage_notes,
                'priority' => $validated['priority'] ?? $source->priority,
                'is_active' => $validated['is_active'],
                'extraction_mode' => $validated['extraction_mode'] ?? $source->extraction_mode,
            ]);

            // Update configuration if provided
            if (isset($validated['configuration']) && is_array($validated['configuration'])) {
                $this->saveSourceConfiguration($source, $validated['configuration']);
            }

            return response()->json([
                'success' => true,
                'message' => 'Fuente actualizada exitosamente',
                'source' => $source->fresh()->load('configurations'),
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating supplier source: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la fuente',
            ], 500);
        }
    }

    /**
     * Delete source
     */
    public function destroy(string $supplierUid, string $sourceUid): JsonResponse
    {
        try {
            $supplier = Supplier::byUid($supplierUid)->firstOrFail();
            $source = $supplier->sources()->where('uid', $sourceUid)->firstOrFail();

            $source->delete();

            return response()->json([
                'success' => true,
                'message' => 'Fuente eliminada exitosamente',
            ]);

        } catch (\Exception $e) {
            Log::error('Error deleting supplier source: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la fuente',
            ], 500);
        }
    }

    /**
     * Test source connection
     */
    public function testConnection(string $supplierUid, string $sourceUid): JsonResponse
    {
        try {
            $supplier = Supplier::byUid($supplierUid)->firstOrFail();
            $source = $supplier->sources()->where('uid', $sourceUid)->firstOrFail();

            $result = $this->sourceConfigService->testConnection($source);

            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'data' => $result['data'] ?? null,
                'response_time' => $result['response_time'] ?? null,
                'status_code' => $result['status_code'] ?? null,
            ]);

        } catch (\Exception $e) {
            Log::error('Error testing source connection: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al probar la conexión',
            ], 500);
        }
    }

    /**
     * Trigger a manual extraction for the given source
     */
    public function triggerExtraction(string $supplierUid, string $sourceUid): JsonResponse
    {
        try {
            $supplier = Supplier::byUid($supplierUid)->firstOrFail();
            $source = $supplier->sources()->where('uid', $sourceUid)->firstOrFail();

            $alreadyRunning = ExtractionBatch::where('source_id', $source->id)
                ->where('status', 'processing')
                ->exists();

            if ($alreadyRunning) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya hay una extracción en curso para esta fuente',
                ], 409);
            }

            ProcessSupplierExtractionJob::dispatch($source->id, 'manual')
                ->onQueue('supplier-extraction');

            $source->update(['last_batch_status' => 'pending']);

            return response()->json([
                'success' => true,
                'message' => 'Extracción iniciada',
            ]);

        } catch (\Exception $e) {
            Log::error('Error triggering extraction: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al iniciar la extracción',
            ], 500);
        }
    }

    /**
     * Get the extraction status for the given source
     */
    public function getExtractionStatus(string $supplierUid, string $sourceUid): JsonResponse
    {
        try {
            $supplier = Supplier::byUid($supplierUid)->firstOrFail();
            $source = $supplier->sources()->where('uid', $sourceUid)->firstOrFail();

            $batch = ExtractionBatch::where('source_id', $source->id)
                ->orderBy('created_at', 'desc')
                ->first();

            return response()->json([
                'status' => $batch?->status ?? 'never',
                'last_processed_at' => $source->last_processed_at?->toIso8601String(),
                'last_batch_status' => $source->last_batch_status,
                'stats' => [
                    'total_items' => $batch?->total_items ?? 0,
                    'new_items' => $batch?->new_items ?? 0,
                    'updated_items' => $batch?->updated_items ?? 0,
                    'failed_items' => $batch?->failed_items ?? 0,
                ],
                'batch_uid' => $batch?->uid,
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting extraction status: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el estado de extracción',
            ], 500);
        }
    }

    /**
     * Upload a file to the given source
     */
    public function uploadFile(UploadSourceFileRequest $request, string $supplierUid, string $sourceUid): JsonResponse
    {

        try {
            $supplier = Supplier::byUid($supplierUid)->firstOrFail();
            $source = $supplier->sources()->where('uid', $sourceUid)->firstOrFail();

            $uploaded = $request->file('file');
            $ext = $uploaded->getClientOriginalExtension();

            $fileType = match (strtolower($ext)) {
                'pdf' => SourceFile::FILE_TYPE_PDF,
                'xlsx', 'xls' => SourceFile::FILE_TYPE_EXCEL,
                'docx', 'doc' => SourceFile::FILE_TYPE_WORD,
                'jpg', 'jpeg', 'png', 'webp' => SourceFile::FILE_TYPE_IMAGE,
                'csv' => SourceFile::FILE_TYPE_CSV,
                default => SourceFile::FILE_TYPE_OTHER,
            };

            $storedPath = $uploaded->storeAs(
                "supplier-files/{$supplier->id}",
                Str::uuid().'.'.$ext,
                'local'
            );

            $fileSize = $uploaded->getSize();
            if ($fileType === SourceFile::FILE_TYPE_IMAGE) {
                $stats = (new ImageCompressionService)->compress(Storage::disk('local')->path($storedPath));
                $fileSize = $stats['compressed'] ?: $fileSize;
            }

            $sourceFile = SourceFile::create([
                'uid' => Str::uuid(),
                'source_id' => $source->id,
                'supplier_id' => $source->supplier_id,
                // Sanitize: strip control chars, path-traversal markers and quotes,
                // collapse whitespace, and cap length. Prevents XSS / log injection
                // when the value is rendered back in listFiles() or audit logs.
                'original_name' => $this->sanitizeFilename((string) $uploaded->getClientOriginalName()),
                'stored_path' => $storedPath,
                'file_type' => $fileType,
                'file_size' => $fileSize,
                'mime_type' => $uploaded->getMimeType(),
                'origin' => SourceFile::ORIGIN_UPLOAD,
                'extraction_status' => SourceFile::STATUS_PENDING,
            ]);

            return response()->json([
                'success' => true,
                'file' => [
                    'uid' => $sourceFile->uid,
                    'name' => $sourceFile->original_name,
                    'size' => $sourceFile->file_size,
                    'type' => $sourceFile->file_type,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Error uploading source file: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al subir el archivo',
            ], 500);
        }
    }

    /**
     * Delete an uploaded file from the given source
     */
    public function deleteFile(string $supplierUid, string $sourceUid, string $fileUid): JsonResponse
    {
        try {
            $supplier = Supplier::byUid($supplierUid)->firstOrFail();
            $source = $supplier->sources()->where('uid', $sourceUid)->firstOrFail();

            $sourceFile = SourceFile::where('uid', $fileUid)
                ->where('source_id', $source->id)
                ->firstOrFail();

            Storage::disk('local')->delete($sourceFile->stored_path);
            $sourceFile->delete();

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            Log::error('Error deleting source file: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el archivo',
            ], 500);
        }
    }

    /**
     * List uploaded files for the given source
     */
    public function listFiles(string $supplierUid, string $sourceUid): JsonResponse
    {
        try {
            $supplier = Supplier::byUid($supplierUid)->firstOrFail();
            $source = $supplier->sources()->where('uid', $sourceUid)->firstOrFail();

            $files = SourceFile::bySource($source->id)
                ->get(['uid', 'original_name', 'file_type', 'file_size', 'extraction_status', 'extracted_at']);

            return response()->json(['success' => true, 'files' => $files]);

        } catch (\Exception $e) {
            Log::error('Error listing source files: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los archivos',
            ], 500);
        }
    }

    /**
     * Get source health status
     */
    public function getHealth(string $supplierUid, string $sourceUid): JsonResponse
    {
        try {
            $supplier = Supplier::byUid($supplierUid)->firstOrFail();
            $source = $supplier->sources()->where('uid', $sourceUid)->firstOrFail();

            $health = [
                'status' => $source->monitor->health_status ?? 'unknown',
                'uptime_percentage' => $source->monitor->uptime_percentage ?? 0,
                'last_check' => $source->monitor->last_checked_at?->diffForHumans(),
                'last_success' => $source->monitor->last_successful_at?->diffForHumans(),
                'consecutive_failures' => $source->monitor->consecutive_failures ?? 0,
                'average_response_time' => $source->monitor->average_response_time ?? 0,
            ];

            return response()->json([
                'success' => true,
                'health' => $health,
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting source health: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el estado de salud de la fuente',
            ], 500);
        }
    }

    /**
     * Consolidate form config fields into typed SourceConfiguration records.
     * The form sends flat keys (urls, timeout, rate_limit, host, …) which we
     * merge into a single 'connection' config per source type.
     */
    private function saveSourceConfiguration(
        Source $source,
        array $config
    ): void {
        // Extract shared connection-option scalars
        $connectionExtras = [
            'timeout' => (int) ($config['timeout'] ?? 30),
            'rate_limit' => (int) ($config['rate_limit'] ?? 10),
            'user_agent' => $config['user_agent'] ?? '',
        ];

        if (isset($config['urls']) && is_array($config['urls'])) {
            // Website: urls array + connection options → one 'connection' config
            $urls = array_values(array_filter(
                $config['urls'],
                fn ($u) => ! empty($u['url'] ?? ($u[0] ?? ''))
            ));
            $this->sourceConfigService->setConfiguration($source, 'connection', array_merge(
                ['urls' => $urls],
                $connectionExtras
            ));

            return;
        }

        if (isset($config['host'])) {
            // FTP / SFTP
            $this->sourceConfigService->setConfiguration($source, 'connection', $config);

            return;
        }

        if (isset($config['base_url'])) {
            // API
            $this->sourceConfigService->setConfiguration($source, 'connection', $config);

            return;
        }

        // Fallback: save whatever arrays came in
        foreach ($config as $key => $value) {
            if (is_array($value)) {
                $this->sourceConfigService->setConfiguration($source, $key, $value);
            }
        }
    }

    /**
     * Detect platform type from a URL (AJAX endpoint for the create/edit form).
     */
    public function detectSource(Request $request): JsonResponse
    {
        $url = trim($request->input('url', ''));

        if (empty($url)) {
            return response()->json(['error' => 'URL requerida'], 422);
        }

        try {
            $result = app(SourceDetectionService::class)->detect($url);

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('SourceDetection error: '.$e->getMessage());

            return response()->json(['error' => 'Error al analizar la URL: '.$e->getMessage()], 500);
        }
    }

    /**
     * Display the standalone detection tool page
     */
    public function showDetectionTool(): View
    {
        return view('supplier::settings.views.detect.tool');
    }

    private function sanitizeFilename(string $name): string
    {
        $name = preg_replace('/[\/\\\.\.\x00-\x1F\x7F"\'<>|:*?]/', '', $name);
        $name = preg_replace('/\s+/', ' ', trim($name));
        return mb_substr($name, 0, 200) ?: 'file';
    }
}
