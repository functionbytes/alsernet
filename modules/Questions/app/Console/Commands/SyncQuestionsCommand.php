<?php

namespace Modules\Questions\Console\Commands;

use Illuminate\Console\Command;
use Modules\Questions\Services\PrestashopQuestionClient;
use Modules\Questions\Services\QuestionIngestor;

/**
 * Trae de la tienda las consultas que el panel no tiene.
 */
class SyncQuestionsCommand extends Command
{
    protected $signature = 'questions:sync
                            {--limit=100 : Consultas por lote}
                            {--pages=0 : Lotes a recorrer; 0 = hasta agotarlas}
                            {--since=0 : Reanudar a partir de este identificador}
                            {--answered : Solo las que ya tienen respuesta}';

    protected $description = 'Importa desde PrestaShop las consultas de producto que falten en el panel';

    public function handle(PrestashopQuestionClient $tienda, QuestionIngestor $ingestor): int
    {
        if (! $tienda->isConfigured()) {
            $this->error('Falta configurar ALSERNETQUESTIONS_API_URL y ALSERNETQUESTIONS_SECRET.');

            return self::FAILURE;
        }

        $limit = (int) $this->option('limit');
        $maxPages = (int) $this->option('pages');
        $sinceId = (int) $this->option('since');
        $total = 0;
        $pagina = 0;

        while (true) {
            if ($maxPages > 0 && $pagina >= $maxPages) {
                break;
            }

            $respuesta = $tienda->listHistory($sinceId, $limit, (bool) $this->option('answered'));

            if (empty($respuesta['ok'])) {
                $this->error('La tienda respondió con error: '.($respuesta['error'] ?? 'desconocido'));

                return self::FAILURE;
            }

            $items = (array) ($respuesta['data'] ?? []);

            if (! $items) {
                break;
            }

            foreach ($items as $item) {
                $id = (int) ($item['id_question'] ?? 0);

                try {
                    if ($ingestor->upsert($item)) {
                        $total++;
                    }
                } catch (\Throwable $e) {
                    // Una fila con datos imposibles no puede parar el recorrido.
                    $this->warn('  #'.$id.' descartada: '.mb_substr($e->getMessage(), 0, 110));
                }

                $sinceId = max($sinceId, $id);
            }

            $pagina++;
            $this->line(sprintf('  lote %d: %d recibidas, hasta la #%d', $pagina, count($items), $sinceId));
        }

        $this->info($total.' consultas registradas o actualizadas.');

        return self::SUCCESS;
    }
}
