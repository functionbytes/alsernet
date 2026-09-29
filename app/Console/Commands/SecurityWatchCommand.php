<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\SecurityAlertNotification;
use App\Support\Security\SecurityCounters;
use FilesystemIterator;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Throwable;

/**
 * Vigilancia automática de seguridad (29-sep-2026). Programado cada 5 min en
 * bootstrap/app.php. Umbrales y rutas: config/security.php (watch.*).
 * Documentación: docs/seguridad/CABECERAS_Y_VIGILANCIA.md
 *
 * (a) Ficheros ejecutables o con "<?php" en directorios de subidas y public/.
 * (b) Ficheros PHP de código añadidos/modificados/borrados desde la última pasada
 *     (índice mtime+size; hash solo si cambió) en storage/app/security/.
 * (c) Denegaciones de la API ERP y logins fallidos en la ventana (5 min).
 *
 * Primera ejecución (o --init): crea la línea base SIN alertar y abre una
 * "ventana de instalación" (hasta medianoche) en la que los cambios de ficheros
 * solo se registran en el log, sin notificar.
 */
class SecurityWatchCommand extends Command
{
    protected $signature = 'security:watch
        {--init : Reconstruye la línea base sin alertar}
        {--quiet-until= : Con --init, fin de la ventana sin alertas de ficheros (por defecto, medianoche)}
        {--dry-run : No notifica ni guarda el índice}';

    protected $description = 'Vigila subidas ejecutables, cambios en código PHP, denegaciones ERP y logins fallidos; avisa a los super-admin';

    private string $stateDir;

    /** @var array<string, mixed> */
    private array $state = [];

    public function handle(): int
    {
        $this->stateDir = storage_path('app/security');
        if (! is_dir($this->stateDir) && ! @mkdir($this->stateDir, 0770, true) && ! is_dir($this->stateDir)) {
            $this->error("No se puede crear {$this->stateDir}");

            return self::FAILURE;
        }

        $this->state = $this->readJson('state.json') ?? [];
        $init = (bool) $this->option('init') || ! isset($this->state['initialized_at']);
        $dryRun = (bool) $this->option('dry-run');
        $started = microtime(true);

        [$uploadFindings, $uploadIndex] = $this->scanUploads($init);
        [$codeFindings, $codeIndex] = $this->scanCode($init);
        $thresholdFindings = $this->checkThresholds();

        $now = time();
        if ($init) {
            $quietUntil = $this->option('quiet-until')
                ? Carbon::parse((string) $this->option('quiet-until'))->getTimestamp()
                : Carbon::tomorrow()->getTimestamp();
            $this->state['initialized_at'] = $now;
            $this->state['quiet_until'] = $quietUntil;
        }
        $quiet = $now < (int) ($this->state['quiet_until'] ?? 0);

        $fileLines = array_merge($uploadFindings, $codeFindings);
        $alertLines = [];

        if ($fileLines !== [] && ! $dryRun) {
            if ($init || $quiet) {
                Log::channel('security')->info('security:watch cambios de ficheros durante la línea base/ventana de instalación (sin alerta)', [
                    'findings' => array_slice($fileLines, 0, 500),
                    'total' => count($fileLines),
                ]);
            } else {
                $alertLines = array_merge($alertLines, $fileLines);
            }
        }

        // Umbrales con enfriamiento para no repetir la misma alerta cada 5 min.
        $cooldown = 60 * (int) config('security.watch.alert_cooldown_minutes', 30);
        foreach ($thresholdFindings as $key => $line) {
            $last = (int) ($this->state['last_alerts'][$key] ?? 0);
            if ($now - $last >= $cooldown) {
                $alertLines[] = $line;
                $this->state['last_alerts'][$key] = $now;
            } elseif (! $dryRun) {
                Log::channel('security')->info('security:watch umbral superado (en enfriamiento): '.$line);
            }
        }

        foreach ($this->limit($fileLines) as $line) {
            $this->line($line);
        }
        foreach ($thresholdFindings as $line) {
            $this->line($line);
        }

        if ($alertLines !== [] && ! $dryRun) {
            $this->sendAlert($alertLines);
        }

        if (! $dryRun) {
            $this->state['last_run'] = $now;
            $this->state['last_duration_ms'] = (int) ((microtime(true) - $started) * 1000);
            $this->writeJson('uploads_index.json', $uploadIndex);
            $this->writeJson('code_index.json', $codeIndex);
            $this->writeJson('state.json', $this->state);
        }

        $this->info(sprintf(
            'security:watch %s: %d subidas, %d ficheros de código, %d hallazgos de ficheros, %d umbrales, %d alertas%s (%.1fs)',
            $init ? '(línea base)' : 'ok',
            count($uploadIndex),
            count($codeIndex),
            count($fileLines),
            count($thresholdFindings),
            $dryRun ? 0 : count($alertLines),
            $quiet && ! $init ? ' [ventana de instalación: ficheros sin alerta]' : '',
            microtime(true) - $started
        ));

        return self::SUCCESS;
    }

    /**
     * (a) Ficheros sospechosos en directorios de subidas y public/.
     *
     * @return array{0: array<int, string>, 1: array<string, array{0:int,1:int,2:?string,3:?string}>}
     */
    private function scanUploads(bool $init): array
    {
        $old = $init ? [] : ($this->readJson('uploads_index.json') ?? []);
        $pattern = (string) config('security.watch.suspicious_name_pattern');
        $maxBytes = (int) config('security.watch.content_scan_max_bytes', 2097152);
        $skip = array_map(fn ($p) => rtrim($p, '/').'/', (array) config('security.watch.content_scan_skip', []));

        $index = [];
        $findings = [];

        foreach ((array) config('security.watch.upload_dirs', []) as $dir) {
            foreach ($this->files(base_path($dir)) as $abs => [$mtime, $size]) {
                $rel = $this->rel($abs);
                $prev = $old[$rel] ?? null;
                $unchanged = $prev !== null && $prev[0] === $mtime && $prev[1] === $size;

                $reason = preg_match($pattern, basename($rel)) ? 'nombre' : null;
                $hash = $unchanged ? ($prev[2] ?? null) : null;

                if ($unchanged) {
                    $reason = $prev[3] ?? $reason;
                } elseif ($reason === null && $size > 0 && $size <= $maxBytes && ! $this->startsWithAny($rel, $skip)) {
                    $content = @file_get_contents($abs);
                    if ($content !== false && stripos($content, '<?php') !== false) {
                        $reason = 'contenido <?php';
                    }
                }

                if ($reason !== null && $hash === null) {
                    $hash = @sha1_file($abs) ?: null;
                }

                $index[$rel] = [$mtime, $size, $hash, $reason];

                if ($reason === null) {
                    continue;
                }
                if ($prev === null) {
                    $findings[] = "Fichero sospechoso NUEVO ({$reason}): {$rel}";
                } elseif (($prev[3] ?? null) === null) {
                    $findings[] = "Fichero pasa a ser sospechoso ({$reason}): {$rel}";
                } elseif (! $unchanged && ($prev[2] ?? null) !== $hash) {
                    $findings[] = "Fichero sospechoso MODIFICADO ({$reason}): {$rel}";
                }
            }
        }

        return [$findings, $index];
    }

    /**
     * (b) Cambios en ficheros PHP de código.
     *
     * @return array{0: array<int, string>, 1: array<string, array{0:int,1:int,2:?string}>}
     */
    private function scanCode(bool $init): array
    {
        $old = $this->readJson('code_index.json') ?? [];
        $skip = (array) config('security.watch.code_skip', []);

        $index = [];
        $added = $modified = [];

        foreach ((array) config('security.watch.code_dirs', []) as $dir) {
            foreach ($this->files(base_path($dir), $skip) as $abs => [$mtime, $size]) {
                if (! str_ends_with(strtolower($abs), '.php')) {
                    continue;
                }
                $rel = $this->rel($abs);
                $prev = $old[$rel] ?? null;

                if ($prev !== null && $prev[0] === $mtime && $prev[1] === $size) {
                    $index[$rel] = $prev;

                    continue;
                }

                // Solo se calcula el hash cuando cambió mtime/size (o fichero nuevo).
                $hash = @sha1_file($abs) ?: null;
                $index[$rel] = [$mtime, $size, $hash];

                if ($init || $old === []) {
                    continue;
                }
                if ($prev === null) {
                    $added[] = $rel;
                } elseif (($prev[2] ?? null) !== $hash) {
                    $modified[] = $rel;
                }
            }
        }

        $deleted = ($init || $old === []) ? [] : array_keys(array_diff_key($old, $index));

        $findings = [];
        foreach ($added as $rel) {
            $findings[] = "Código PHP NUEVO: {$rel}";
        }
        foreach ($modified as $rel) {
            $findings[] = "Código PHP MODIFICADO: {$rel}";
        }
        foreach ($deleted as $rel) {
            $findings[] = "Código PHP BORRADO: {$rel}";
        }

        return [$findings, $index];
    }

    /**
     * (c) Umbrales en la ventana.
     *
     * @return array<string, string> clave de enfriamiento => texto
     */
    private function checkThresholds(): array
    {
        $minutes = (int) config('security.watch.window_minutes', 5);
        $t = (array) config('security.watch.thresholds', []);
        $out = [];

        $erp403 = SecurityCounters::sum('erp_denied_403', $minutes);
        $erp401 = SecurityCounters::sum('erp_denied_401', $minutes);
        if ($erp403 >= (int) ($t['erp_denied_403'] ?? PHP_INT_MAX)) {
            $out['erp_403'] = "API ERP: {$erp403} accesos denegados (403, IP no permitida) en {$minutes} min";
        }
        if ($erp401 >= (int) ($t['erp_denied_401'] ?? PHP_INT_MAX)) {
            $out['erp_401'] = "API ERP: {$erp401} accesos denegados (401, credenciales) en {$minutes} min";
        }

        $failedTotal = SecurityCounters::sum('failed_logins', $minutes);
        try {
            if (Schema::hasTable('login_attempts')) {
                $since = now()->subMinutes($minutes);
                $base = fn () => DB::table('login_attempts')
                    ->whereIn('status', ['failed', 'lockout', '2fa_failed'])
                    ->where('attempted_at', '>=', $since);

                $failedTotal = max($failedTotal, (int) $base()->count());

                $perIp = (int) ($t['failed_logins_per_ip'] ?? PHP_INT_MAX);
                foreach ($base()->whereNotNull('ip_address')->select('ip_address', DB::raw('COUNT(*) AS n'))
                    ->groupBy('ip_address')->having('n', '>=', $perIp)->orderByDesc('n')->limit(10)->get() as $row) {
                    $out['login_ip_'.$row->ip_address] = "Login: {$row->n} intentos fallidos desde la IP {$row->ip_address} en {$minutes} min";
                }

                $perEmail = (int) ($t['failed_logins_per_email'] ?? PHP_INT_MAX);
                foreach ($base()->whereNotNull('email')->select('email', DB::raw('COUNT(*) AS n'))
                    ->groupBy('email')->having('n', '>=', $perEmail)->orderByDesc('n')->limit(10)->get() as $row) {
                    $out['login_email_'.sha1((string) $row->email)] = "Login: {$row->n} intentos fallidos contra la cuenta {$row->email} en {$minutes} min";
                }
            }
        } catch (Throwable $e) {
            Log::channel('security')->warning('security:watch no pudo leer login_attempts: '.$e->getMessage());
        }

        if ($failedTotal >= (int) ($t['failed_logins'] ?? PHP_INT_MAX)) {
            $out['login_total'] = "Login: {$failedTotal} intentos fallidos en total en {$minutes} min";
        }

        return $out;
    }

    /**
     * @param  array<int, string>  $lines
     */
    private function sendAlert(array $lines): void
    {
        $summary = count($lines).' hallazgo(s) de security:watch en '.config('app.url');

        // Lista completa al log; la notificación lleva como mucho max_files_in_alert.
        Log::channel('security')->warning('security:watch ALERTA: '.$summary, ['findings' => $lines]);
        $lines = $this->limit($lines);

        try {
            $role = (string) config('security.watch.notify_role', 'super-admin');
            $users = User::role($role)->get();
            if ($users->isEmpty()) {
                Log::channel('security')->warning("security:watch sin destinatarios con rol {$role}");

                return;
            }

            $mailer = (string) config('mail.default');
            $withMail = $mailer !== '' && ! in_array($mailer, ['log', 'array', 'null'], true)
                && filled(config('mail.from.address'));

            Notification::sendNow($users, new SecurityAlertNotification($summary, $lines, $withMail));
            $this->warn("Alerta enviada a {$users->count()} usuario(s) {$role}".($withMail ? ' (BD + email)' : ' (BD)'));
        } catch (Throwable $e) {
            Log::channel('security')->error('security:watch no pudo notificar: '.$e->getMessage());
            $this->error('No se pudo notificar: '.$e->getMessage());
        }
    }

    /**
     * Recorre ficheros sin seguir enlaces simbólicos.
     *
     * @param  array<int, string>  $skip  rutas relativas a base_path o nombres de directorio
     * @return \Generator<string, array{0:int,1:int}>
     */
    private function files(string $root, array $skip = []): \Generator
    {
        if (! is_dir($root)) {
            return;
        }

        $dirIt = new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS | FilesystemIterator::CURRENT_AS_FILEINFO);
        $filter = new \RecursiveCallbackFilterIterator($dirIt, function (\SplFileInfo $f) use ($skip) {
            if ($f->isLink()) {
                return false;
            }
            if ($f->isDir()) {
                $rel = $this->rel($f->getPathname());
                foreach ($skip as $s) {
                    if ($rel === $s || $f->getFilename() === $s) {
                        return false;
                    }
                }
            }

            return true;
        });

        try {
            foreach (new RecursiveIteratorIterator($filter, RecursiveIteratorIterator::LEAVES_ONLY, RecursiveIteratorIterator::CATCH_GET_CHILD) as $f) {
                if ($f->isFile()) {
                    yield $f->getPathname() => [(int) $f->getMTime(), (int) $f->getSize()];
                }
            }
        } catch (Throwable $e) {
            Log::channel('security')->warning("security:watch no pudo recorrer {$root}: ".$e->getMessage());
        }
    }

    private function rel(string $abs): string
    {
        $base = rtrim(base_path(), '/').'/';

        return str_starts_with($abs, $base) ? substr($abs, strlen($base)) : $abs;
    }

    /**
     * @param  array<int, string>  $prefixes
     */
    private function startsWithAny(string $path, array $prefixes): bool
    {
        foreach ($prefixes as $p) {
            if (str_starts_with($path, $p)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int, string>  $lines
     * @return array<int, string>
     */
    private function limit(array $lines): array
    {
        $max = (int) config('security.watch.max_files_in_alert', 40);
        if (count($lines) <= $max) {
            return $lines;
        }
        $extra = count($lines) - $max;

        return array_merge(array_slice($lines, 0, $max), ["… y {$extra} más (ver log security)"]);
    }

    private function readJson(string $name): ?array
    {
        $path = $this->stateDir.'/'.$name;
        if (! is_file($path)) {
            return null;
        }
        $data = json_decode((string) @file_get_contents($path), true);

        return is_array($data) ? $data : null;
    }

    private function writeJson(string $name, array $data): void
    {
        $path = $this->stateDir.'/'.$name;
        $tmp = $path.'.'.getmypid().'.tmp';
        if (@file_put_contents($tmp, json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) === false) {
            $this->error("No se puede escribir {$path}");

            return;
        }
        @chmod($tmp, 0660);
        if (! @rename($tmp, $path)) {
            @unlink($tmp);
            $this->error("No se puede reemplazar {$path}");
        }
    }
}
