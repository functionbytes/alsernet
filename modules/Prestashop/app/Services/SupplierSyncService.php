<?php

namespace Modules\Prestashop\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\ImageManager;
use Modules\Prestashop\Entities\Product;
use Modules\Supplier\Entities\Supplier;
use Modules\Supplier\Entities\SupplierAiContent;
use Modules\Supplier\Entities\SupplierProductImage;
use Modules\Supplier\Services\ImageCompressionService;
use Throwable;

/**
 * SyncService - Handles synchronization of AI-generated content to PrestaShop and ERP
 *
 * This service manages:
 * - PrestaShop product synchronization via Web Service API
 * - ERP integration for product data
 * - Image processing and upload
 * - Conflict resolution strategies
 * - Publication scheduling and rollback
 * - Comprehensive logging and event dispatching
 */
class SyncService
{
    protected const MAX_RETRY_ATTEMPTS = 3;

    protected const RETRY_DELAY_SECONDS = 5;

    protected const IMAGE_TIMEOUT_SECONDS = 60;

    protected const API_TIMEOUT_SECONDS = 30;

    protected const CONFLICT_STRATEGY_OVERWRITE = 'overwrite';

    protected const CONFLICT_STRATEGY_MERGE = 'merge';

    protected const CONFLICT_STRATEGY_SKIP = 'skip';

    protected const CONFLICT_STRATEGY_CREATE_NEW = 'create_new';

    protected const SYNC_STATUS_PENDING = 'pending';

    protected const SYNC_STATUS_IN_PROGRESS = 'in_progress';

    protected const SYNC_STATUS_SUCCESS = 'success';

    protected const SYNC_STATUS_FAILED = 'failed';

    protected const SYNC_STATUS_PARTIAL = 'partial';

    protected ImageManager $imageManager;

    protected ImageCompressionService $imageCompressor;

    public function __construct()
    {
        $this->imageManager = new ImageManager(new GdDriver);
        $this->imageCompressor = new ImageCompressionService;
    }

    /**
     * Sync single content to PrestaShop
     */
    public function syncToPrestaShop(SupplierAiContent $content): bool
    {
        $this->updateSyncStatus($content, self::SYNC_STATUS_IN_PROGRESS);

        try {
            DB::beginTransaction();

            // Validate content is ready for sync
            if (! $this->validateContentForSync($content)) {
                throw new \RuntimeException('Content validation failed for sync');
            }

            // Map content to PrestaShop format
            $productData = $this->mapContentToPrestaShopFormat($content);

            // Sync to PrestaShop via API or direct DB
            if ($this->isWebServiceEnabled()) {
                $result = $this->syncViaWebService($content, $productData);
            } else {
                $result = $this->syncViaDirectDB($content, $productData);
            }

            if (! $result) {
                throw new \RuntimeException('PrestaShop sync operation failed');
            }

            // Sync images
            $imageSyncResult = $this->syncImages($content);
            if (! empty($imageSyncResult['failed'])) {
                Log::warning('Some images failed to sync', [
                    'content_id' => $content->id,
                    'failed_count' => count($imageSyncResult['failed']),
                ]);
            }

            // Update content sync timestamp
            $content->update(['synced_to_prestashop_at' => now()]);

            // Log success
            $content->log(
                SupplierAiContent::ACTION_SYNCED_TO_ERP,
                null,
                null,
                [
                    'platform' => 'prestashop',
                    'product_id' => $content->product_id,
                    'images_synced' => count($imageSyncResult['success'] ?? []),
                ]
            );

            DB::commit();

            // Dispatch event
            Event::dispatch('supplier.content.synced', [
                'content' => $content,
                'platform' => 'prestashop',
            ]);

            $this->updateSyncStatus($content, self::SYNC_STATUS_SUCCESS);

            return true;

        } catch (Throwable $e) {
            DB::rollBack();

            $errorMessage = "PrestaShop sync failed: {$e->getMessage()}";
            Log::error($errorMessage, [
                'content_id' => $content->id,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->updateSyncStatus($content, self::SYNC_STATUS_FAILED, $errorMessage);

            return false;
        }
    }

    /**
     * Sync batch of content to PrestaShop
     */
    public function syncBatchToPrestaShop(array $contentIds): array
    {
        $results = [
            'total' => count($contentIds),
            'success' => [],
            'failed' => [],
            'skipped' => [],
        ];

        foreach ($contentIds as $contentId) {
            try {
                $content = SupplierAiContent::findOrFail($contentId);

                // Check if content is ready for sync
                if (! $content->isValidated() && ! $content->isPublished()) {
                    $results['skipped'][] = [
                        'id' => $contentId,
                        'reason' => 'Content not validated',
                    ];

                    continue;
                }

                $success = $this->syncToPrestaShop($content);

                if ($success) {
                    $results['success'][] = $contentId;
                } else {
                    $results['failed'][] = [
                        'id' => $contentId,
                        'error' => $content->error_message ?? 'Unknown error',
                    ];
                }

            } catch (Throwable $e) {
                $results['failed'][] = [
                    'id' => $contentId,
                    'error' => $e->getMessage(),
                ];

                Log::error('Batch sync error', [
                    'content_id' => $contentId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Batch PrestaShop sync completed', $results);

        return $results;
    }

    /**
     * Update existing PrestaShop product
     */
    public function updatePrestaShopProduct(int $productId, array $data): bool
    {
        try {
            if ($this->isWebServiceEnabled()) {
                return $this->updateProductViaWebService($productId, $data);
            } else {
                return $this->updateProductViaDirectDB($productId, $data);
            }
        } catch (Throwable $e) {
            Log::error('PrestaShop product update failed', [
                'product_id' => $productId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Publish content (mark as published and optionally sync)
     */
    public function publishContent(SupplierAiContent $content): void
    {
        DB::transaction(function () use ($content) {
            // Mark as published
            $content->publish();

            // Auto-sync if enabled
            if ($this->shouldAutoSync($content)) {
                $this->syncToPrestaShop($content);
            }

            Event::dispatch('supplier.content.published', ['content' => $content]);
        });
    }

    /**
     * Publish batch of content
     */
    public function publishBatch(array $contentIds): array
    {
        $results = [
            'total' => count($contentIds),
            'published' => [],
            'failed' => [],
        ];

        foreach ($contentIds as $contentId) {
            try {
                $content = SupplierAiContent::findOrFail($contentId);

                $this->publishContent($content);
                $results['published'][] = $contentId;

            } catch (Throwable $e) {
                $results['failed'][] = [
                    'id' => $contentId,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Schedule content publication
     */
    public function schedulePublication(SupplierAiContent $content, Carbon $scheduledAt): void
    {
        $content->update([
            'scheduled_publish_at' => $scheduledAt,
        ]);

        $content->log('scheduled_publication', null, null, [
            'scheduled_at' => $scheduledAt->toIso8601String(),
        ]);

        Event::dispatch('supplier.content.scheduled', [
            'content' => $content,
            'scheduled_at' => $scheduledAt,
        ]);
    }

    /**
     * Sync to ERP system
     */
    public function syncToErp(SupplierAiContent $content): bool
    {
        try {
            $erpData = $this->mapContentToErpFormat($content);

            // Send to ERP API
            $response = $this->sendToErpApi($erpData);

            if ($response['success'] ?? false) {
                $content->syncToErp();

                Event::dispatch('supplier.content.synced_to_erp', [
                    'content' => $content,
                    'erp_reference' => $response['reference'] ?? null,
                ]);

                return true;
            }

            return false;

        } catch (Throwable $e) {
            Log::error('ERP sync failed', [
                'content_id' => $content->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Map content to ERP format
     */
    public function mapContentToErpFormat(SupplierAiContent $content): array
    {
        return [
            'reference' => $content->erp_reference,
            'model_id' => $content->model_id,
            'ean' => $content->ean,
            'name' => $content->generated_name,
            'short_description' => $content->short_description,
            'long_description' => $content->long_description,
            'supplier_id' => $content->supplier->erp_id,
            'source_attributes' => $content->source_attributes,
            'metadata' => [
                'ai_generated' => true,
                'validated_at' => $content->validated_at?->toIso8601String(),
                'prompt_id' => $content->prompt_id,
            ],
        ];
    }

    /**
     * Sync images for content
     */
    public function syncImages(SupplierAiContent $content): array
    {
        $results = [
            'success' => [],
            'failed' => [],
        ];

        // Get images from extraction results or source
        $images = $this->getContentImages($content);

        foreach ($images as $imageData) {
            try {
                // Download image
                $localPath = $this->downloadImage($imageData['url'], $this->generateImagePath($content, $imageData));

                if (! $localPath) {
                    $results['failed'][] = [
                        'url' => $imageData['url'],
                        'error' => 'Download failed',
                    ];

                    continue;
                }

                // Process image (resize, optimize)
                $processedPath = $this->processImage($localPath);

                // Upload to PrestaShop
                if ($content->product_id) {
                    $uploaded = $this->uploadToPrestaShop($content->product_id, $processedPath);

                    if ($uploaded) {
                        $results['success'][] = [
                            'url' => $imageData['url'],
                            'local_path' => $processedPath,
                            'product_id' => $content->product_id,
                        ];
                    } else {
                        $results['failed'][] = [
                            'url' => $imageData['url'],
                            'error' => 'Upload to PrestaShop failed',
                        ];
                    }
                }

            } catch (Throwable $e) {
                $results['failed'][] = [
                    'url' => $imageData['url'] ?? 'unknown',
                    'error' => $e->getMessage(),
                ];

                Log::error('Image sync failed', [
                    'content_id' => $content->id,
                    'image_url' => $imageData['url'] ?? null,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Download image from URL
     */
    public function downloadImage(string $url, string $destinationPath): bool
    {
        try {
            $response = Http::timeout(self::IMAGE_TIMEOUT_SECONDS)
                ->retry(self::MAX_RETRY_ATTEMPTS, self::RETRY_DELAY_SECONDS)
                ->get($url);

            if ($response->successful()) {
                Storage::put($destinationPath, $response->body());

                return true;
            }

            return false;

        } catch (Throwable $e) {
            Log::error('Image download failed', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Upload image to PrestaShop
     */
    public function uploadToPrestaShop(int $productId, string $imagePath): bool
    {
        try {
            if (! Storage::exists($imagePath)) {
                return false;
            }

            if ($this->isWebServiceEnabled()) {
                return $this->uploadImageViaWebService($productId, $imagePath);
            } else {
                return $this->uploadImageViaDirectDB($productId, $imagePath);
            }

        } catch (Throwable $e) {
            Log::error('PrestaShop image upload failed', [
                'product_id' => $productId,
                'image_path' => $imagePath,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Detect conflicts before sync
     */
    public function detectConflicts(SupplierAiContent $content): array
    {
        $conflicts = [];

        // Check if product exists in PrestaShop
        if ($content->product_id) {
            $existingProduct = $this->getPrestaShopProduct($content->product_id);

            if ($existingProduct) {
                // Check for content differences
                if ($existingProduct->name !== $content->generated_name) {
                    $conflicts[] = [
                        'type' => 'name_mismatch',
                        'field' => 'name',
                        'current' => $existingProduct->name,
                        'new' => $content->generated_name,
                    ];
                }

                if ($existingProduct->description !== $content->long_description) {
                    $conflicts[] = [
                        'type' => 'description_mismatch',
                        'field' => 'description',
                        'current' => $existingProduct->description,
                        'new' => $content->long_description,
                    ];
                }
            }
        }

        // Check for duplicate EAN
        if ($content->ean) {
            $duplicateProduct = $this->findProductByEan($content->ean);
            if ($duplicateProduct && $duplicateProduct->id_product !== $content->product_id) {
                $conflicts[] = [
                    'type' => 'duplicate_ean',
                    'field' => 'ean',
                    'existing_product_id' => $duplicateProduct->id_product,
                    'ean' => $content->ean,
                ];
            }
        }

        return $conflicts;
    }

    /**
     * Resolve conflict using specified strategy
     */
    public function resolveConflict(SupplierAiContent $content, string $strategy): void
    {
        switch ($strategy) {
            case self::CONFLICT_STRATEGY_OVERWRITE:
                $this->syncToPrestaShop($content);
                break;

            case self::CONFLICT_STRATEGY_MERGE:
                $this->mergeWithExisting($content);
                break;

            case self::CONFLICT_STRATEGY_SKIP:
                $content->log('conflict_skipped', null, null, [
                    'strategy' => $strategy,
                    'conflicts' => $this->detectConflicts($content),
                ]);
                break;

            case self::CONFLICT_STRATEGY_CREATE_NEW:
                $this->createNewProduct($content);
                break;

            default:
                throw new \InvalidArgumentException("Unknown conflict strategy: {$strategy}");
        }
    }

    /**
     * Rollback publication
     */
    public function rollbackPublication(SupplierAiContent $content): void
    {
        DB::transaction(function () use ($content) {
            // Get previous state from history
            $previousState = $this->getPreviousPublishedState($content);

            if ($previousState) {
                // Restore previous data to PrestaShop
                if ($content->product_id) {
                    $this->updatePrestaShopProduct($content->product_id, $previousState);
                }
            } else {
                // Remove from PrestaShop if it was newly created
                if ($content->product_id) {
                    $this->deleteFromPrestaShop($content->product_id);
                }
            }

            // Update content status
            $content->update([
                'published_at' => null,
                'status' => SupplierAiContent::STATUS_VALIDATED,
            ]);

            $content->log('publication_rolled_back', null, null, [
                'rollback_at' => now()->toIso8601String(),
                'previous_state' => $previousState,
            ]);

            Event::dispatch('supplier.content.rollback', ['content' => $content]);
        });
    }

    /**
     * Get publication history
     */
    public function getPublicationHistory(SupplierAiContent $content): array
    {
        return $content->logs()
            ->whereIn('action', [
                SupplierAiContent::ACTION_PUBLISHED,
                'publication_rolled_back',
                'scheduled_publication',
            ])
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($log) {
                return [
                    'action' => $log->action,
                    'user' => $log->user?->name ?? 'System',
                    'timestamp' => $log->created_at,
                    'details' => $log->details,
                ];
            })
            ->toArray();
    }

    /**
     * Update sync status
     */
    public function updateSyncStatus(SupplierAiContent $content, string $status, ?string $error = null): void
    {
        $content->update([
            'sync_status' => $status,
            'sync_error' => $error,
            'last_sync_attempt' => now(),
        ]);

        if ($error) {
            $content->log('sync_failed', null, null, [
                'error' => $error,
                'status' => $status,
            ]);
        }
    }

    /**
     * Get sync statistics for supplier
     */
    public function getSyncStatistics(int $supplierId, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $query = SupplierAiContent::where('supplier_id', $supplierId);

        if ($from) {
            $query->where('created_at', '>=', $from);
        }

        if ($to) {
            $query->where('created_at', '<=', $to);
        }

        $total = $query->count();
        $published = (clone $query)->whereNotNull('published_at')->count();
        $syncedToPrestaShop = (clone $query)->whereNotNull('synced_to_prestashop_at')->count();
        $syncedToErp = (clone $query)->whereNotNull('synced_to_erp_at')->count();

        $avgSyncTime = $query->whereNotNull('synced_to_prestashop_at')
            ->whereNotNull('published_at')
            ->get()
            ->map(fn ($content) => $content->synced_to_prestashop_at->diffInSeconds($content->published_at))
            ->avg();

        return [
            'total_content' => $total,
            'published' => $published,
            'synced_to_prestashop' => $syncedToPrestaShop,
            'synced_to_erp' => $syncedToErp,
            'publish_rate' => $total > 0 ? round(($published / $total) * 100, 2) : 0,
            'sync_rate' => $published > 0 ? round(($syncedToPrestaShop / $published) * 100, 2) : 0,
            'avg_sync_time_seconds' => $avgSyncTime ? round($avgSyncTime) : null,
        ];
    }

    // ========================================================================
    // Protected Helper Methods
    // ========================================================================

    /**
     * Validate content is ready for sync
     */
    protected function validateContentForSync(SupplierAiContent $content): bool
    {
        if (! $content->isValidated() && ! $content->isPublished()) {
            Log::warning('Content not validated for sync', ['content_id' => $content->id]);

            return false;
        }

        if (empty($content->generated_name)) {
            Log::warning('Content missing generated name', ['content_id' => $content->id]);

            return false;
        }

        return true;
    }

    /**
     * Map content to PrestaShop format
     */
    protected function mapContentToPrestaShopFormat(SupplierAiContent $content): array
    {
        return [
            'id_product' => $content->product_id,
            'id_supplier' => $content->supplier->supplier_id,
            'reference' => $content->erp_reference ?? $content->model_id,
            'ean13' => $content->ean,
            'name' => $content->generated_name,
            'description' => $content->long_description,
            'description_short' => $content->short_description,
            'meta_title' => $content->seo_title,
            'meta_description' => $content->seo_description,
            'meta_keywords' => $content->seo_keywords,
            'active' => 1,
            'date_add' => $content->created_at?->format('Y-m-d H:i:s'),
            'date_upd' => now()->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Check if Web Service is enabled
     */
    protected function isWebServiceEnabled(): bool
    {
        return ! empty(config('prestashop.api_key'));
    }

    /**
     * Sync via PrestaShop Web Service
     */
    protected function syncViaWebService(SupplierAiContent $content, array $productData): bool
    {
        $apiKey = config('prestashop.api_key');
        $apiUrl = rtrim(config('prestashop.url'), '/').'/api/inventaries';

        try {
            $response = Http::timeout(self::API_TIMEOUT_SECONDS)
                ->withBasicAuth($apiKey, '')
                ->retry(self::MAX_RETRY_ATTEMPTS, self::RETRY_DELAY_SECONDS)
                ->asForm()
                ->post($apiUrl, ['product' => $productData]);

            if ($response->successful()) {
                $responseData = $response->json();
                $productId = $responseData['product']['id'] ?? null;

                if ($productId) {
                    $content->update(['product_id' => $productId]);

                    return true;
                }
            }

            Log::error('PrestaShop API sync failed', [
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return false;

        } catch (Throwable $e) {
            Log::error('PrestaShop Web Service error', [
                'error' => $e->getMessage(),
                'content_id' => $content->id,
            ]);

            return false;
        }
    }

    /**
     * Sync via direct database connection
     */
    protected function syncViaDirectDB(SupplierAiContent $content, array $productData): bool
    {
        try {
            if ($content->product_id) {
                // Update existing product
                $product = Product::on('prestashop')->find($content->product_id);
                if ($product) {
                    $product->update($productData);

                    return true;
                }
            } else {
                // Create new product
                $product = Product::on('prestashop')->create($productData);
                $content->update(['product_id' => $product->id_product]);

                return true;
            }

            return false;

        } catch (Throwable $e) {
            Log::error('PrestaShop direct DB sync failed', [
                'error' => $e->getMessage(),
                'content_id' => $content->id,
            ]);

            return false;
        }
    }

    /**
     * Update product via Web Service
     */
    protected function updateProductViaWebService(int $productId, array $data): bool
    {
        $apiKey = config('prestashop.api_key');
        $apiUrl = rtrim(config('prestashop.url'), '/')."/api/inventaries/{$productId}";

        try {
            $response = Http::timeout(self::API_TIMEOUT_SECONDS)
                ->withBasicAuth($apiKey, '')
                ->retry(self::MAX_RETRY_ATTEMPTS, self::RETRY_DELAY_SECONDS)
                ->asForm()
                ->put($apiUrl, ['product' => $data]);

            return $response->successful();

        } catch (Throwable $e) {
            Log::error('PrestaShop product update via API failed', [
                'product_id' => $productId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Update product via direct database
     */
    protected function updateProductViaDirectDB(int $productId, array $data): bool
    {
        try {
            $product = Product::on('prestashop')->find($productId);

            if ($product) {
                return $product->update($data);
            }

            return false;

        } catch (Throwable $e) {
            Log::error('PrestaShop product update via DB failed', [
                'product_id' => $productId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Check if auto-sync is enabled
     */
    protected function shouldAutoSync(SupplierAiContent $content): bool
    {
        return config('prestashop.sync_enabled', false)
            && config('prestashop.sync_products', true);
    }

    /**
     * Send data to ERP API
     */
    protected function sendToErpApi(array $data): array
    {
        // Implement ERP API integration
        // This is a placeholder - adjust based on your ERP system
        try {
            $response = Http::timeout(self::API_TIMEOUT_SECONDS)
                ->retry(self::MAX_RETRY_ATTEMPTS, self::RETRY_DELAY_SECONDS)
                ->post(config('erp.api_url').'/inventaries', $data);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'reference' => $response->json('reference'),
                    'response' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'error' => $response->body(),
            ];

        } catch (Throwable $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get content images from various sources
     */
    protected function getContentImages(SupplierAiContent $content): array
    {
        $images = [];

        // Get from supplier product images
        $productImages = SupplierProductImage::where('supplier_id', $content->supplier_id)
            ->where('reference', $content->model_id)
            ->completed()
            ->ordered()
            ->get();

        foreach ($productImages as $image) {
            $images[] = [
                'url' => $image->source_url ?? $image->cdn_url,
                'is_primary' => $image->is_primary,
                'position' => $image->position,
            ];
        }

        // Fallback to source attributes if available
        if (empty($images) && ! empty($content->source_attributes['images'])) {
            foreach ($content->source_attributes['images'] as $index => $url) {
                $images[] = [
                    'url' => $url,
                    'is_primary' => $index === 0,
                    'position' => $index,
                ];
            }
        }

        return $images;
    }

    /**
     * Generate unique image path
     */
    protected function generateImagePath(SupplierAiContent $content, array $imageData): string
    {
        $extension = pathinfo(parse_url($imageData['url'], PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
        $filename = sprintf(
            'supplier_%d_content_%d_img_%d.%s',
            $content->supplier_id,
            $content->id,
            $imageData['position'] ?? 0,
            $extension
        );

        return "suppliers/{$content->supplier_id}/images/{$filename}";
    }

    /**
     * Process image (resize + compress without quality loss)
     */
    protected function processImage(string $localPath): string
    {
        $fullPath = Storage::path($localPath);

        // Scale down oversized images using Intervention Image v4
        try {
            $image = $this->imageManager->decodePath($fullPath);

            if ($image->width() > 1200 || $image->height() > 1200) {
                $image->scaleDown(width: 1200, height: 1200);

                // Build processed path: replace only the last extension
                $ext = strtolower(pathinfo($localPath, PATHINFO_EXTENSION));
                $processedPath = preg_replace('/\.[^.]+$/', '_r.'.$ext, $localPath);
                $image->save(Storage::path($processedPath));
                $localPath = $processedPath;
                $fullPath = Storage::path($localPath);
            }
        } catch (Throwable $e) {
            Log::warning('Image resize failed, skipping resize step', [
                'path' => $localPath,
                'error' => $e->getMessage(),
            ]);
        }

        // Apply maximum lossless compression (jpegoptim / optipng / pngquant / cwebp)
        $stats = $this->imageCompressor->compress($fullPath);

        if ($stats['original'] > 0 && $stats['compressed'] < $stats['original']) {
            $saved = $stats['original'] - $stats['compressed'];
            $pct = round($saved / $stats['original'] * 100, 1);
            Log::info('Image compressed', [
                'path' => $localPath,
                'original_kb' => round($stats['original'] / 1024, 1),
                'compressed_kb' => round($stats['compressed'] / 1024, 1),
                'saved_pct' => $pct,
            ]);
        }

        return $localPath;
    }

    /**
     * Upload image via Web Service
     */
    protected function uploadImageViaWebService(int $productId, string $imagePath): bool
    {
        $apiKey = config('prestashop.api_key');
        $apiUrl = rtrim(config('prestashop.url'), '/')."/api/images/inventaries/{$productId}";

        try {
            $response = Http::timeout(self::IMAGE_TIMEOUT_SECONDS)
                ->withBasicAuth($apiKey, '')
                ->attach('image', Storage::get($imagePath), basename($imagePath))
                ->post($apiUrl);

            return $response->successful();

        } catch (Throwable $e) {
            Log::error('Image upload via API failed', [
                'product_id' => $productId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Upload image via direct database/filesystem
     */
    protected function uploadImageViaDirectDB(int $productId, string $imagePath): bool
    {
        // This would require direct filesystem access to PrestaShop
        // Implement based on your PrestaShop installation setup
        Log::warning('Direct DB image upload not implemented', [
            'product_id' => $productId,
            'image_path' => $imagePath,
        ]);

        return false;
    }

    /**
     * Get PrestaShop product by ID
     */
    protected function getPrestaShopProduct(int $productId): ?Product
    {
        try {
            return Product::on('prestashop')->find($productId);
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * Find product by EAN
     */
    protected function findProductByEan(string $ean): ?Product
    {
        try {
            return Product::on('prestashop')->where('ean13', $ean)->first();
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * Merge content with existing product
     */
    protected function mergeWithExisting(SupplierAiContent $content): void
    {
        if (! $content->product_id) {
            return;
        }

        $existingProduct = $this->getPrestaShopProduct($content->product_id);

        if ($existingProduct) {
            // Merge strategy: keep existing name but update descriptions
            $mergedData = [
                'description' => $content->long_description,
                'description_short' => $content->short_description,
                'meta_title' => $content->seo_title,
                'meta_description' => $content->seo_description,
            ];

            $this->updatePrestaShopProduct($content->product_id, $mergedData);

            $content->log('merged_with_existing', null, null, [
                'merge_fields' => array_keys($mergedData),
            ]);
        }
    }

    /**
     * Create new product instead of updating
     */
    protected function createNewProduct(SupplierAiContent $content): void
    {
        // Clear product_id to force creation
        $oldProductId = $content->product_id;
        $content->update(['product_id' => null]);

        $this->syncToPrestaShop($content);

        $content->log('created_new_product', null, null, [
            'old_product_id' => $oldProductId,
            'new_product_id' => $content->product_id,
        ]);
    }

    /**
     * Get previous published state
     */
    protected function getPreviousPublishedState(SupplierAiContent $content): ?array
    {
        $publishLog = $content->logs()
            ->where('action', SupplierAiContent::ACTION_PUBLISHED)
            ->orderBy('created_at', 'desc')
            ->first();

        return $publishLog?->details['previous_data'] ?? null;
    }

    /**
     * Delete product from PrestaShop
     */
    protected function deleteFromPrestaShop(int $productId): bool
    {
        try {
            if ($this->isWebServiceEnabled()) {
                $apiKey = config('prestashop.api_key');
                $apiUrl = rtrim(config('prestashop.url'), '/')."/api/inventaries/{$productId}";

                $response = Http::withBasicAuth($apiKey, '')
                    ->delete($apiUrl);

                return $response->successful();
            } else {
                $product = Product::on('prestashop')->find($productId);

                return $product?->delete() ?? false;
            }
        } catch (Throwable $e) {
            Log::error('PrestaShop product deletion failed', [
                'product_id' => $productId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
