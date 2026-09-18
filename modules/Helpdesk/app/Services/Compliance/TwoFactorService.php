<?php

namespace Modules\Helpdesk\Services\Compliance;

use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorService
{
    public function __construct(private readonly Google2FA $google2fa) {}

    /**
     * Generate a new TOTP secret and 8 recovery codes for the user.
     * Does NOT confirm — user must call confirmEnable() with a valid code.
     *
     * @return array{secret: string, qr_code_url: string, recovery_codes: string[]}
     */
    public function enableForUser(User $user): array
    {
        $secret = $this->google2fa->generateSecretKey();
        $recoveryCodes = $this->generateRecoveryCodes();

        $user->forceFill([
            'two_factor_secret' => Crypt::encryptString($secret),
            'two_factor_recovery_codes' => Crypt::encryptString(
                json_encode(array_map(fn ($code) => Hash::make($code), $recoveryCodes))
            ),
            'two_factor_confirmed_at' => null,
        ])->save();

        $qrCodeUrl = $this->google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secret
        );

        return [
            'secret' => $secret,
            'qr_code_url' => $qrCodeUrl,
            'recovery_codes' => $recoveryCodes,
        ];
    }

    /**
     * Verify the first TOTP code and mark 2FA as confirmed.
     */
    public function confirmEnable(User $user, string $code): bool
    {
        if (! $user->two_factor_secret) {
            return false;
        }

        $secret = Crypt::decryptString($user->two_factor_secret);

        if (! $this->google2fa->verifyKey($secret, $code)) {
            return false;
        }

        $user->forceFill(['two_factor_confirmed_at' => now()])->save();

        return true;
    }

    /**
     * Disable 2FA for the user, clearing all related columns.
     */
    public function disableForUser(User $user): void
    {
        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();
    }

    /**
     * Validate a TOTP code or a one-time recovery code.
     * Recovery codes are consumed on use.
     */
    public function verify(User $user, string $code): bool
    {
        if (! $user->two_factor_confirmed_at || ! $user->two_factor_secret) {
            return false;
        }

        $secret = Crypt::decryptString($user->two_factor_secret);

        // Try TOTP first
        if ($this->google2fa->verifyKey($secret, $code)) {
            return true;
        }

        // Fall back to recovery codes
        return $this->verifyRecoveryCode($user, $code);
    }

    /**
     * Check if 2FA is enabled and confirmed for the user.
     */
    public function isEnabled(User $user): bool
    {
        return $user->two_factor_confirmed_at !== null;
    }

    /**
     * Generate 8 random recovery codes in XXXX-XXXX format.
     *
     * @return string[]
     */
    private function generateRecoveryCodes(): array
    {
        return array_map(
            fn () => Str::upper(Str::random(4)).'-'.Str::upper(Str::random(4)),
            range(1, 8)
        );
    }

    /**
     * Verify a recovery code and remove it from the stored list if valid.
     */
    private function verifyRecoveryCode(User $user, string $code): bool
    {
        if (! $user->two_factor_recovery_codes) {
            return false;
        }

        $hashedCodes = json_decode(
            Crypt::decryptString($user->two_factor_recovery_codes),
            true
        );

        if (! is_array($hashedCodes)) {
            return false;
        }

        foreach ($hashedCodes as $index => $hashed) {
            if (Hash::check($code, $hashed)) {
                // Consume this code — one use only
                unset($hashedCodes[$index]);

                $user->forceFill([
                    'two_factor_recovery_codes' => Crypt::encryptString(
                        json_encode(array_values($hashedCodes))
                    ),
                ])->save();

                return true;
            }
        }

        return false;
    }
}
