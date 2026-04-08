<?php

namespace Modules\Auth\Services;

use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * TOTP (Time-based One-Time Password) service.
 *
 * Implements RFC 6238 TOTP using HMAC-SHA1, compatible with
 * Google Authenticator, Authy, and Microsoft Authenticator.
 */
class TwoFactorService
{
    private const BASE32_CHARS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    private const PERIOD = 30;

    private const DIGITS = 6;

    private const WINDOW = 1; // Allow ±1 period to account for clock drift

    /**
     * Generate a new random TOTP secret (base32 encoded).
     */
    public function generateSecret(): string
    {
        $bytes = random_bytes(20); // 160 bits = 20 bytes

        return $this->base32Encode($bytes);
    }

    /**
     * Build the otpauth:// URI for QR code generation.
     */
    public function getQrCodeUri(string $secret, string $email, string $issuer): string
    {
        $issuer = rawurlencode($issuer);
        $email = rawurlencode($email);

        return "otpauth://totp/{$issuer}:{$email}?secret={$secret}&issuer={$issuer}&digits=6&period=30&algorithm=SHA1";
    }

    /**
     * Generate a QR code as SVG data URI for embedding inline.
     */
    public function getQrCodeSvg(string $secret, string $email, string $issuer): string
    {
        $uri = $this->getQrCodeUri($secret, $email, $issuer);

        return QrCode::format('svg')
            ->size(200)
            ->generate($uri);
    }

    /**
     * Verify a TOTP code against the secret.
     * Accepts codes within a ±WINDOW period window.
     */
    public function verify(string $secret, string $code): bool
    {
        $code = preg_replace('/\s+/', '', $code);

        if (! ctype_digit($code) || strlen($code) !== self::DIGITS) {
            return false;
        }

        $timestamp = (int) floor(time() / self::PERIOD);

        for ($offset = -self::WINDOW; $offset <= self::WINDOW; $offset++) {
            if ($this->computeTotp($secret, $timestamp + $offset) === $code) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate recovery codes (8 codes, 10 characters each).
     *
     * @return array<string>
     */
    public function generateRecoveryCodes(): array
    {
        return array_map(
            fn () => strtoupper(Str::random(5).'-'.Str::random(5)),
            range(1, 8)
        );
    }

    /**
     * Compute the TOTP value for a given secret and timestamp counter.
     */
    private function computeTotp(string $secret, int $counter): string
    {
        $key = $this->base32Decode($secret);
        $time = pack('N*', 0).pack('N*', $counter); // 8-byte big-endian counter

        $hash = hash_hmac('sha1', $time, $key, true);
        $offset = ord($hash[19]) & 0x0F;

        $code = (
            ((ord($hash[$offset]) & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8) |
            (ord($hash[$offset + 3]) & 0xFF)
        ) % (10 ** self::DIGITS);

        return str_pad((string) $code, self::DIGITS, '0', STR_PAD_LEFT);
    }

    /**
     * Encode bytes to base32 string.
     */
    private function base32Encode(string $input): string
    {
        $output = '';
        $buffer = 0;
        $bitsLeft = 0;

        foreach (str_split($input) as $byte) {
            $buffer = ($buffer << 8) | ord($byte);
            $bitsLeft += 8;

            while ($bitsLeft >= 5) {
                $bitsLeft -= 5;
                $output .= self::BASE32_CHARS[($buffer >> $bitsLeft) & 0x1F];
            }
        }

        if ($bitsLeft > 0) {
            $output .= self::BASE32_CHARS[($buffer << (5 - $bitsLeft)) & 0x1F];
        }

        return $output;
    }

    /**
     * Decode a base32 string to bytes.
     */
    private function base32Decode(string $input): string
    {
        $input = strtoupper(preg_replace('/[^A-Z2-7]/', '', $input));
        $output = '';
        $buffer = 0;
        $bitsLeft = 0;

        foreach (str_split($input) as $char) {
            $pos = strpos(self::BASE32_CHARS, $char);

            if ($pos === false) {
                continue;
            }

            $buffer = ($buffer << 5) | $pos;
            $bitsLeft += 5;

            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $output .= chr(($buffer >> $bitsLeft) & 0xFF);
            }
        }

        return $output;
    }
}
