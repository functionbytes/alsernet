<?php

namespace Modules\Auth\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Modules\Auth\Events\TwoFactorFailed;
use Modules\Auth\Events\TwoFactorVerified;
use Modules\Auth\Events\UserLoggedIn;
use Modules\Auth\Events\UserLoggedOut;
use Modules\Auth\Http\Requests\LoginApiRequest;
use Modules\Auth\Http\Resources\UserResource;
use Modules\Auth\Services\AuthRateLimiter;
use Modules\Auth\Services\AuthService;
use Modules\Auth\Services\DeviceService;
use Modules\Auth\Services\TwoFactorService;

class AuthApiController extends Controller
{
    public function __construct(
        private readonly AuthRateLimiter $limiter,
        private readonly DeviceService $devices,
        private readonly AuthService $auth,
    ) {}

    public function login(LoginApiRequest $request): JsonResponse
    {
        $identifier = $request->input('email');
        $check = $this->limiter->check('login', $identifier, $request);

        if (! $check['allowed']) {
            return response()->json([
                'success' => false,
                'message' => "Demasiados intentos. Reintenta en {$check['seconds']}s.",
            ], 429);
        }

        // 29-sep-2026: mismo camino que el login web (bloqueo de cuenta y
        // contador de fallos persistente), sin abrir sesión en el guard web.
        $user = $this->auth->validateCredentials(
            ['email' => $identifier, 'password' => (string) $request->input('password')],
            $request,
        );

        if (! $user) {
            $this->limiter->hit('login', $identifier, $request);

            // Mensaje genérico: no distinguir cuenta inexistente, bloqueada o deshabilitada.
            return response()->json([
                'success' => false,
                'message' => 'Las credenciales proporcionadas no son correctas.',
            ], 422);
        }

        if ($user->hasTwoFactorEnabled()) {
            return response()->json([
                'success' => true,
                'two_factor_required' => true,
                'challenge_token' => $this->issueChallengeToken($user),
                'message' => 'Se requiere verificación 2FA.',
            ]);
        }

        $this->limiter->clear('login', $identifier, $request);

        return $this->issueToken($user, $request);
    }

    public function twoFactorChallenge(Request $request): JsonResponse
    {
        $request->validate([
            'challenge_token' => ['required', 'string'],
            'code' => ['nullable', 'string'],
            'recovery_code' => ['nullable', 'string'],
        ]);

        [$userId, $expiresAt, $nonce] = $this->decodeChallengeToken($request->input('challenge_token'));

        // 29-sep-2026: el challenge_token es de un solo uso (nonce en caché) y
        // se invalida tras demasiados fallos; además hay limitador por usuario.
        if (! $userId || $expiresAt < time() || ! Cache::has($this->challengeCacheKey($nonce))) {
            return response()->json(['success' => false, 'message' => 'Token de reto inválido o expirado.'], 422);
        }

        $check = $this->limiter->check('two_factor', (string) $userId, $request);

        if (! $check['allowed']) {
            return response()->json([
                'success' => false,
                'message' => "Demasiados intentos. Reintenta en {$check['seconds']}s.",
            ], 429);
        }

        /** @var User|null $user */
        $user = User::find($userId);

        if (! $user || ! $user->available || $user->isLocked() || ! $user->hasTwoFactorEnabled()) {
            Cache::forget($this->challengeCacheKey($nonce));

            return response()->json(['success' => false, 'message' => 'Token de reto inválido o expirado.'], 422);
        }

        $twoFactor = app(TwoFactorService::class);
        $valid = false;
        $method = null;

        if ($request->filled('code') && $twoFactor->verifyOnce($user->two_factor_secret, (string) $request->input('code'), $user->id)) {
            $valid = true;
            $method = 'otp';
        } elseif ($request->filled('recovery_code')) {
            $codes = $user->two_factor_recovery_codes ?: [];
            $index = array_search(trim((string) $request->input('recovery_code')), $codes, true);

            if ($index !== false) {
                unset($codes[$index]);
                $user->forceFill(['two_factor_recovery_codes' => array_values($codes)])->save();
                $valid = true;
                $method = 'recovery_code';
            }
        }

        if (! $valid) {
            $this->limiter->hit('two_factor', (string) $userId, $request);
            $this->auth->registerFailure($user);
            TwoFactorFailed::dispatch($user, $request->ip());

            $attempts = (int) Cache::increment($this->challengeCacheKey($nonce));
            if ($attempts >= (int) config('auth.auth-policy.rate_limits.two_factor.max_attempts', 5)) {
                Cache::forget($this->challengeCacheKey($nonce));
            }

            return response()->json(['success' => false, 'message' => 'Código incorrecto.'], 422);
        }

        Cache::forget($this->challengeCacheKey($nonce));
        $this->limiter->clear('two_factor', (string) $userId, $request);
        TwoFactorVerified::dispatch($user, $request->ip(), $method);

        return $this->issueToken($user, $request);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => new UserResource($request->user()->load('roles')),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $request->user()->currentAccessToken()->delete();

        UserLoggedOut::dispatch($user, $request->ip());

        return response()->json(['success' => true, 'message' => 'Sesión cerrada.']);
    }

    public function logoutAll(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        return response()->json(['success' => true, 'message' => 'Todas las sesiones API cerradas.']);
    }

    private function issueToken(User $user, Request $request): JsonResponse
    {
        $deviceName = (string) ($request->input('device_name') ?: 'api');
        $token = $user->createToken($deviceName)->plainTextToken;

        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->save();

        $this->devices->registerForUser($user, $request);

        UserLoggedIn::dispatch($user, $request->ip(), $request->userAgent());

        return response()->json([
            'success' => true,
            'message' => 'Inicio de sesión exitoso.',
            'data' => [
                'token' => $token,
                'token_type' => 'Bearer',
                'user' => new UserResource($user->load('roles')),
            ],
        ]);
    }

    private function issueChallengeToken(User $user): string
    {
        $ttlSeconds = 300;
        $expiresAt = now()->addSeconds($ttlSeconds)->timestamp;
        $nonce = Str::random(40);
        $payload = $user->id.'|'.$expiresAt.'|'.$nonce;
        $signature = hash_hmac('sha256', $payload, config('app.key'));

        // Valor = nº de intentos fallidos con este token.
        Cache::put($this->challengeCacheKey($nonce), 0, $ttlSeconds);

        return base64_encode($payload.'|'.$signature);
    }

    /**
     * @return array{0: ?int, 1: int, 2: string}
     */
    private function decodeChallengeToken(string $token): array
    {
        $decoded = base64_decode($token, true);

        if (! $decoded) {
            return [null, 0, ''];
        }

        $parts = explode('|', $decoded);

        if (count($parts) !== 4) {
            return [null, 0, ''];
        }

        [$userId, $expiresAt, $nonce, $signature] = $parts;
        $expected = hash_hmac('sha256', $userId.'|'.$expiresAt.'|'.$nonce, config('app.key'));

        if (! hash_equals($expected, $signature) || $nonce === '') {
            return [null, 0, ''];
        }

        return [(int) $userId, (int) $expiresAt, $nonce];
    }

    private function challengeCacheKey(string $nonce): string
    {
        return 'auth:api-2fa-challenge:'.hash('sha256', $nonce);
    }
}
