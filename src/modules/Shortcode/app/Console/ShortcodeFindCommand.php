<?php

namespace Modules\Shortcode\Console;

use Illuminate\Console\Command;
use Modules\Shortcode\Facades\Shortcode;

class ShortcodeFindCommand extends Command
{
    protected $signature = 'shortcode:find
                            {path : Archivo o directorio a escanear}
                            {--ext=blade.php,php,html,md,txt : Extensiones a incluir (coma separadas)}';

    protected $description = 'Escanea archivos y lista los shortcodes que aparecen, sin ejecutarlos';

    public function handle(): int
    {
        $path = (string) $this->argument('path');

        if (! file_exists($path)) {
            $this->error("Ruta no encontrada: {$path}");

            return self::FAILURE;
        }

        $extensions = array_map('trim', explode(',', (string) $this->option('ext')));
        $files = $this->collectFiles($path, $extensions);

        if (empty($files)) {
            $this->warn('No se encontraron archivos que procesar.');

            return self::SUCCESS;
        }

        $totalByName = [];
        $rows = [];

        foreach ($files as $file) {
            $content = @file_get_contents($file);
            if ($content === false) {
                continue;
            }

            $found = Shortcode::find($content);
            if (empty($found)) {
                continue;
            }

            $counts = [];
            foreach ($found as $entry) {
                $counts[$entry['name']] = ($counts[$entry['name']] ?? 0) + 1;
                $totalByName[$entry['name']] = ($totalByName[$entry['name']] ?? 0) + 1;
            }

            foreach ($counts as $name => $count) {
                $rows[] = [$this->relativePath($file, $path), $name, $count];
            }
        }

        if (empty($rows)) {
            $this->info('No se encontraron shortcodes en los archivos escaneados.');

            return self::SUCCESS;
        }

        $this->table(['Archivo', 'Shortcode', 'Ocurrencias'], $rows);

        arsort($totalByName);
        $this->line('');
        $this->info('Resumen total:');
        foreach ($totalByName as $name => $count) {
            $this->line("  [{$name}] → {$count}");
        }

        return self::SUCCESS;
    }

    /**
     * @param  array<int, string>  $extensions
     * @return array<int, string>
     */
    protected function collectFiles(string $path, array $extensions): array
    {
        if (is_file($path)) {
            return [$path];
        }

        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }
            $filename = $file->getFilename();
            foreach ($extensions as $ext) {
                if (str_ends_with($filename, '.'.$ext)) {
                    $files[] = $file->getPathname();
                    break;
                }
            }
        }

        return $files;
    }

    protected function relativePath(string $file, string $base): string
    {
        $base = rtrim($base, DIRECTORY_SEPARATOR);
        if (is_file($base)) {
            $base = dirname($base);
        }

        return ltrim(str_replace($base, '', $file), DIRECTORY_SEPARATOR);
    }
}
