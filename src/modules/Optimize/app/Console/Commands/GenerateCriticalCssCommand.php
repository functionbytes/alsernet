<?php

declare(strict_types=1);

namespace Modules\Optimize\Console\Commands;

use Illuminate\Console\Command;
use Modules\Optimize\Services\CriticalCssService;

class GenerateCriticalCssCommand extends Command
{
    protected $signature = 'optimize:generate-critical-css
                            {--theme= : Tema objetivo (por defecto el activo)}
                            {--bytes=20480 : Bytes a extraer por archivo CSS}';

    protected $description = 'Generar critical.css inline a partir de los assets declarados del tema';

    public function handle(CriticalCssService $service): int
    {
        $theme = $this->option('theme') ?: null;
        $bytes = max(1024, (int) $this->option('bytes'));

        $this->info('Generando Critical CSS…');

        $result = $service->generate($theme, $bytes);

        if (! $result['success']) {
            $this->error($result['message']);

            return self::FAILURE;
        }

        $this->info('✔ Critical CSS generado');
        $this->line('  Archivo: '.$result['path']);
        $this->line('  Archivos analizados: '.$result['files']);
        $this->line('  Tamaño: '.$result['size_formatted']);
        $this->newLine();
        $this->line('Activa `optimize.inject_critical_css` en settings para que el middleware lo inyecte inline.');

        return self::SUCCESS;
    }
}
