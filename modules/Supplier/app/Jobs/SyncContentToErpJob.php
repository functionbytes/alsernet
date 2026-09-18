<?php

namespace Modules\Supplier\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Core\Models\Setting;
use Modules\Supplier\Models\Ai\AiContent;

/**
 * Pushes the AI-generated description to the ERP after a content is approved.
 * This marks the product as having a description in Oracle so it no longer
 * appears in the description_empty=1 filter on subsequent syncs.
 */
class SyncContentToErpJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 120, 300];

    public function __construct(public readonly string $contentUid)
    {
        $this->onQueue('sync');
    }

    public function handle(): void
    {
        $content = AiContent::where('uid', $this->contentUid)
            ->with('supplierProduct')
            ->first();

        if (! $content) {
            Log::warning('SyncContentToErpJob: content not found', ['uid' => $this->contentUid]);

            return;
        }

        $erpUrl = Setting::get('supplier.erp_modelo_url', 'http://interges:8080/api-gestion/modelo/');

        $erpId = $content->supplierProduct?->erp_id
            ?? ($content->source_attributes['id'] ?? null)
            ?? $content->erp_reference;

        if (! $erpId || ! $erpUrl) {
            Log::warning('SyncContentToErpJob: missing erpId or erpUrl', ['uid' => $this->contentUid]);

            return;
        }

        // El servidor de escritura del ERP usa name-based virtual hosting: sin forzar
        // el header Host exacto (sin puerto), Apache enruta a un vhost por defecto que
        // responde 200/404 falsos según la ruta en vez del vhost real de api-gestion.
        $response = Http::timeout(15)
            ->withHeaders(['Host' => parse_url($erpUrl, PHP_URL_HOST)])
            ->asForm()
            ->post($erpUrl, [
                'idmodelo' => (string) $erpId,
                'nombre' => $content->generated_name ?? $content->supplierProduct?->name ?? '',
                'descripcion' => $content->long_description ?? '',
                'publicar' => 0,
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException(
                "ERP responded {$response->status()} for model {$erpId}: {$response->body()}"
            );
        }

        $content->update(['synced_to_erp_at' => now()]);

        Log::info('SyncContentToErpJob: description pushed to ERP', [
            'content_uid' => $this->contentUid,
            'erp_id' => $erpId,
        ]);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('SyncContentToErpJob permanently failed', [
            'content_uid' => $this->contentUid,
            'error' => $e->getMessage(),
        ]);
    }
}
