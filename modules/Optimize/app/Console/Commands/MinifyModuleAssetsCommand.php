<?php

declare(strict_types=1);

namespace Modules\Optimize\Console\Commands;

use Illuminate\Console\Command;
use Modules\Optimize\Services\AssetOptimizerService;

class MinifyModuleAssetsCommand extends Command
{
    protected $signature = 'optimize:minify-module-assets
                            {--force : Regenerar aunque el .min sea más reciente}';

    protected $description = 'Minificar CSS/JS de todos los módulos activos';

    public function handle(AssetOptimizerService $optimizer): int
    {
        $force = (bool) $this->option('force');

        $this->info('Escaneando módulos activos…');
        $this->newLine();

        $result = $optimizer->minifyAllModuleAssets($force);

        $css = $result['css'];
        $js = $result['js'];

        if ($css['count'] > 0) {
            $this->info("✔ CSS minificados: {$css['count']}  (".$this->fmt($css['before']).' → '.$this->fmt($css['after']).', '.$this->pct($css['before'], $css['after']).' menos)');
        } else {
            $this->warn('CSS: todos los .min están actualizados.');
        }

        if ($js['count'] > 0) {
            $this->info("✔ JS  minificados: {$js['count']}  (".$this->fmt($js['before']).' → '.$this->fmt($js['after']).', '.$this->pct($js['before'], $js['after']).' menos)');
        } else {
            $this->warn('JS: todos los .min están actualizados.');
        }

        if (! empty($result['errors'])) {
            $this->newLine();
            $this->error('Errores:');
            foreach ($result['errors'] as $err) {
                $this->line('  - '.$err);
            }
        }

        return self::SUCCESS;
    }

    private function fmt(int $bytes): string
    {
        return $bytes >= 1024 ? number_format($bytes / 1024, 1).' KB' : $bytes.' B';
    }

    private function pct(int $before, int $after): string
    {
        if ($before <= 0) {
            return '0 %';
        }

        return number_format((1 - $after / $before) * 100, 1).' %';
    }
}
