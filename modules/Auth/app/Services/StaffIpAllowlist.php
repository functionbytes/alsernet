<?php

namespace Modules\Auth\Services;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Modules\Core\Models\Setting;
use Symfony\Component\HttpFoundation\IpUtils;

/**
 * Filtro por IP del login y del panel del personal (29-sep-2026).
 *
 * Lee el modo y la lista de redes permitidas de `settings` (con caché corta e
 * invalidación al guardar) y decide si una IP / un usuario con excepción
 * remota puede usar las rutas del personal. Ver RestrictStaffAccessByIp.
 */
class StaffIpAllowlist
{
    public const MODE_OFF = 'off';

    public const MODE_MONITOR = 'monitor';

    public const MODE_ENFORCE = 'enforce';

    public const MODES = [self::MODE_OFF, self::MODE_MONITOR, self::MODE_ENFORCE];

    public const SETTING_MODE = 'auth.staff_ip_filter.mode';

    public const SETTING_ALLOWLIST = 'auth.staff_ip_filter.allowlist';

    private const CACHE_KEY = 'auth.staff_ip_filter.state';

    private const CACHE_REMOTE_KEY = 'auth.staff_ip_filter.remote_active';

    private const CACHE_TTL = 60;

    /** Estado memorizado durante la petición. */
    private ?array $state = null;

    public function config(string $key, mixed $default = null): mixed
    {
        return config('auth.auth-policy.staff_ip_filter.'.$key, $default);
    }

    public function isForcedOff(): bool
    {
        return (bool) $this->config('force_off', false);
    }

    /**
     * Modo efectivo: FORCE_OFF (config/.env) > settings > config por defecto.
     */
    public function mode(): string
    {
        if ($this->isForcedOff()) {
            return self::MODE_OFF;
        }

        return $this->state()['mode'];
    }

    /**
     * Origen del modo efectivo (para `auth:ip-filter status` y la pantalla).
     */
    public function modeSource(): string
    {
        if ($this->isForcedOff()) {
            return 'AUTH_STAFF_IP_FILTER_FORCE_OFF';
        }

        return $this->state()['mode_from_settings'] ? 'settings' : 'config';
    }

    /**
     * @return array<int, array{ip: string, description: string}>
     */
    public function entries(): array
    {
        return $this->state()['entries'];
    }

    /**
     * @return array<int, string>
     */
    public function alwaysAllowed(): array
    {
        return array_values((array) $this->config('always_allowed', ['127.0.0.1', '::1']));
    }

    public function isAllowed(?string $ip, ?array $entries = null): bool
    {
        if ($ip === null || $ip === '') {
            return false;
        }

        $cidrs = array_merge(
            $this->alwaysAllowed(),
            array_column($entries ?? $this->entries(), 'ip'),
        );

        return $cidrs !== [] && IpUtils::checkIp($ip, $cidrs);
    }

    /**
     * ¿La IP es una IP o un rango CIDR válido (IPv4 o IPv6)?
     */
    public static function isValidIpOrCidr(string $value): bool
    {
        $value = trim($value);

        if (! str_contains($value, '/')) {
            return filter_var($value, FILTER_VALIDATE_IP) !== false;
        }

        [$ip, $mask] = explode('/', $value, 2);

        if (! ctype_digit($mask)) {
            return false;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
            return (int) $mask >= 0 && (int) $mask <= 32;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false) {
            return (int) $mask >= 0 && (int) $mask <= 128;
        }

        return false;
    }

    /**
     * Convierte el texto de la pantalla ("IP o CIDR  descripción", una por
     * línea) en entradas. Lanza InvalidArgumentException con las líneas malas.
     *
     * @return array<int, array{ip: string, description: string}>
     */
    public static function parseText(string $text): array
    {
        $entries = [];
        $errors = [];

        foreach (preg_split('/\r\n|\r|\n/', $text) as $n => $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            $parts = preg_split('/[\s,;|]+/', $line, 2);
            $ip = trim($parts[0]);
            $description = trim(ltrim($parts[1] ?? '', " \t-#|,;"));

            if (! self::isValidIpOrCidr($ip)) {
                $errors[] = 'Línea '.($n + 1).': "'.$ip.'" no es una IP ni un rango CIDR válido.';

                continue;
            }

            $entries[$ip] = ['ip' => $ip, 'description' => mb_substr($description, 0, 120)];
        }

        if ($errors !== []) {
            throw new InvalidArgumentException(implode(' ', $errors));
        }

        return array_values($entries);
    }

    public static function toText(array $entries): string
    {
        return implode("\n", array_map(
            fn (array $e) => trim($e['ip'].'  '.($e['description'] ?? '')),
            $entries,
        ));
    }

    /**
     * Guarda la lista. Si se indica $actorIp, rechaza una lista que deje fuera
     * a quien la guarda (protección contra bloquearse a uno mismo).
     *
     * @param  array<int, array{ip: string, description: string}>  $entries
     */
    public function saveEntries(array $entries, ?string $actorIp = null): void
    {
        foreach ($entries as $entry) {
            if (! self::isValidIpOrCidr((string) ($entry['ip'] ?? ''))) {
                throw new InvalidArgumentException('Entrada no válida: '.($entry['ip'] ?? ''));
            }
        }

        if ($actorIp !== null && ! $this->isAllowed($actorIp, $entries)) {
            throw new InvalidArgumentException(
                "Tu IP actual ({$actorIp}) no quedaría dentro de la lista: no se guarda para que no te quedes fuera."
            );
        }

        Setting::set(self::SETTING_ALLOWLIST, json_encode(array_values($entries), JSON_UNESCAPED_UNICODE));
        $this->clearCache();
    }

    public function setMode(string $mode): void
    {
        if (! in_array($mode, self::MODES, true)) {
            throw new InvalidArgumentException("Modo no válido: {$mode}");
        }

        Setting::set(self::SETTING_MODE, $mode);
        $this->clearCache();
    }

    public function clearCache(): void
    {
        $this->state = null;
        Cache::forget(self::CACHE_KEY);
        Cache::forget(self::CACHE_REMOTE_KEY);
        Cache::forget('setting_'.self::SETTING_MODE);
        Cache::forget('setting_'.self::SETTING_ALLOWLIST);
    }

    // ---------------------------------------------------------------------
    // Excepción remota por usuario (users.remote_access_enabled / _until)
    // ---------------------------------------------------------------------

    public static function remoteColumnsExist(): bool
    {
        static $exists = null;

        return $exists ??= Schema::hasColumn('users', 'remote_access_enabled');
    }

    /**
     * Excepción concedida y no caducada (sin mirar el 2FA).
     */
    public function hasRemoteException(?User $user): bool
    {
        if (! $user || ! self::remoteColumnsExist() || ! (bool) $user->getAttribute('remote_access_enabled')) {
            return false;
        }

        $until = $user->getAttribute('remote_access_until');

        return $until === null || Carbon::parse($until)->isFuture();
    }

    /**
     * La excepción solo surte efecto con 2FA activado en la cuenta.
     */
    public function remoteExceptionUsable(?User $user): bool
    {
        return $this->hasRemoteException($user)
            && method_exists($user, 'hasTwoFactorEnabled')
            && $user->hasTwoFactorEnabled();
    }

    /**
     * ¿Hay al menos un usuario con excepción remota vigente? (Si no, en enforce
     * ni siquiera se muestra el formulario de login desde fuera.)
     */
    public function anyRemoteExceptionActive(): bool
    {
        if (! self::remoteColumnsExist()) {
            return false;
        }

        return (bool) Cache::remember(self::CACHE_REMOTE_KEY, self::CACHE_TTL, fn () => User::query()
            ->where('remote_access_enabled', true)
            ->where(fn ($q) => $q->whereNull('remote_access_until')->orWhere('remote_access_until', '>', now()))
            ->whereNotNull('two_factor_confirmed_at')
            ->exists());
    }

    public function setRemoteAccess(User $target, bool $enabled, ?Carbon $until, User $actor): void
    {
        if (! self::remoteColumnsExist()) {
            throw new InvalidArgumentException('Falta la migración de acceso remoto en users.');
        }

        $before = [
            'remote_access_enabled' => (bool) $target->getAttribute('remote_access_enabled'),
            'remote_access_until' => $target->getAttribute('remote_access_until'),
        ];

        $target->forceFill([
            'remote_access_enabled' => $enabled,
            'remote_access_until' => $enabled ? $until : null,
        ])->save();

        Cache::forget(self::CACHE_REMOTE_KEY);

        try {
            activity()
                ->causedBy($actor)
                ->performedOn($target)
                ->event($enabled ? 'remote_access_granted' : 'remote_access_revoked')
                ->withProperties([
                    'old' => $before,
                    'attributes' => [
                        'remote_access_enabled' => $enabled,
                        'remote_access_until' => $enabled ? $until?->toDateTimeString() : null,
                    ],
                    'two_factor_enabled' => $target->hasTwoFactorEnabled(),
                    'ip' => request()->ip(),
                ])
                ->log($enabled ? 'Excepción de acceso remoto concedida' : 'Excepción de acceso remoto retirada');
        } catch (\Throwable) {
            // El registro de actividad no debe impedir el cambio.
        }
    }

    // ---------------------------------------------------------------------

    /**
     * @return array{mode: string, mode_from_settings: bool, entries: array}
     */
    private function state(): array
    {
        if ($this->state !== null) {
            return $this->state;
        }

        try {
            $state = Cache::remember(self::CACHE_KEY, self::CACHE_TTL, fn () => $this->loadState());
        } catch (\Throwable) {
            $state = $this->loadState();
        }

        return $this->state = $state;
    }

    private function loadState(): array
    {
        $default = (string) $this->config('mode', self::MODE_MONITOR);
        $default = in_array($default, self::MODES, true) ? $default : self::MODE_MONITOR;

        try {
            $storedMode = Setting::get(self::SETTING_MODE);
            $storedList = Setting::get(self::SETTING_ALLOWLIST);
        } catch (\Throwable) {
            $storedMode = null;
            $storedList = null;
        }

        $fromSettings = is_string($storedMode) && in_array($storedMode, self::MODES, true);

        $entries = [];
        $decoded = is_string($storedList) ? json_decode($storedList, true) : null;

        foreach ((array) $decoded as $row) {
            $ip = is_array($row) ? (string) ($row['ip'] ?? '') : (string) $row;

            if (self::isValidIpOrCidr($ip)) {
                $entries[] = ['ip' => trim($ip), 'description' => is_array($row) ? (string) ($row['description'] ?? '') : ''];
            }
        }

        return [
            'mode' => $fromSettings ? $storedMode : $default,
            'mode_from_settings' => $fromSettings,
            'entries' => $entries,
        ];
    }
}
