<?php

namespace Modules\Forms\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Vacía la cola de salida del módulo alsernetforms de la tienda.
 *
 * Cada envío de formulario se guarda primero en `alsernet_requests` y se
 * entrega al panel desde ahí. Cuando el panel no contesta, la entrega queda
 * pendiente esperando a un reintento... que no llegaba nunca: el contenedor de
 * la tienda no lleva demonio cron y nadie disparaba su script. Se acumularon
 * 119 peticiones, la más vieja de febrero, y con ellas envíos de clientes que
 * jamás abrieron ticket.
 *
 * Mismo patrón que `reviews:drain-store` y `questions:drain-store`, que existen
 * exactamente por lo mismo.
 */
class DrainStoreOutboxCommand extends Command
{
    protected $signature = 'forms:drain-store
                            {--timeout=60 : Segundos de espera}';

    protected $description = 'Pide a la tienda que entregue los envíos pendientes de su cola de salida';

    public function handle(): int
    {
        $url = $this->cronUrl();
        $secret = (string) config('forms.store.cron_secret', '');

        if ($url === '' || $secret === '') {
            $this->error('Falta configurar FORMS_STORE_API_URL y FORMS_STORE_CRON_SECRET.');

            return self::FAILURE;
        }

        try {
            $respuesta = Http::withHeaders(['X-Alsernet-Cron-Secret' => $secret])
                ->timeout((int) $this->option('timeout'))
                ->connectTimeout((int) config('forms.store.http_connect_timeout', 3))
                ->get($url);
        } catch (\Throwable $e) {
            Log::warning('Forms: no se pudo vaciar la cola de la tienda.', ['error' => $e->getMessage()]);
            $this->error('No se pudo contactar con la tienda: '.$e->getMessage());

            return self::FAILURE;
        }

        if (! $respuesta->successful()) {
            Log::warning('Forms: la tienda rechazó el vaciado de la cola.', ['status' => $respuesta->status()]);
            $this->error('La tienda respondió '.$respuesta->status().'.');

            return self::FAILURE;
        }

        $datos = (array) $respuesta->json();

        if (isset($datos['skipped'])) {
            $this->line('Sin trabajo: '.$datos['skipped'].'.');

            return self::SUCCESS;
        }

        $enviados = (int) ($datos['sent'] ?? 0);
        $fallidos = (int) ($datos['failed'] ?? 0);

        $this->info($enviados.' envíos entregados, '.$fallidos.' con error.');

        if ($fallidos > 0) {
            Log::warning('Forms: la tienda no pudo entregar parte de su cola.', ['failed' => $fallidos]);
        }

        return self::SUCCESS;
    }

    /**
     * El cron vive junto al api.php con el que ya se habla, así que se deriva
     * de esa dirección en vez de pedir una segunda variable de entorno.
     */
    private function cronUrl(): string
    {
        $api = rtrim((string) config('forms.store.api_url', ''), '/');

        if ($api === '') {
            return '';
        }

        return preg_replace('~/api\.php$~', '/cron.php', $api);
    }
}
