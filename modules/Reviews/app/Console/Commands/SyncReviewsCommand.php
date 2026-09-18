<?php

namespace Modules\Reviews\Console\Commands;

use Illuminate\Console\Command;
use Modules\Reviews\Services\PrestashopReviewClient;
use Modules\Reviews\Services\ReviewIngestor;

/**
 * Trae de la tienda las opiniones que el panel no tiene.
 *
 * Sirve para la carga inicial y para reconciliar cuando el webhook se pierde
 * algo: la bandeja de salida reintenta, pero si se agotan los intentos la
 * opinión se queda solo en PrestaShop.
 */
class SyncReviewsCommand extends Command
{
    protected $signature = 'reviews:sync
                            {--entity=all : product, store o all}
                            {--limit=100 : Opiniones por lote}
                            {--pages=0 : Lotes a recorrer; 0 = hasta agotarlas}
                            {--since=0 : Reanudar a partir de este identificador}';

    protected $description = 'Importa desde PrestaShop el histórico de opiniones que falte en el panel';

    public function handle(PrestashopReviewClient $tienda, ReviewIngestor $ingestor): int
    {
        if (! $tienda->isConfigured()) {
            $this->error('Falta configurar ALSERNETREVIEWS_API_URL y ALSERNETREVIEWS_SECRET.');

            return self::FAILURE;
        }

        $entidades = match ($this->option('entity')) {
            'product' => ['product'],
            'store' => ['store'],
            default => ['product', 'store'],
        };

        $limit = (int) $this->option('limit');
        $maxPages = (int) $this->option('pages');
        $totalGeneral = 0;

        foreach ($entidades as $entity) {
            $this->info($entity === 'product' ? 'Opiniones de producto' : 'Opiniones de tienda');

            $sinceId = (int) $this->option('since');
            $total = 0;
            $pagina = 0;
            $barra = null;

            while (true) {
                if ($maxPages > 0 && $pagina >= $maxPages) {
                    break;
                }

                $respuesta = $tienda->listHistory($entity, $sinceId, $limit);

                if (empty($respuesta['ok'])) {
                    $this->error('  La tienda respondió con error: '.($respuesta['error'] ?? 'desconocido'));

                    return self::FAILURE;
                }

                $items = (array) ($respuesta['data'] ?? []);

                if (! $items) {
                    break;
                }

                foreach ($items as $item) {
                    $id = (int) ($item['id_productcomment'] ?? $item['id_storecomment'] ?? 0);

                    try {
                        if ($ingestor->upsert($item)) {
                            $total++;
                        }
                    } catch (\Throwable $e) {
                        // Una fila con datos imposibles no puede detener el
                        // recorrido: se anota y se sigue.
                        $this->warn('  #'.$id.' descartada: '.mb_substr($e->getMessage(), 0, 120));
                    }

                    $sinceId = max($sinceId, $id);
                }

                $pagina++;
                $this->line(sprintf('  lote %d: %d recibidas, hasta la #%d', $pagina, count($items), $sinceId));
            }

            $this->info('  '.$total.' registradas o actualizadas.');
            $totalGeneral += $total;
        }

        $this->newLine();
        $this->info($totalGeneral.' opiniones en total.');

        return self::SUCCESS;
    }
}
