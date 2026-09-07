<?php

namespace Modules\HelpdeskEmailActivity\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Modules\HelpdeskEmailActivity\Support\CanIEmailDataset;

/**
 * Refresca el dataset de compatibilidad que usa la pestaña "Compatibilidad" del
 * inspector de mensajes.
 *
 * No está en el scheduler a propósito: el fichero vive EN EL REPO y se comitea,
 * así que actualizarlo es un cambio de código revisable, no un efecto silencioso
 * en producción — que un servidor cambiara solo la puntuación de todos los
 * correos sin dejar rastro en git es justo lo que se quiere evitar.
 */
class UpdateCanIEmailDataCommand extends Command
{
    protected $signature = 'helpdeskemailactivity:update-caniemail
                            {--url=https://www.caniemail.com/api/data.json : Origen del dataset}';

    protected $description = 'Descarga la última versión del dataset de compatibilidad de caniemail.com';

    public function handle(): int
    {
        $url = (string) $this->option('url');
        $path = CanIEmailDataset::path();

        $this->info("Descargando {$url}…");

        $response = Http::timeout(30)->get($url);

        if (! $response->successful()) {
            $this->error("El servidor respondió {$response->status()}.");

            return self::FAILURE;
        }

        $data = $response->json();

        // Se valida antes de sobrescribir: un HTML de error o un JSON truncado
        // dejarían el módulo sin comprobación de compatibilidad y el fallo solo
        // se vería al abrir la pestaña.
        if (! is_array($data) || ! isset($data['data'], $data['nicenames']) || count($data['data']) < 100) {
            $this->error('La respuesta no tiene la forma esperada del dataset de caniemail.');

            return self::FAILURE;
        }

        $previous = CanIEmailDataset::lastUpdate();

        File::put($path, $response->body());
        CanIEmailDataset::flush();

        $this->info(sprintf(
            'Guardadas %d features en %s (anterior: %s → nueva: %s).',
            count($data['data']),
            $path,
            $previous ?? 'desconocida',
            $data['last_update_date'] ?? 'desconocida',
        ));

        $this->comment('Los análisis ya calculados se recalcularán solos: su clave de caché incluye la versión del dataset (ver EmailHtmlCheckService::cacheKey()).');
        $this->comment('Recuerda commitear el fichero: forma parte del código, no es un dato de entorno.');

        return self::SUCCESS;
    }
}
