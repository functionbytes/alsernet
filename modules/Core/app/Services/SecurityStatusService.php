<?php

namespace Modules\Core\Services;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Role;
use Throwable;

/**
 * Datos de solo lectura de la pantalla "Configuración de seguridad"
 * (29-sep-2026, H4). Nada de aquí ejecuta procesos externos: lee config,
 * ficheros de estado y /proc.
 */
class SecurityStatusService
{
    /** Filtro por IP del personal (lo gestiona su propia pantalla). */
    public function ipFilter(): array
    {
        $class = 'Modules\\Auth\\Services\\StaffIpAllowlist';
        if (! class_exists($class)) {
            return ['available' => false];
        }

        try {
            $filter = app($class);

            return [
                'available' => true,
                'mode' => $filter->mode(),
                'source' => $filter->modeSource(),
                'forced_off' => $filter->isForcedOff(),
                'entries' => count($filter->entries()),
            ];
        } catch (Throwable) {
            return ['available' => false];
        }
    }

    public function passwordPolicy(): array
    {
        return [
            'password' => (array) config('auth.auth-policy.password', []),
            'lockout' => (array) config('auth.auth-policy.lockout', []),
        ];
    }

    /** Roles existentes (guard web) para el selector del 2FA obligatorio. */
    public function roles(): array
    {
        try {
            return Role::query()->where('guard_name', 'web')->orderBy('name')->pluck('name')->all();
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * Usuarios activos de esos roles sin 2FA confirmado: si se activa el 2FA
     * obligatorio solo podrán ir a Perfil > Doble factor hasta activarlo.
     */
    public function usersWithout2fa(array $roles, int $limit = 100): array
    {
        $roles = array_values(array_intersect($roles, $this->roles()));
        if ($roles === []) {
            return ['total' => 0, 'users' => []];
        }

        try {
            $query = User::role($roles)
                ->where(fn ($q) => $q->whereNull('two_factor_secret')->orWhere('two_factor_secret', '')
                    ->orWhereNull('two_factor_confirmed_at'));

            return [
                'total' => (clone $query)->count(),
                'users' => $query->orderBy('email')->limit($limit)->get(['id', 'firstname', 'lastname', 'email'])
                    ->map(fn ($u) => [
                        'id' => $u->id,
                        'name' => trim($u->firstname.' '.$u->lastname),
                        'email' => $u->email,
                        'roles' => $u->getRoleNames()->intersect($roles)->values()->all(),
                    ])->all(),
            ];
        } catch (Throwable) {
            return ['total' => null, 'users' => []];
        }
    }

    /**
     * Reportes CSP de los últimos días (storage/logs/csp-AAAA-MM-DD.log).
     *
     * @return array{days: array<string, int>, total: int}
     */
    public function cspReports(int $days = 7): array
    {
        $out = [];
        $total = 0;

        for ($i = 0; $i < $days; $i++) {
            $date = now()->subDays($i)->format('Y-m-d');
            $file = storage_path("logs/csp-{$date}.log");
            $count = 0;

            if (is_file($file) && is_readable($file) && ($h = @fopen($file, 'r'))) {
                // Como mucho 50 MB por fichero: es un recuento orientativo.
                $read = 0;
                while (($line = fgets($h)) !== false && $read < 52428800) {
                    $read += strlen($line);
                    if (str_contains($line, 'csp-violation')) {
                        $count++;
                    }
                }
                fclose($h);
            }

            $out[$date] = $count;
            $total += $count;
        }

        return ['days' => $out, 'total' => $total];
    }

    /** Estado de security:watch (storage/app/security/state.json) y security:audit. */
    public function watchStatus(): array
    {
        $state = null;
        $file = storage_path('app/security/state.json');
        if (is_file($file) && is_readable($file)) {
            $state = json_decode((string) @file_get_contents($file), true);
        }

        $ts = fn ($v) => is_numeric($v) ? Carbon::createFromTimestamp((int) $v)->setTimezone(config('app.timezone')) : null;

        $mailer = (string) config('mail.default');
        $mail = $mailer !== '' && ! in_array($mailer, ['log', 'array', 'null'], true) && filled(config('mail.from.address'));

        $baseline = base_path((string) config('security.audit.baseline_file', 'docs/seguridad/auditoria_linea_base.json'));

        return [
            'watch_last_run' => is_array($state) ? $ts($state['last_run'] ?? null) : null,
            'watch_duration_ms' => is_array($state) ? ($state['last_duration_ms'] ?? null) : null,
            'watch_initialized_at' => is_array($state) ? $ts($state['initialized_at'] ?? null) : null,
            'watch_quiet_until' => is_array($state) ? $ts($state['quiet_until'] ?? null) : null,
            'window_minutes' => (int) config('security.watch.window_minutes', 5),
            'notify_role' => (string) config('security.watch.notify_role', 'super-admin'),
            'email' => $mail,
            'mailer' => $mailer,
            // security:audit no deja registro de sus ejecuciones; solo la fecha de la línea base.
            'audit_last_run' => null,
            'audit_baseline_at' => is_file($baseline) ? Carbon::createFromTimestamp((int) filemtime($baseline))->setTimezone(config('app.timezone')) : null,
        ];
    }

    public function system(): array
    {
        return [
            'debug' => (bool) config('app.debug'),
            'env' => (string) config('app.env'),
            'maintenance' => app()->isDownForMaintenance(),
            'php' => PHP_VERSION,
            'laravel' => app()->version(),
            'config_cached' => app()->configurationIsCached(),
            'routes_cached' => app()->routesAreCached(),
            'workers' => $this->horizonWorkers(),
            'clamav' => $this->clamav(),
            'composer_audit' => $this->composerAudit(),
            'hsts_app' => (bool) config('security.headers.hsts', false),
            'simulator_public' => (bool) config('helpdesk.simulator_public_enabled', false),
        ];
    }

    /**
     * Procesos horizon:work de esta instalación y su antigüedad, leyendo /proc
     * (sin exec). Un worker arrancado antes de un cambio de código o de .env
     * sigue con lo anterior hasta `php artisan horizon:terminate`.
     */
    public function horizonWorkers(): array
    {
        if (! is_dir('/proc') || ! is_readable('/proc/stat')) {
            return ['available' => false];
        }

        return Cache::remember('security_config.status.workers', 60, function () {
            $btime = null;
            foreach (file('/proc/stat') ?: [] as $line) {
                if (str_starts_with($line, 'btime ')) {
                    $btime = (int) substr($line, 6);
                }
            }
            $hz = 100; // CLK_TCK en Linux x86_64
            $base = realpath(base_path()) ?: base_path();
            $ages = [];

            foreach (glob('/proc/[0-9]*', GLOB_ONLYDIR) ?: [] as $dir) {
                $cmd = @file_get_contents($dir.'/cmdline');
                if (! $cmd || ! str_contains($cmd, 'horizon:work')) {
                    continue;
                }
                if ($this->masterPath((int) basename($dir)) !== $base) {
                    continue;
                }
                $stat = @file_get_contents($dir.'/stat');
                if (! $stat || $btime === null) {
                    continue;
                }
                $fields = explode(' ', substr($stat, strrpos($stat, ')') + 2));
                $start = $btime + (int) (($fields[19] ?? 0) / $hz);
                $ages[] = time() - $start;
            }

            if ($ages === []) {
                return ['available' => true, 'count' => 0];
            }

            return [
                'available' => true,
                'count' => count($ages),
                'oldest_seconds' => max($ages),
                'newest_seconds' => min($ages),
                'started_at' => Carbon::createFromTimestamp(time() - max($ages))->setTimezone(config('app.timezone')),
            ];
        });
    }

    /** Sube por el árbol de procesos hasta el `artisan horizon` maestro y devuelve su base. */
    private function masterPath(int $pid): ?string
    {
        for ($i = 0; $i < 6 && $pid > 1; $i++) {
            $cmd = @file_get_contents("/proc/{$pid}/cmdline");
            if ($cmd) {
                $args = explode("\0", trim($cmd, "\0"));
                foreach ($args as $arg) {
                    if (str_ends_with($arg, '/artisan') && str_starts_with($arg, '/')) {
                        $dir = dirname($arg);

                        return realpath($dir) ?: $dir;
                    }
                }
                $cwd = @readlink("/proc/{$pid}/cwd");
                if ($cwd && in_array('artisan', $args, true) && in_array('horizon', $args, true)) {
                    return realpath($cwd) ?: $cwd;
                }
            }
            $status = @file_get_contents("/proc/{$pid}/status");
            if (! $status || ! preg_match('/^PPid:\s+(\d+)/m', $status, $m)) {
                return null;
            }
            $pid = (int) $m[1];
        }

        return null;
    }

    /** ¿Hay binario de ClamAV? (el escaneo en sí se activa en Media/Helpdesk). */
    public function clamav(): array
    {
        $candidates = array_unique(array_filter([
            (string) config('media.virus_scan.clamscan_path', 'clamscan'),
            (string) config('helpdesk.attachments.virus_scan.clamscan_path', 'clamscan'),
            'clamdscan',
        ]));

        $found = null;
        foreach ($candidates as $bin) {
            if (str_starts_with($bin, '/')) {
                if (is_file($bin) && is_executable($bin)) {
                    $found = $bin;
                    break;
                }

                continue;
            }
            foreach (['/usr/bin', '/usr/local/bin', '/bin', '/usr/sbin'] as $dir) {
                if (is_file("{$dir}/{$bin}") && is_executable("{$dir}/{$bin}")) {
                    $found = "{$dir}/{$bin}";
                    break 2;
                }
            }
        }

        return [
            'binary' => $found,
            'media_enabled' => (bool) config('media.virus_scan.enabled', false),
            'helpdesk_enabled' => (bool) config('helpdesk.attachments.virus_scan.enabled', false),
        ];
    }

    /**
     * Resultado de `composer audit --format=json` si alguien lo ha dejado en
     * storage/app/security/composer-audit.json (p. ej. desde cron). Desde la web
     * no se ejecuta composer (sin HOME/caché de composer, con red y lento).
     */
    public function composerAudit(): ?array
    {
        $file = storage_path('app/security/composer-audit.json');
        if (! is_file($file) || ! is_readable($file)) {
            return null;
        }

        return Cache::remember('security_config.status.composer_audit.'.filemtime($file), 3600, function () use ($file) {
            $data = json_decode((string) @file_get_contents($file), true);
            if (! is_array($data)) {
                return null;
            }
            $advisories = 0;
            foreach ((array) ($data['advisories'] ?? []) as $list) {
                $advisories += is_array($list) ? count($list) : 0;
            }

            return [
                'advisories' => $advisories,
                'abandoned' => count((array) ($data['abandoned'] ?? [])),
                'generated_at' => Carbon::createFromTimestamp((int) filemtime($file))->setTimezone(config('app.timezone')),
            ];
        });
    }
}
