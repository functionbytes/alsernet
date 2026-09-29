<?php

namespace Modules\Auth\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\Auth\Models\LoginAttempt;
use Modules\Auth\Services\ImpersonationService;
use Modules\Auth\Services\StaffIpAllowlist;
use Symfony\Component\HttpFoundation\Response;

/**
 * Filtra por IP el login y el panel del personal (29-sep-2026).
 *
 * Se añade a los grupos `web` y `api` desde AuthServiceProvider y solo actúa en
 * las rutas de `auth-policy.staff_ip_filter.web_paths` / `api_paths` (login,
 * recuperación de contraseña, magic link, 2FA, /panel/*, /impersonate/*,
 * /broadcasting/auth, /api/auth/*). El portal, el widget, los webhooks, etc.
 * no pasan por aquí.
 *
 * Orden de decisión:
 *  1. modo off → pasa.
 *  2. IP en la lista (o 127.0.0.1 / ::1) → pasa.
 *  3. Usuario real (el impersonador si hay impersonación) con excepción remota
 *     vigente, 2FA activado y verificado en esta sesión, dentro de la duración
 *     máxima de sesión remota → pasa.
 *  4. Resto: se registra en login_attempts (status ip_not_allowed).
 *     - monitor: deja pasar.
 *     - enforce: 403 genérico; si había sesión se cierra. Excepción: el flujo
 *       de login (login, 2FA, logout) se deja abrir si existe alguna excepción
 *       remota vigente, para que ese personal pueda identificarse.
 */
class RestrictStaffAccessByIp
{
    public const STATUS_NOT_ALLOWED = 'ip_not_allowed';

    public const STATUS_REMOTE = 'ip_remote_access';

    public const SESSION_REMOTE_SINCE = 'auth.remote_access_since';

    public function __construct(
        private readonly StaffIpAllowlist $filter,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->appliesTo($request)) {
            return $next($request);
        }

        $mode = $this->filter->mode();

        if ($mode === StaffIpAllowlist::MODE_OFF) {
            return $next($request);
        }

        $ip = (string) $request->ip();

        if ($this->filter->isAllowed($ip)) {
            return $next($request);
        }

        $user = $this->realUser($request);

        if ($user && $this->remoteAllowed($request, $user)) {
            return $next($request);
        }

        $this->record($request, $user, $mode);

        if ($mode !== StaffIpAllowlist::MODE_ENFORCE) {
            return $next($request);
        }

        if (! $user && $this->isLoginFlow($request) && $this->filter->anyRemoteExceptionActive()) {
            return $next($request);
        }

        if ($user && $request->hasSession()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return $this->deny($request);
    }

    public function appliesTo(Request $request): bool
    {
        $exclude = (array) $this->filter->config('exclude_paths', []);

        if ($exclude !== [] && $request->is(...$exclude)) {
            return false;
        }

        $paths = $request->is('api', 'api/*')
            ? (array) $this->filter->config('api_paths', [])
            : (array) $this->filter->config('web_paths', []);

        if ($paths !== [] && $request->is(...$paths)) {
            return true;
        }

        return (bool) $this->filter->config('web_authenticated_routes', true)
            && $this->isWebAuthenticatedRoute($request);
    }

    /**
     * Ruta con sesión web y `auth` del guard web (AJAX del panel fuera de /panel).
     * `auth:sanctum` no entra: lo usan también integraciones con token.
     */
    private function isWebAuthenticatedRoute(Request $request): bool
    {
        $route = $request->route();

        if (! $route || ! $request->hasSession()) {
            return false;
        }

        $middleware = $route->gatherMiddleware();

        if (! in_array('web', $middleware, true)) {
            return false;
        }

        foreach ($middleware as $m) {
            if (! is_string($m)) {
                continue;
            }

            [$name, $params] = array_pad(explode(':', $m, 2), 2, null);

            if (in_array($name, ['auth', Authenticate::class], true)
                && ($params === null || in_array('web', explode(',', $params), true))) {
                return true;
            }
        }

        return false;
    }

    private function isLoginFlow(Request $request): bool
    {
        $paths = (array) $this->filter->config('login_flow_paths', []);

        return $paths !== [] && $request->is(...$paths);
    }

    /**
     * Usuario "real" de la sesión web. En una impersonación se evalúa al
     * impersonador, no al impersonado. En la API (sin sesión) no hay usuario:
     * la excepción remota exige 2FA verificado en sesión.
     */
    private function realUser(Request $request): ?User
    {
        if (! $request->hasSession()) {
            return null;
        }

        try {
            $user = Auth::guard('web')->user();
        } catch (\Throwable) {
            return null;
        }

        if (! $user instanceof User) {
            return null;
        }

        $impersonatorId = $request->session()->get(ImpersonationService::SESSION_KEY);

        if ($impersonatorId) {
            return User::find($impersonatorId);
        }

        return $user;
    }

    private function remoteAllowed(Request $request, User $user): bool
    {
        if (! $this->filter->remoteExceptionUsable($user) || ! $request->session()->get('two_factor_passed')) {
            return false;
        }

        $session = $request->session();
        $since = (int) $session->get(self::SESSION_REMOTE_SINCE, 0);
        $maxHours = max(1, (int) $this->filter->config('remote_session_hours', 8));

        if ($since === 0) {
            $session->put(self::SESSION_REMOTE_SINCE, time());
            $this->log($request, $user, self::STATUS_REMOTE, 'Excepción remota (2FA verificado)', true);

            return true;
        }

        return (time() - $since) <= $maxHours * 3600;
    }

    private function record(Request $request, ?User $user, string $mode): void
    {
        $reason = sprintf('[%s] %s /%s', $mode, $request->method(), ltrim($request->path(), '/'));

        if ($user && $this->filter->hasRemoteException($user)) {
            $reason .= ' (excepción remota sin 2FA verificado o sesión remota caducada)';
        }

        $this->log($request, $user, self::STATUS_NOT_ALLOWED, $reason);
    }

    private function log(Request $request, ?User $user, string $status, string $reason, bool $force = false): void
    {
        try {
            $minutes = max(1, (int) $this->filter->config('log_dedupe_minutes', 10));
            $key = 'auth:ipf:log:'.sha1($status.'|'.$request->ip().'|'.($user?->id ?? '-').'|'.$request->method().'|'.$request->path());

            if (! $force && ! Cache::add($key, 1, $minutes * 60)) {
                return;
            }

            $email = $user?->email;

            if (! $email && $request->isMethod('post') && $request->is('login', 'api/auth/login', 'magic-link', 'forgot-password')) {
                $email = is_string($request->input('email')) ? mb_substr($request->input('email'), 0, 191) : null;
            }

            LoginAttempt::create([
                'user_id' => $user?->id,
                'email' => $email,
                'ip_address' => $request->ip(),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
                'status' => $status,
                'reason' => mb_substr($reason, 0, 255),
                'attempted_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // El registro nunca debe tumbar la petición.
            Log::warning('RestrictStaffAccessByIp: no se pudo registrar el acceso', ['error' => $e->getMessage()]);
        }
    }

    private function deny(Request $request): Response
    {
        if ($request->expectsJson() || $request->is('api/*', 'broadcasting/auth')) {
            return response()->json(['message' => 'Acceso no disponible.'], 403);
        }

        return response()->view('auth::errors.access-unavailable', [], 403);
    }
}
