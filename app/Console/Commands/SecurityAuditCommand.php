<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Comprobaciones automáticas del apartado 16 de
 * docs/seguridad/GUIA_DESARROLLO_SEGURO.md (29-sep-2026).
 *
 *  1-6: búsquedas por línea en modules/ y app/ (mismas expresiones que la guía).
 *  7:   rutas sin middleware de autenticación frente a
 *       docs/seguridad/rutas_publicas_aprobadas.txt.
 *
 * Los resultados 1-6 se comparan con la línea base
 * docs/seguridad/auditoria_linea_base.json (fichero + contenido de la línea, no el
 * número de línea, para que no salten al mover código).
 *
 * Código de salida: 0 sin novedades; 1 si hay resultados nuevos respecto a la
 * línea base o rutas públicas no aprobadas. Uso:
 *   php artisan security:audit                    (antes de cada merge)
 *   php artisan security:audit --update-baseline  (tras revisar y justificar)
 *   php artisan security:audit --update-routes    (reescribe la lista aprobada)
 */
class SecurityAuditCommand extends Command
{
    protected $signature = 'security:audit
        {--update-baseline : Guarda los resultados actuales de 1-6 como línea base}
        {--update-routes : Reescribe rutas_publicas_aprobadas.txt con las rutas públicas actuales}
        {--details : Muestra todos los resultados, no solo las novedades}';

    protected $description = 'Ejecuta las comprobaciones del apartado 16 de la guía de desarrollo seguro; sale con 1 si hay novedades';

    /** Exclusión común de la guía: tests y código de terceros. */
    private const EXCLUDE = '#/tests/|Prestashop/integrations|/vendor/|/node_modules/#';

    public function handle(): int
    {
        $checks = $this->runGrepChecks();
        $baselinePath = base_path((string) config('security.audit.baseline_file', 'docs/seguridad/auditoria_linea_base.json'));
        $baseline = is_file($baselinePath) ? (json_decode((string) file_get_contents($baselinePath), true) ?: []) : [];

        $novelties = 0;

        foreach ($checks as $id => $check) {
            $current = $check['results'];
            $base = $baseline[$id] ?? [];
            $new = $this->multisetDiff($current, $base);
            $gone = $this->multisetDiff($base, $current);
            $novelties += count($new);

            $this->line(sprintf(
                '<%s>[%s] %s: %d resultado(s) (línea base %d) — %d nuevo(s), %d desaparecido(s)</>',
                $new ? 'fg=red' : 'fg=green',
                $id,
                $check['title'],
                count($current),
                count($base),
                count($new),
                count($gone)
            ));
            foreach ($this->option('details') ? $current : $new as $item) {
                $this->line('    '.($this->option('details') && ! in_array($item, $new, true) ? '  ' : '+ ').$item);
            }
        }

        // 7) Rutas públicas
        [$public, $approved] = $this->publicRoutes();
        $newRoutes = array_values(array_diff($public, $approved));
        $removedRoutes = array_values(array_diff($approved, $public));
        $novelties += count($newRoutes);

        $this->line(sprintf(
            '<%s>[7] Rutas sin autenticación: %d (aprobadas %d) — %d nueva(s) sin aprobar, %d ya no existen/no son públicas</>',
            $newRoutes ? 'fg=red' : 'fg=green',
            count($public),
            count($approved),
            count($newRoutes),
            count($removedRoutes)
        ));
        foreach ($newRoutes as $r) {
            $this->line('    + '.$r);
        }
        foreach ($removedRoutes as $r) {
            $this->line('    - '.$r);
        }
        $this->line('    Nota: el filtro se basa en NOMBRES de middleware; un middleware que se llame "auth"/"sanctum" pero no compruebe nada no aparece aquí.');

        if ($this->option('update-baseline')) {
            $data = [];
            foreach ($checks as $id => $check) {
                $data[$id] = $check['results'];
            }
            $data['_generated_at'] = now()->toIso8601String();
            file_put_contents($baselinePath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n");
            $this->info('Línea base actualizada: '.$this->rel($baselinePath));
        }

        if ($this->option('update-routes')) {
            $this->writeApprovedRoutes($public);
            $this->info('Lista de rutas públicas aprobadas actualizada.');
        }

        if ($novelties > 0 && ! $this->option('update-baseline') && ! $this->option('update-routes')) {
            $this->error("{$novelties} novedad(es): revísalas y justifícalas en el PR (luego --update-baseline / --update-routes).");

            return self::FAILURE;
        }

        $this->info('Sin novedades respecto a la línea base.');

        return self::SUCCESS;
    }

    /**
     * @return array<string, array{title: string, results: array<int, string>}>
     */
    private function runGrepChecks(): array
    {
        $defs = [
            '1' => ['Nombre o extensión del cliente', '/getClientOriginal(Name|Extension)\(/', true, null],
            '3' => ['Escrituras en el disco public', '/(store|storeAs|putFile|putFileAs)\([^)]*[\'"]public[\'"]|disk\([\'"]public[\'"]\)->put/', true, null],
            '4' => ['Ejecución de comandos / código', '/\b(shell_exec|exec|system|passthru|popen|proc_open)\s*\(|fromShellCommandline|\beval\s*\(|\bunserialize\s*\(/', true, '#->exec\(|Console/Commands#'],
            '5' => ['SQL con variables de la petición', '/(whereRaw|orderByRaw|selectRaw|havingRaw|DB::raw|DB::statement)\([^)]*\$(request|input|_GET|_POST)/', false, null],
            '6' => ['Validación de rutas con strpos', '/strpos\(\$[a-zA-Z]*[Pp]ath/', true, null],
        ];

        $results = array_fill_keys(['1', '2', '3', '4', '5', '6'], []);

        foreach ($this->phpFiles(['modules', 'app']) as $rel => $abs) {
            $lines = @file($abs, FILE_IGNORE_NEW_LINES);
            if ($lines === false) {
                continue;
            }
            $excluded = (bool) preg_match(self::EXCLUDE, '/'.$rel);

            foreach ($lines as $i => $line) {
                foreach ($defs as $id => [, $regex, $useExclude, $extraExclude]) {
                    if ($useExclude && $excluded) {
                        continue;
                    }
                    if (! preg_match($regex, $line)) {
                        continue;
                    }
                    if ($extraExclude && (preg_match($extraExclude, $rel) || preg_match($extraExclude, $line))) {
                        continue;
                    }
                    $results[$id][] = $rel.': '.$this->norm($line);
                }

                // 2) addMedia...( seguido (en 3 líneas) de toMediaCollection sin usingFileName
                if (! $excluded && preg_match('/addMedia(FromRequest|FromUrl)?\(/', $line)) {
                    $window = implode("\n", array_slice($lines, $i, 4));
                    if (str_contains($window, 'toMediaCollection') && ! str_contains($window, 'usingFileName')) {
                        $results['2'][] = $rel.': '.$this->norm($line);
                    }
                }
            }
        }

        $titles = [
            '1' => $defs['1'][0],
            '2' => 'Media Library sin nombre fijado',
            '3' => $defs['3'][0],
            '4' => $defs['4'][0],
            '5' => $defs['5'][0],
            '6' => $defs['6'][0],
        ];

        $out = [];
        foreach ($titles as $id => $title) {
            $r = $results[$id];
            sort($r);
            $out[$id] = ['title' => $title, 'results' => $r];
        }

        return $out;
    }

    /**
     * @return array{0: array<int, string>, 1: array<int, string>}
     */
    private function publicRoutes(): array
    {
        $buffer = new BufferedOutput;
        Artisan::call('route:list', ['--json' => true], $buffer);
        $routes = json_decode($buffer->fetch(), true) ?: [];

        $public = [];
        foreach ($routes as $route) {
            $mw = json_encode($route['middleware'] ?? []);
            if (! preg_match('/auth|can:|signed|sanctum|Authorize|ValidateSignature/i', (string) $mw)) {
                $public[] = $route['method'].' '.$route['uri'];
            }
        }
        $public = array_values(array_unique($public));
        sort($public);

        $file = base_path((string) config('security.audit.approved_routes_file'));
        $approved = [];
        if (is_file($file)) {
            foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                if (! str_starts_with($line, '#')) {
                    $approved[] = trim($line);
                }
            }
        }

        return [$public, $approved];
    }

    /**
     * @param  array<int, string>  $public
     */
    private function writeApprovedRoutes(array $public): void
    {
        $file = base_path((string) config('security.audit.approved_routes_file'));
        $header = [];
        if (is_file($file)) {
            foreach (file($file, FILE_IGNORE_NEW_LINES) as $line) {
                if (! str_starts_with($line, '#')) {
                    break;
                }
                $header[] = $line;
            }
        }

        file_put_contents($file, implode("\n", array_merge($header, $public))."\n");
    }

    /**
     * @return \Generator<string, string>
     */
    private function phpFiles(array $dirs): \Generator
    {
        foreach ($dirs as $dir) {
            $root = base_path($dir);
            if (! is_dir($root)) {
                continue;
            }
            $it = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::LEAVES_ONLY,
                RecursiveIteratorIterator::CATCH_GET_CHILD
            );
            $files = [];
            foreach ($it as $f) {
                if ($f->isFile() && str_ends_with($f->getFilename(), '.php')) {
                    $files[$this->rel($f->getPathname())] = $f->getPathname();
                }
            }
            ksort($files);
            yield from $files;
        }
    }

    /**
     * Elementos de $a que no están en $b, respetando repeticiones.
     *
     * @return array<int, string>
     */
    private function multisetDiff(array $a, array $b): array
    {
        $counts = array_count_values(array_map('strval', $b));
        $out = [];
        foreach ($a as $item) {
            if (($counts[$item] ?? 0) > 0) {
                $counts[$item]--;
            } else {
                $out[] = $item;
            }
        }

        return $out;
    }

    private function norm(string $line): string
    {
        return mb_substr(preg_replace('/\s+/', ' ', trim($line)) ?? '', 0, 240);
    }

    private function rel(string $abs): string
    {
        $base = rtrim(base_path(), '/').'/';

        return str_starts_with($abs, $base) ? substr($abs, strlen($base)) : $abs;
    }
}
