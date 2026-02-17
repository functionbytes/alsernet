<?php

namespace Modules\Mailrelay\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessEmailImportJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Modules\Mailrelay\Entities\ImportJob;
use Modules\Mailrelay\Enums\ImportStatus;

/**
 * ImportController
 *
 * API controller for managing email imports from CSV/Excel files.
 * Provides endpoints for uploading files, checking import progress,
 * retrieving import reports, and listing import jobs.
 *
 * Reference: FASE 8 - Section 8.3
 */
class ImportController extends Controller
{
    /**
     * Upload and process an import file
     *
     * Accepts CSV or Excel files for bulk email import.
     * Validates the file, stores it, creates an import job,
     * and dispatches ProcessEmailImportJob for background processing.
     *
     *
     * @throws ValidationException
     */
    public function upload(Request $request): JsonResponse
    {
        try {
            // Validate request input
            $validated = $request->validate([
                'file' => 'required|file|mimes:csv,xlsx,xls|max:10240', // Max 10MB
                'list_id' => 'nullable|string|exists:mailrelay_groups,id',
                'validate_emails' => 'nullable|boolean',
                'sync_mailrelay' => 'nullable|boolean',
            ]);

            Log::info('ImportController: File upload request received', [
                'filename' => $request->file('file')->getClientOriginalName(),
                'size' => $request->file('file')->getSize(),
                'list_id' => $validated['list_id'] ?? null,
            ]);

            // Get uploaded file
            $file = $request->file('file');
            $originalFilename = $file->getClientOriginalName();

            // Store the file in imports directory
            $filePath = $file->store('imports', 'local');

            if (! $filePath) {
                Log::error('ImportController: Failed to store uploaded file');

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to store uploaded file',
                ], 500);
            }

            // Prepare import options
            $options = [
                'validate_emails' => $validated['validate_emails'] ?? true,
                'sync_mailrelay' => $validated['sync_mailrelay'] ?? true,
                'list_id' => $validated['list_id'] ?? null,
            ];

            // Create import job record
            $importJob = ImportJob::create([
                'filename' => $originalFilename,
                'status' => ImportStatus::PENDING,
                'total_emails' => 0,
                'processed_emails' => 0,
                'valid_emails' => 0,
                'invalid_emails' => 0,
                'options' => $options,
            ]);

            Log::info('ImportController: Import job created', [
                'import_id' => $importJob->id,
                'filename' => $originalFilename,
            ]);

            // Dispatch the import processing job
            ProcessEmailImportJob::dispatch(
                $importJob->id,
                $filePath,
                $validated['list_id'] ?? '',
                $validated['validate_emails'] ?? true
            );

            Log::info('ImportController: ProcessEmailImportJob dispatched', [
                'import_id' => $importJob->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Import file uploaded successfully and processing has started',
                'data' => [
                    'import_id' => $importJob->id,
                    'filename' => $importJob->filename,
                    'status' => $importJob->status->value,
                    'total_emails' => $importJob->total_emails,
                    'created_at' => $importJob->created_at->toIso8601String(),
                ],
            ], 201);

        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('ImportController: Upload error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while uploading the import file',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Check the status and progress of an import job
     *
     * Returns current progress including counts of processed, valid, and invalid emails.
     */
    public function status(string $importId): JsonResponse
    {
        try {
            Log::info('ImportController: Status check for import', ['import_id' => $importId]);

            // Find import job
            $importJob = ImportJob::find($importId);

            if (! $importJob) {
                return response()->json([
                    'success' => false,
                    'message' => 'Import job not found',
                ], 404);
            }

            // Calculate progress percentage
            $progressPercentage = $importJob->getProgressPercentage();

            return response()->json([
                'success' => true,
                'data' => [
                    'import_id' => $importJob->id,
                    'filename' => $importJob->filename,
                    'status' => $importJob->status->value,
                    'total_emails' => $importJob->total_emails,
                    'processed_emails' => $importJob->processed_emails,
                    'valid_emails' => $importJob->valid_emails,
                    'invalid_emails' => $importJob->invalid_emails,
                    'progress_percentage' => $progressPercentage,
                    'started_at' => $importJob->started_at?->toIso8601String(),
                    'completed_at' => $importJob->completed_at?->toIso8601String(),
                    'created_at' => $importJob->created_at->toIso8601String(),
                    'is_completed' => $importJob->status === ImportStatus::COMPLETED,
                    'is_failed' => $importJob->status === ImportStatus::FAILED,
                    'is_processing' => $importJob->status === ImportStatus::PROCESSING,
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('ImportController: Status check error', [
                'import_id' => $importId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while checking import status',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Get the detailed report of a completed import job
     *
     * Returns detailed information including errors, validation results,
     * and processing summary.
     */
    public function report(string $importId): JsonResponse
    {
        try {
            Log::info('ImportController: Report request for import', ['import_id' => $importId]);

            // Find import job
            $importJob = ImportJob::find($importId);

            if (! $importJob) {
                return response()->json([
                    'success' => false,
                    'message' => 'Import job not found',
                ], 404);
            }

            // Check if import has completed
            if ($importJob->status !== ImportStatus::COMPLETED && $importJob->status !== ImportStatus::FAILED) {
                return response()->json([
                    'success' => false,
                    'message' => 'Import report is only available for completed or failed imports',
                    'status' => $importJob->status->value,
                ], 400);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'import_id' => $importJob->id,
                    'filename' => $importJob->filename,
                    'status' => $importJob->status->value,
                    'summary' => [
                        'total_emails' => $importJob->total_emails,
                        'processed_emails' => $importJob->processed_emails,
                        'valid_emails' => $importJob->valid_emails,
                        'invalid_emails' => $importJob->invalid_emails,
                        'success_rate' => $importJob->total_emails > 0
                            ? round(($importJob->valid_emails / $importJob->total_emails) * 100, 2)
                            : 0,
                    ],
                    'report' => $importJob->report ?? [],
                    'options' => $importJob->options ?? [],
                    'started_at' => $importJob->started_at?->toIso8601String(),
                    'completed_at' => $importJob->completed_at?->toIso8601String(),
                    'duration_seconds' => $importJob->started_at && $importJob->completed_at
                        ? $importJob->started_at->diffInSeconds($importJob->completed_at)
                        : null,
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('ImportController: Report error', [
                'import_id' => $importId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching import report',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * List all import jobs with optional filtering and pagination
     *
     *
     * @throws ValidationException
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Validate request parameters
            $validated = $request->validate([
                'status' => 'nullable|string|in:pending,processing,completed,failed,cancelled',
                'search' => 'nullable|string|max:255',
                'per_page' => 'nullable|integer|min:1|max:100',
                'page' => 'nullable|integer|min:1',
                'sort_by' => 'nullable|string|in:filename,status,total_emails,created_at,started_at,completed_at',
                'sort_order' => 'nullable|string|in:asc,desc',
            ]);

            Log::info('ImportController: Index request', $validated);

            // Build query
            $query = ImportJob::query();

            // Filter by status
            if (! empty($validated['status'])) {
                $query->where('status', $validated['status']);
            }

            // Search by filename
            if (! empty($validated['search'])) {
                $search = $validated['search'];
                $query->where('filename', 'like', "%{$search}%");
            }

            // Sorting
            $sortBy = $validated['sort_by'] ?? 'created_at';
            $sortOrder = $validated['sort_order'] ?? 'desc';
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $validated['per_page'] ?? 15;
            $imports = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $imports->map(function ($importJob) {
                    return [
                        'import_id' => $importJob->id,
                        'filename' => $importJob->filename,
                        'status' => $importJob->status->value,
                        'total_emails' => $importJob->total_emails,
                        'processed_emails' => $importJob->processed_emails,
                        'valid_emails' => $importJob->valid_emails,
                        'invalid_emails' => $importJob->invalid_emails,
                        'progress_percentage' => $importJob->getProgressPercentage(),
                        'started_at' => $importJob->started_at?->toIso8601String(),
                        'completed_at' => $importJob->completed_at?->toIso8601String(),
                        'created_at' => $importJob->created_at?->toIso8601String(),
                    ];
                }),
                'meta' => [
                    'current_page' => $imports->currentPage(),
                    'per_page' => $imports->perPage(),
                    'total' => $imports->total(),
                    'last_page' => $imports->lastPage(),
                    'from' => $imports->firstItem(),
                    'to' => $imports->lastItem(),
                ],
                'links' => [
                    'first' => $imports->url(1),
                    'last' => $imports->url($imports->lastPage()),
                    'prev' => $imports->previousPageUrl(),
                    'next' => $imports->nextPageUrl(),
                ],
            ], 200);

        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('ImportController: Index error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching import jobs',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }
}
