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

class AuthController extends BaseApiController
{
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

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->noContent('Sesión cerrada.');
    }

    public function logoutAll(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        return $this->noContent('Todas las sesiones cerradas.');
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        Password::broker('ecommerce_customers')->sendResetLink([
            'email' => $request->string('email'),
        ]);

        return $this->ok(null, 'Si el correo existe, recibirás un enlace para restablecer tu contraseña.');
    }

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
     * Social login (Google / Apple / Facebook). Flutter obtains the access token
     * from the native SDK and posts it here. We use Socialite stateless mode to
     * resolve the user info, then upsert the Customer.
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
