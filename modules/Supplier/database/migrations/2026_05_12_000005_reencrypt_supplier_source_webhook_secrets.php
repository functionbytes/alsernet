<?php

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Re-encrypt legacy plaintext values in `supplier_source_webhooks`.
     *
     * The `secret_key` (cast: encrypted) and `auth_config` (cast: encrypted:array)
     * columns were recently switched to encrypted casts on the SourceWebhook model.
     * Rows written before that change still hold plaintext, which makes the model
     * throw a DecryptException on read. This migration rewrites those rows using
     * the exact same primitives the cast uses:
     *   - encrypted        => Crypt::encryptString() / Crypt::decryptString()
     *   - encrypted:array  => Crypt::encryptString(json_encode($value)) on write,
     *                         json_decode(Crypt::decryptString($value), true) on read
     *
     * It is idempotent: a value that already decrypts cleanly is left untouched.
     */
    public function up(): void
    {
        if (! Schema::hasTable('supplier_source_webhooks')) {
            return;
        }

        DB::transaction(function (): void {
            $rows = DB::table('supplier_source_webhooks')->get();

            foreach ($rows as $row) {
                $updates = [];

                $secretKey = $this->reencryptSecretKey($row->secret_key ?? null);
                if ($secretKey !== null) {
                    $updates['secret_key'] = $secretKey;
                }

                $authConfig = $this->reencryptAuthConfig($row->auth_config ?? null);
                if ($authConfig !== null) {
                    $updates['auth_config'] = $authConfig;
                }

                if ($updates !== []) {
                    DB::table('supplier_source_webhooks')
                        ->where('id', $row->id)
                        ->update($updates);
                }
            }
        });
    }

    /**
     * Reverting an encryption operation cannot be done safely: once a column has
     * been re-encrypted there is no reliable way to know which rows were plaintext
     * before. Decrypting blindly would also leak secrets back into the database.
     * Intentionally left empty.
     */
    public function down(): void
    {
        // No-op: irreversible by design (see method docblock).
    }

    /**
     * Return the encrypted form of a plaintext `secret_key`, or null when the
     * value is empty or already encrypted (nothing to do).
     */
    private function reencryptSecretKey(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            Crypt::decryptString($value);

            return null; // Already encrypted.
        } catch (DecryptException) {
            return Crypt::encryptString($value);
        }
    }

    /**
     * Return the encrypted form of a plaintext `auth_config` (JSON-encoded array),
     * or null when the value is empty or already encrypted (nothing to do).
     */
    private function reencryptAuthConfig(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            Crypt::decryptString($value);

            return null; // Already encrypted.
        } catch (DecryptException) {
            $decoded = json_decode($value, true);

            // Fall back to the raw string if it was not valid JSON, so the model's
            // encrypted:array cast still has something it can json_decode later.
            $payload = is_array($decoded) ? $decoded : (array) $value;

            return Crypt::encryptString(json_encode($payload));
        }
    }
};
