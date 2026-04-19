<?php

namespace Modules\Shortcode\Console;

use Illuminate\Console\Command;
use Modules\Shortcode\Facades\Shortcode;

class ShortcodeBenchmarkCommand extends Command
{
    protected $signature = 'shortcode:benchmark
                            {--iterations=1000 : Cantidad de compilaciones por shortcode}
                            {--name= : Limitar a un shortcode específico}';

    protected $description = 'Mide el tiempo promedio de compilación de los shortcodes registrados';

    public function handle(): int
    {
        $iterations = max(10, (int) $this->option('iterations'));
        $filter = $this->option('name');

        $registered = Shortcode::getRegistered();
        if ($filter) {
            $registered = array_values(array_filter($registered, fn ($s) => $s['name'] === $filter));
            if (empty($registered)) {
                $this->error("Shortcode [{$filter}] no encontrado.");

                return self::FAILURE;
            }
        }

        // Cache deshabilitado durante benchmark para medir compilación pura.
        $cacheWas = config('shortcode.cache');
        config(['shortcode.cache' => false]);

        $this->line('Ejecutando benchmark con '.$iterations.' iteraciones por shortcode...');
        $this->line('');

        $rows = [];
        foreach ($registered as $sc) {
            $example = $sc['example'] ?? "[{$sc['name']}][/{$sc['name']}]";

            // Warmup: 10 iter para estabilizar opcache.
            for ($i = 0; $i < 10; $i++) {
                Shortcode::compile($example);
            }

            $start = hrtime(true);
            for ($i = 0; $i < $iterations; $i++) {
                Shortcode::compile($example);
            }
            $elapsedNs = hrtime(true) - $start;

            $totalMs = $elapsedNs / 1_000_000;
            $avgUs = ($elapsedNs / $iterations) / 1_000;

            $rows[] = [
                $sc['name'],
                number_format($totalMs, 2).' ms',
                number_format($avgUs, 2).' μs',
                strlen($example).' bytes',
            ];
        }

        config(['shortcode.cache' => $cacheWas]);

        usort($rows, fn ($a, $b) => (float) $b[2] <=> (float) $a[2]);

        $this->table(['Shortcode', 'Total', 'Promedio', 'Ejemplo'], $rows);
        $this->line('');
        $this->info('Benchmark completado. Promedio en microsegundos por compilación.');

        return self::SUCCESS;
    }
}
