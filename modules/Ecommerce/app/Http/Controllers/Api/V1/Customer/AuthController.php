<?php

namespace Modules\Ecommerce\Http\Controllers\Api\V1\Customer;

use App\Http\Api\V1\BaseApiController;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Modules\Ecommerce\Enums\CustomerStatus;
use Modules\Ecommerce\Events\CustomerEmailVerified;
use Modules\Ecommerce\Events\CustomerRegistered;
use Modules\Ecommerce\Http\Requests\Api\V1\Auth\ForgotPasswordRequest;
use Modules\Ecommerce\Http\Requests\Api\V1\Auth\LoginCustomerRequest;
use Modules\Ecommerce\Http\Requests\Api\V1\Auth\RegisterCustomerRequest;
use Modules\Ecommerce\Http\Requests\Api\V1\Auth\ResetPasswordRequest;
use Modules\Ecommerce\Http\Requests\Api\V1\Auth\SocialLoginRequest;
use Modules\Ecommerce\Http\Resources\Api\V1\CustomerResource;
use Modules\Ecommerce\Models\Customer;
use Modules\Ecommerce\Notifications\ConfirmEmailNotification;

/**
 * @group Autenticación
 *
 * Registro, inicio de sesión y gestión de tokens Sanctum para clientes B2C.
 */
class AuthController extends BaseApiController
{
    /**
     * Registrar cliente
     *
     * Crea una nueva cuenta de cliente y devuelve un token de acceso.
     * Se envía un correo de verificación automáticamente.
     *
     * @unauthenticated
     */
    public function register(RegisterCustomerRequest $request): JsonResponse
    {
        $customer = DB::transaction(function () use ($request) {
            $customer = Customer::create([
                'name' => $request->string('name'),
                'email' => $request->string('email'),
                'password' => $request->string('password'),
                'phone' => $request->input('phone'),
                'status' => CustomerStatus::ACTIVE,
                'email_verification_token' => Str::random(60),
            ]);

            CustomerRegistered::dispatch($customer);

            return $customer;
        });

        $token = $customer->createToken($request->input('device_name', 'mobile'))->plainTextToken;

        return $this->created([
            'customer' => (new CustomerResource($customer))->toArray($request),
            'token' => $token,
            'tokenType' => 'Bearer',
        ], 'Cuenta creada. Revisa tu correo para verificarla.');
    }

    /**
     * Iniciar sesión
     *
     * Autentica al cliente con email y contraseña. Devuelve un token Bearer.
     *
     * @unauthenticated
     */
    public function login(LoginCustomerRequest $request): JsonResponse
    {
        $customer = Customer::query()->where('email', $request->string('email'))->first();

        if (! $customer || ! Hash::check($request->string('password'), $customer->password)) {
            return $this->errorResponse('Credenciales inválidas.', 'INVALID_CREDENTIALS', 401);
        }

        if ($customer->status !== CustomerStatus::ACTIVE) {
            return $this->errorResponse('Tu cuenta no está activa.', 'ACCOUNT_INACTIVE', 403);
        }

        $token = $customer->createToken($request->input('device_name', 'mobile'))->plainTextToken;

        return $this->ok([
            'customer' => (new CustomerResource($customer))->toArray($request),
            'token' => $token,
            'tokenType' => 'Bearer',
        ], 'Sesión iniciada.');
    }

    /**
     * Cerrar sesión
     *
     * Invalida el token de acceso actual.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->noContent('Sesión cerrada.');
    }

    /**
     * Cerrar todas las sesiones
     *
     * Invalida todos los tokens de acceso del cliente en todos los dispositivos.
     */
    public function logoutAll(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        return $this->noContent('Todas las sesiones cerradas.');
    }

    /**
     * Solicitar restablecimiento de contraseña
     *
     * Envía un correo con enlace de restablecimiento si el email existe.
     * Siempre devuelve 200 para evitar enumeración de usuarios.
     *
     * @unauthenticated
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        Password::broker('ecommerce_customers')->sendResetLink([
            'email' => $request->string('email'),
        ]);

        return $this->ok(null, 'Si el correo existe, recibirás un enlace para restablecer tu contraseña.');
    }

    /**
     * Restablecer contraseña
     *
     * Actualiza la contraseña usando el token recibido por correo e invalida todas las sesiones.
     *
     * @unauthenticated
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::broker('ecommerce_customers')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (Customer $customer, string $password) {
                $customer->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                $customer->tokens()->delete();

                event(new PasswordReset($customer));
            }
        );

        if ($status !== Password::PasswordReset) {
            return $this->errorResponse(__($status), 'PASSWORD_RESET_FAILED', 422);
        }

        return $this->ok(null, 'Contraseña restablecida.');
    }

    /**
     * Verificar email
     *
     * Verifica el correo del cliente usando el token enviado al registrarse.
     *
     * @unauthenticated
     *
     * @urlParam token string required Token de verificación enviado al email. Example: abc123xyz
     */
    public function verifyEmail(string $token): JsonResponse
    {
        $customer = Customer::query()->where('email_verification_token', $token)->first();

        if (! $customer) {
            return $this->errorResponse('Token de verificación inválido.', 'INVALID_TOKEN', 404);
        }

        if ($customer->email_verified_at !== null) {
            return $this->ok(null, 'Email ya verificado.');
        }

        $customer->forceFill([
            'email_verified_at' => now(),
            'email_verification_token' => null,
        ])->save();

        CustomerEmailVerified::dispatch($customer);

        return $this->ok(null, 'Email verificado correctamente.');
    }

    /**
     * Reenviar verificación de email
     *
     * Envía un nuevo correo de verificación al cliente autenticado.
     */
    public function resendVerification(Request $request): JsonResponse
    {
        $customer = $request->user();

        if ($customer->email_verified_at !== null) {
            return $this->ok(null, 'Email ya verificado.');
        }

        $token = Str::random(60);
        $customer->forceFill(['email_verification_token' => $token])->save();
        $customer->notify(new ConfirmEmailNotification($token));

        return $this->ok(null, 'Email de verificación enviado.');
    }

    /**
     * Login social (Google / Apple / Facebook)
     *
     * Flutter obtiene el access token del SDK nativo y lo envía aquí.
     * Usamos Socialite en modo stateless para verificar y hacer upsert del Customer.
     *
     * @unauthenticated
     *
     * @urlParam provider string required Proveedor OAuth. Valores: google, apple, facebook. Example: google
     */
    public function social(SocialLoginRequest $request, string $provider): JsonResponse
    {
        if (! in_array($provider, ['google', 'apple', 'facebook'], true)) {
            return $this->errorResponse('Proveedor no soportado.', 'PROVIDER_NOT_SUPPORTED', 422);
        }

        try {
            $providerUser = Socialite::driver($provider)
                ->stateless()
                ->userFromToken($request->string('access_token')->toString());
        } catch (\Throwable $e) {
            return $this->errorResponse('No se pudo verificar el token social.', 'SOCIAL_TOKEN_INVALID', 401);
        }

        if (! $providerUser->getEmail()) {
            return $this->errorResponse('El proveedor no devolvió un correo electrónico.', 'SOCIAL_NO_EMAIL', 422);
        }

        $customer = Customer::query()->updateOrCreate(
            ['email' => $providerUser->getEmail()],
            [
                'name' => $providerUser->getName() ?? $providerUser->getNickname() ?? 'Usuario',
                'avatar' => $providerUser->getAvatar(),
                'provider' => $provider,
                'provider_id' => $providerUser->getId(),
                'status' => CustomerStatus::ACTIVE,
                'email_verified_at' => now(),
                'password' => $providerUser->getEmail() ? Hash::make(Str::random(40)) : null,
            ]
        );

        $token = $customer->createToken($request->input('device_name', 'mobile'))->plainTextToken;

        return $this->ok([
            'customer' => (new CustomerResource($customer))->toArray($request),
            'token' => $token,
            'tokenType' => 'Bearer',
        ], 'Sesión iniciada con '.ucfirst($provider).'.');
    }
}
