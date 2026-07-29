<?php

namespace Modules\Supplier\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\Supplier\Services\PromptSelectionService;

/**
 * PromptSelectionApiController
 *
 * API endpoints for n8n to select and retrieve prompts based on product criteria
 *
 * @group AI Content Generation
 */
class PromptSelectionApiController extends Controller
{
    public function __construct(protected PromptSelectionService $promptService) {}

    /**
     * Select the best prompt for given criteria
     *
     * POST /api/suppliers/prompts/select
     *
     * Request body:
     * {
     *   "supplier_id": 123,           // Required: Supplier ID
     *   "category_id": 45,            // Optional: Category ID
     *   "content_type": "description", // Required: description, seo_title, etc.
     *   "source_id": 67,              // Optional: Source ID
     *   "product_data": {             // Optional: Product data for variable replacement
     *     "product_name": "Product Name",
     *     "category": "Category Name",
     *     "price": "99.99",
     *     "description": "Original description...",
     *     ...
     *   }
     * }
     */
    public function select(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'supplier_id' => 'nullable|integer|exists:suppliers,id',
            'category_id' => 'nullable|integer|exists:categories,id',
            'content_type' => 'required|string|in:description,short_description,title,seo_title,seo_description,seo_keywords,metadata,features,specifications,benefits',
            'source_id' => 'nullable|integer|exists:supplier_sources,id',
            'product_data' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $promptData = $this->promptService->prepareForN8n(
                $request->input('supplier_id'),
                $request->input('category_id'),
                $request->input('content_type', 'description'),
                $request->input('product_data', [])
            );

            if (! $promptData) {
                return response()->json([
                    'success' => false,
                    'message' => 'No matching prompt found for the given criteria',
                    'criteria' => $request->only(['supplier_id', 'category_id', 'content_type', 'source_id']),
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $promptData,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error selecting prompt',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Explain prompt selection for debugging
     *
     * GET /api/suppliers/prompts/explain
     *
     * Query params:
     * - supplier_id (optional)
     * - category_id (optional)
     * - content_type (required)
     * - source_id (optional)
     */
    public function explain(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'supplier_id' => 'nullable|integer',
            'category_id' => 'nullable|integer',
            'content_type' => 'required|string',
            'source_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $explanation = $this->promptService->explainSelection(
                $request->input('supplier_id'),
                $request->input('category_id'),
                $request->input('content_type', 'description'),
                $request->input('source_id')
            );

            return response()->json([
                'success' => true,
                'explanation' => $explanation,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error explaining selection',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Batch select prompts for multiple inventaries
     *
     * POST /api/suppliers/prompts/batch-select
     *
     * Request body:
     * {
     *   "inventaries": [
     *     {
     *       "supplier_id": 123,
     *       "category_id": 45,
     *       "content_type": "description",
     *       "product_data": { ... }
     *     },
     *     ...
     *   ]
     * }
     */
    public function batchSelect(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'inventaries' => 'required|array|min:1|max:100',
            'inventaries.*.supplier_id' => 'nullable|integer',
            'inventaries.*.category_id' => 'nullable|integer',
            'inventaries.*.content_type' => 'required|string',
            'inventaries.*.source_id' => 'nullable|integer',
            'inventaries.*.product_data' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $results = [];

            foreach ($request->input('inventaries', []) as $index => $productRequest) {
                $promptData = $this->promptService->prepareForN8n(
                    $productRequest['supplier_id'] ?? null,
                    $productRequest['category_id'] ?? null,
                    $productRequest['content_type'] ?? 'description',
                    $productRequest['product_data'] ?? []
                );

                $results[] = [
                    'index' => $index,
                    'success' => $promptData !== null,
                    'data' => $promptData,
                ];
            }

            $successCount = collect($results)->where('success', true)->count();

            return response()->json([
                'success' => true,
                'total' => count($results),
                'successful' => $successCount,
                'failed' => count($results) - $successCount,
                'results' => $results,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error in batch selection',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Health check endpoint
     *
     * GET /api/suppliers/prompts/health
     */
    public function health(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'service' => 'Prompt Selection Service',
            'status' => 'operational',
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
