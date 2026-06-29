<?php

namespace Modules\Shortcode\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Shortcode\Facades\Shortcode;

/**
 * Escanea columnas de texto en BD buscando shortcodes NO registrados (huérfanos)
 * y reporta dónde están. Útil tras deshabilitar un shortcode o migrar desde otro CMS.
 *
 * Las tablas y columnas a escanear se configuran en config/shortcode.php:
 *
 *   'validate_sources' => [
 *       ['table' => 'pages',       'id' => 'id', 'column' => 'content'],
 *       ['table' => 'blog_posts',  'id' => 'id', 'column' => 'content'],
 *   ],
 */
class ShortcodeValidateCommand extends Command
{
    protected $signature = 'shortcode:validate
                            {--source= : Nombre de columna a forzar (tabla.columna); si se omite, usa config}';

    protected $description = 'Detecta shortcodes huérfanos (no registrados) en el contenido de la BD';

    public function handle(): int
    {
        $sources = $this->resolveSources();

        if (empty($sources)) {
            $this->warn('No hay fuentes configuradas para validar. Define shortcode.validate_sources en config.');

            return self::SUCCESS;
        }

        $registered = array_flip(Shortcode::all());
        $orphans = [];
        $rowsScanned = 0;

        foreach ($sources as $source) {
            $table = $source['table'];
            $column = $source['column'];
            $idCol = $source['id'] ?? 'id';

            if (! Schema::hasTable($table)) {
                $this->warn("Saltando: tabla [{$table}] no existe.");

                continue;
            }
            if (! Schema::hasColumn($table, $column)) {
                $this->warn("Saltando: columna [{$table}.{$column}] no existe.");

                continue;
            }

            $this->line("Escaneando {$table}.{$column}...");

            DB::table($table)
                ->select([$idCol, $column])
                ->whereNotNull($column)
                ->where($column, 'like', '%[%')
                ->orderBy($idCol)
                ->chunk(200, function ($rows) use (&$rowsScanned, &$orphans, $table, $column, $idCol, $registered) {
                    foreach ($rows as $row) {
                        $rowsScanned++;
                        $found = Shortcode::find((string) $row->{$column});
                        foreach ($found as $entry) {
                            if (! isset($registered[$entry['name']])) {
                                $orphans[$entry['name']][] = [
                                    'source' => "{$table}.{$column}",
                                    'id' => $row->{$idCol},
                                ];
                            }
                        }
                    }
                });
        }

        $this->line('');
        $this->info("Filas escaneadas: {$rowsScanned}");

        if (empty($orphans)) {
            $this->info('No se encontraron shortcodes huérfanos.');

            return self::SUCCESS;
        }

        $this->line('');
        $this->warn('Shortcodes huérfanos encontrados:');
        $this->line('');

        $rows = [];
        foreach ($orphans as $name => $occurrences) {
            $sample = array_slice($occurrences, 0, 3);
            $sampleStr = implode(', ', array_map(fn ($o) => $o['source'].'#'.$o['id'], $sample));
            $rows[] = [
                "[{$name}]",
                count($occurrences),
                $sampleStr.(count($occurrences) > 3 ? '...' : ''),
            ];
        }

        usort($rows, fn ($a, $b) => $b[1] <=> $a[1]);

        $this->table(['Shortcode', 'Ocurrencias', 'Muestras'], $rows);

        return self::FAILURE;
    }

    /**
     * @return array<int, array{table: string, column: string, id?: string}>
     */
    protected function resolveSources(): array
    {
        if ($override = $this->option('source')) {
            if (! str_contains($override, '.')) {
                $this->error('Formato --source inválido. Usa tabla.columna');

                return [];
            }
            [$table, $column] = explode('.', $override, 2);

            return [['table' => $table, 'column' => $column, 'id' => 'id']];
        }

        return (array) config('shortcode.validate_sources', []);
    }
}
