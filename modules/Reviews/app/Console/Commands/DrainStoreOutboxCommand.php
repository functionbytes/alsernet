<?php

namespace Modules\Reviews\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Vacía la cola de salida del módulo de la tienda.
 *
 * PrestaShop registra en una tabla de salida cada opinión nueva o cambiada, y
 * su cron.php es quien las empuja al panel. El contenedor de la tienda no lleva
 * demonio cron, así que sin esto las opiniones se quedaban en esa cola y no
 * llegaban nunca: se dispara desde el planificador del panel, que sí corre.
 */
class DrainStoreOutboxCommand extends Command
{
    protected $signature = 'reviews:drain-store
                            {--timeout=30 : Segundos de espera}';

    protected $description = 'Pide a la tienda que envíe las opiniones pendientes de su cola de salida';

    public function handle(): int
    {
        $url = $this->cronUrl();
        $secret = (string) config('reviews.secret', '');

        if ($url === '' || $secret === '') {
            $this->error('Falta configurar ALSERNETREVIEWS_API_URL y ALSERNETREVIEWS_SECRET.');

            return self::FAILURE;
        }

        try {
            $respuesta = Http::withHeaders(['X-Alsernet-Cron-Secret' => $secret])
                ->timeout((int) $this->option('timeout'))
                ->connectTimeout((int) config('reviews.http_connect_timeout', 3))
                ->get($url);
        } catch (\Throwable $e) {
            Log::warning('Reviews: no se pudo vaciar la cola de la tienda.', ['error' => $e->getMessage()]);
            $this->error('No se pudo contactar con la tienda: '.$e->getMessage());

            return self::FAILURE;
        }

        if (! $respuesta->successful()) {
            Log::warning('Reviews: la tienda rechazó el vaciado de la cola.', [
                'status' => $respuesta->status(),
            ]);
            $this->error('La tienda respondió '.$respuesta->status().'.');

            return self::FAILURE;
        }

        $datos = (array) $respuesta->json();

        if (isset($datos['skipped'])) {
            $this->line('Sin trabajo: '.$datos['skipped'].'.');

            return self::SUCCESS;
        }

        $enviadas = (int) ($datos['sent'] ?? 0);
        $fallidas = (int) ($datos['failed'] ?? 0);

        $this->info($enviadas.' opiniones enviadas, '.$fallidas.' con error.');

        if ($fallidas > 0) {
            Log::warning('Reviews: la tienda no pudo enviar parte de su cola.', ['failed' => $fallidas]);
        }

        return self::SUCCESS;
    }

    /**
     * El cron vive junto al api.php con el que ya se habla, así que se deriva
     * de esa dirección en vez de pedir una segunda variable de entorno.
     */
    private function cronUrl(): string
    {
        $api = rtrim((string) config('reviews.api_url', ''), '/');

        if ($api === '') {
            return '';
        }

        return preg_replace('~/api\.php$~', '/cron.php', $api);
    }
}
