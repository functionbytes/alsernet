<?php

namespace Modules\Supplier\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Modules\Supplier\Models\Ai\AiContent;
use Modules\Supplier\Models\Characteristic\ModelCharacteristic;
use Modules\Supplier\Models\Characteristic\VariantCharacteristic;
use Modules\Supplier\Models\Product\ProductAttribute;
use Modules\Supplier\Services\Integrations\ErpCharacteristicAssignmentService;

/**
 * Sincroniza con el ERP las características pendientes (nivel modelo y
 * nivel variante) asociadas al producto de un contenido aprobado.
 * Se dispara siempre tras aprobar; es barato si no hay nada pendiente.
 */
class SyncCharacteristicsToErpJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 120, 300];

    public function __construct(public readonly string $contentUid)
    {
        $this->onQueue('sync');
    }

    public function handle(ErpCharacteristicAssignmentService $service): void
    {
        $content = AiContent::where('uid', $this->contentUid)
            ->with('supplierProduct.attributes')
            ->first();

        if (! $content || ! $content->supplierProduct) {
            Log::warning('SyncCharacteristicsToErpJob: content or supplierProduct not found', ['uid' => $this->contentUid]);

            return;
        }

        $idModelo = $content->supplierProduct?->erp_id
            ?? ($content->source_attributes['id'] ?? null)
            ?? $content->erp_reference;

        if (! $idModelo) {
            Log::warning('SyncCharacteristicsToErpJob: missing idModelo', ['uid' => $this->contentUid]);

            return;
        }

        [$modelSynced, $modelFailed] = $this->syncModelCharacteristics($service, $content->supplierProduct->id, (int) $idModelo);
        [$variantSynced, $variantFailed] = $this->syncVariantCharacteristics($service, $content->supplierProduct->attributes);
        [$productSynced, $productFailed] = $this->syncProductLevelCharacteristics($service, $content->supplierProduct->id);

        Log::info('SyncCharacteristicsToErpJob: sync summary', [
            'content_uid' => $this->contentUid,
            'model_synced' => $modelSynced,
            'model_failed' => $modelFailed,
            'variant_synced' => $variantSynced + $productSynced,
            'variant_failed' => $variantFailed + $productFailed,
        ]);
    }

    /**
     * @return array{0: int, 1: int} [synced, failed]
     */
    private function syncModelCharacteristics(ErpCharacteristicAssignmentService $service, int $productId, int $idModelo): array
    {
        $synced = 0;
        $failed = 0;

        ModelCharacteristic::where('product_id', $productId)
            ->pending()
            ->with('characteristic')
            ->get()
            ->each(function (ModelCharacteristic $item) use ($service, $idModelo, &$synced, &$failed) {
                if (! $item->characteristic) {
                    return;
                }

                $result = $service->assignToModel($item->characteristic->erp_id, $idModelo);

                $item->update([
                    'sync_status' => $result['success'] ? 'synced' : 'error',
                    'erp_response' => json_encode($result),
                    'last_sync_at' => now(),
                ]);

                $result['success'] ? $synced++ : $failed++;
            });

        return [$synced, $failed];
    }

    /**
     * @param  Collection<int, ProductAttribute>  $attributes
     * @return array{0: int, 1: int} [synced, failed]
     */
    private function syncVariantCharacteristics(ErpCharacteristicAssignmentService $service, $attributes): array
    {
        $synced = 0;
        $failed = 0;

        foreach ($attributes as $attribute) {
            VariantCharacteristic::where('product_attribute_id', $attribute->id)
                ->pending()
                ->with(['characteristic', 'value'])
                ->get()
                ->each(function (VariantCharacteristic $item) use ($service, $attribute, &$synced, &$failed) {
                    if (! $item->characteristic || ! $item->value) {
                        return;
                    }

                    $result = $service->assignToArticle($item->characteristic->erp_id, $item->value->erp_id, $attribute->erp_id);

                    $item->update([
                        'sync_status' => $result['success'] ? 'synced' : 'error',
                        'erp_response' => json_encode($result),
                        'last_sync_at' => now(),
                    ]);

                    $result['success'] ? $synced++ : $failed++;
                });
        }

        return [$synced, $failed];
    }

    /**
     * Productos SIN variantes tratados como su propio artículo
     * (product_attribute_id null, product_id = este producto).
     *
     * @return array{0: int, 1: int} [synced, failed]
     */
    private function syncProductLevelCharacteristics(ErpCharacteristicAssignmentService $service, int $productId): array
    {
        $synced = 0;
        $failed = 0;

        VariantCharacteristic::whereNull('product_attribute_id')
            ->where('product_id', $productId)
            ->pending()
            ->with(['characteristic', 'value'])
            ->get()
            ->each(function (VariantCharacteristic $item) use ($service, &$synced, &$failed) {
                if (! $item->characteristic || ! $item->value || ! $item->erp_article_id) {
                    return;
                }

                $result = $service->assignToArticle($item->characteristic->erp_id, $item->value->erp_id, $item->erp_article_id);

                $item->update([
                    'sync_status' => $result['success'] ? 'synced' : 'error',
                    'erp_response' => json_encode($result),
                    'last_sync_at' => now(),
                ]);

                $result['success'] ? $synced++ : $failed++;
            });

        return [$synced, $failed];
    }

    public function failed(\Throwable $e): void
    {
        Log::error('SyncCharacteristicsToErpJob permanently failed', [
            'content_uid' => $this->contentUid,
            'error' => $e->getMessage(),
        ]);
    }
}
