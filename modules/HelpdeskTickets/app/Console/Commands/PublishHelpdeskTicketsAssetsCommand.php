<?php

namespace Modules\HelpdeskTickets\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Copia los assets del módulo a public/modules/helpdesktickets/, que es de
 * donde los sirve asset() en las vistas.
 *
 * Hasta ahora la copia se hacía a mano y se desincronizaba: en el momento de
 * escribir esto, tickets-app.css llevaba días con un ajuste de posicionamiento
 * que estaba en el módulo y no en lo que servía el servidor, así que el cambio
 * simplemente no se veía en la aplicación. Con un comando el paso es
 * reproducible y se puede encadenar a un deploy.
 */
class PublishHelpdeskTicketsAssetsCommand extends Command
{
    protected $signature = 'helpdesktickets:publish-assets {--check : Solo informar de las diferencias, sin copiar}';

    protected $description = 'Publica los assets de HelpdeskTickets en public/modules/helpdesktickets';

    public function handle(): int
    {
        $source = module_path('HelpdeskTickets', 'public');
        $target = public_path('modules/helpdesktickets');

        if (! File::isDirectory($source)) {
            $this->error("No existe el directorio de origen: {$source}");

            return self::FAILURE;
        }

        $checkOnly = (bool) $this->option('check');
        $stale = 0;

        foreach (File::allFiles($source) as $file) {
            $relative = $file->getRelativePathname();
            $destination = $target.DIRECTORY_SEPARATOR.$relative;

            $missing = ! File::exists($destination);
            $differs = ! $missing && File::get($destination) !== File::get($file->getPathname());

            if (! $missing && ! $differs) {
                continue;
            }

            $stale++;
            $this->line(($missing ? '  falta   ' : '  difiere ').$relative);

            if (! $checkOnly) {
                File::ensureDirectoryExists(dirname($destination));
                File::copy($file->getPathname(), $destination);
            }
        }

        if ($stale === 0) {
            $this->info('Los assets publicados ya están al día.');

            return self::SUCCESS;
        }

        if ($checkOnly) {
            $this->warn("{$stale} asset(s) desincronizado(s). Ejecuta el comando sin --check para publicarlos.");

            return self::FAILURE;
        }

        $this->info("{$stale} asset(s) publicado(s) en {$target}.");

        return self::SUCCESS;
    }
}
