<?php

namespace Modules\Auth\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Modules\Auth\Events\LoginFailed;
use Modules\Auth\Events\UserLoggedIn;
use Modules\Auth\Events\UserLoggedOut;
use Modules\Auth\Http\Requests\LoginApiRequest;
use Modules\Auth\Http\Resources\UserResource;
use Modules\Auth\Services\AuthRateLimiter;
use Modules\Auth\Services\DeviceService;
use Modules\Auth\Services\TwoFactorService;

class AuthApiController extends Controller
{
    public function __construct(
        private readonly AuthRateLimiter $limiter,
        private readonly DeviceService $devices,
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

        /** @var User|null $user */
        $user = User::where('email', $identifier)->first();

        if (! $user || ! Hash::check($request->input('password'), $user->password)) {
            $this->limiter->hit('login', $identifier, $request);
            LoginFailed::dispatch($identifier, $request->ip(), $request->userAgent(), $user);

            return response()->json([
                'success' => false,
                'message' => 'Las credenciales proporcionadas no son correctas.',
            ], 422);
        }

        if (! $user->available) {
            LoginFailed::dispatch($identifier, $request->ip(), $request->userAgent(), $user, 'account_disabled');

            return response()->json([
                'success' => false,
                'message' => 'Tu cuenta está deshabilitada.',
            ], 403);
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

        [$userId, $expiresAt] = $this->decodeChallengeToken($request->input('challenge_token'));

        if (! $userId || $expiresAt < time()) {
            return response()->json(['success' => false, 'message' => 'Token de reto inválido o expirado.'], 422);
        }

        /** @var User|null $user */
        $user = User::find($userId);

        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Usuario no encontrado.'], 422);
        }

        $twoFactor = app(TwoFactorService::class);
        $valid = false;

        if ($request->filled('code') && $twoFactor->verify($user->two_factor_secret, $request->input('code'))) {
            $valid = true;
        } elseif ($request->filled('recovery_code')) {
            $codes = $user->two_factor_recovery_codes ?: [];
            $index = array_search($request->input('recovery_code'), $codes, true);

            if ($index !== false) {
                unset($codes[$index]);
                $user->forceFill(['two_factor_recovery_codes' => array_values($codes)])->save();
                $valid = true;
            }
        }

        if (! $valid) {
            return response()->json(['success' => false, 'message' => 'Código incorrecto.'], 422);
        }

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
        $ttl = now()->addMinutes(5)->timestamp;
        $payload = $user->id.'|'.$ttl;
        $signature = hash_hmac('sha256', $payload, config('app.key'));

        return base64_encode($payload.'|'.$signature);
    }

    /**
     * @return array{0: ?int, 1: int}
     */
    private function decodeChallengeToken(string $token): array
    {
        $decoded = base64_decode($token, true);

        if (! $decoded) {
            return [null, 0];
        }

        $parts = explode('|', $decoded);

        if (count($parts) !== 3) {
            return [null, 0];
        }

        [$userId, $expiresAt, $signature] = $parts;
        $expected = hash_hmac('sha256', $userId.'|'.$expiresAt, config('app.key'));

        if (! hash_equals($expected, $signature)) {
            return [null, 0];
        }

        return [(int) $userId, (int) $expiresAt];
    }
}
