<?php

namespace Modules\Chat\Http\Controllers\Helpdesk\Customers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Modules\Chat\Http\Controllers\Controller;
use Modules\Chat\Models\Conversations\ConversationLabel;
use Modules\Chat\Services\Customers\CustomerImportService;

class CustomerImportController extends Controller
{
    public function __construct(
        protected CustomerImportService $importService
    ) {}

    /**
     * Show advanced import UI.
     */
    public function showAdvanced(): View
    {
        $labels = ConversationLabel::where('account_id', Auth::user()->account_id)
            ->orderBy('title')
            ->get();

        return view('Chat::chats.customers.import-advanced', [
            'labels' => $labels,
            'availableFields' => $this->importService->getAvailableFields(),
        ]);
    }

    /**
     * Parse uploaded CSV file.
     */
    public function parseFile(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        try {
            $file = $request->file('file');
            $filePath = $file->store('temp');
            $fullPath = storage_path('app/'.$filePath);

            $parsed = $this->importService->parseCSV($fullPath);

            // Store file path in session for later use
            session(['import_file_path' => $fullPath]);

            return response()->json([
                'success' => true,
                'headers' => $parsed['headers'],
                'sample_rows' => $parsed['sample_rows'],
                'total_rows' => $parsed['total_rows'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Preview import with field mapping.
     */
    public function preview(Request $request): JsonResponse
    {
        $request->validate([
            'field_mapping' => 'required|array',
            'update_existing' => 'boolean',
            'label_ids' => 'array',
            'label_ids.*' => 'integer|exists:chat_labels,id',
        ]);

        $filePath = session('import_file_path');

        if (! $filePath || ! file_exists($filePath)) {
            return response()->json([
                'success' => false,
                'message' => 'File not found. Please upload the file again.',
            ], 400);
        }

        try {
            $accountId = Auth::user()->account_id;
            $options = [
                'update_existing' => $request->boolean('update_existing'),
                'label_ids' => $request->input('label_ids', []),
            ];

            $preview = $this->importService->previewImport(
                $filePath,
                $request->input('field_mapping'),
                $accountId,
                $options
            );

            return response()->json([
                'success' => true,
                'preview' => $preview,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Execute import.
     */
    public function execute(Request $request): JsonResponse
    {
        $request->validate([
            'field_mapping' => 'required|array',
            'update_existing' => 'boolean',
            'label_ids' => 'array',
            'label_ids.*' => 'integer|exists:chat_labels,id',
        ]);

        $filePath = session('import_file_path');

        if (! $filePath || ! file_exists($filePath)) {
            return response()->json([
                'success' => false,
                'message' => 'File not found. Please upload the file again.',
            ], 400);
        }

        try {
            $accountId = Auth::user()->account_id;
            $options = [
                'update_existing' => $request->boolean('update_existing'),
                'label_ids' => $request->input('label_ids', []),
            ];

            $results = $this->importService->processImport(
                $filePath,
                $request->input('field_mapping'),
                $accountId,
                $options
            );

            // Clean up uploaded file
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            session()->forget('import_file_path');

            return response()->json([
                'success' => true,
                'message' => 'Import completed successfully',
                'results' => $results,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download CSV template.
     */
    public function downloadTemplate()
    {
        try {
            $filePath = $this->importService->generateTemplate();

            return response()->download($filePath, 'customer_import_template.csv', [
                'Content-Type' => 'text/csv',
            ])->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['message' => 'Failed to generate template: '.$e->getMessage()]);
        }
    }
}
