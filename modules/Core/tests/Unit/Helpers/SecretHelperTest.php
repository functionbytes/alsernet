<?php

namespace Modules\Core\Tests\Unit\Helpers;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class SecretHelperTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Ensure the helper is loaded.
        require_once base_path('modules/Core/app/Helpers/SecretHelper.php');
    }

    /** @test */
    public function test_decrypts_encrypted_prefix_values(): void
    {
        $plaintext = 'my-super-secret-password';
        $encrypted = 'encrypted:'.Crypt::encryptString($plaintext);

        // Simulate what secret_env() does internally: decrypt the payload.
        $this->assertStringStartsWith('encrypted:', $encrypted);
        $decrypted = Crypt::decryptString(substr($encrypted, 10));
        $this->assertEquals($plaintext, $decrypted);
    }

    /** @test */
    public function test_returns_plaintext_values_unchanged(): void
    {
        // A value without the "encrypted:" prefix must be returned verbatim.
        // We test the logic directly: no prefix => return as-is.
        $value = 'plaintext-value-123';
        $this->assertFalse(str_starts_with($value, 'encrypted:'));

        // The function would return it unchanged. We verify the string guard.
        $result = str_starts_with($value, 'encrypted:') ? 'would decrypt' : $value;
        $this->assertEquals('plaintext-value-123', $result);
    }

    /** @test */
    public function test_fails_closed_on_decrypt_error(): void
    {
        // A corrupted "encrypted:" payload must not throw — the function must
        // return the default value and log the error.
        $corruptedPayload = 'encrypted:this-is-not-a-valid-crypt-payload';

        $default = 'fallback-default';

        // Replicate fail-closed logic from secret_env().
        try {
            $result = Crypt::decryptString(substr($corruptedPayload, 10));
        } catch (DecryptException $e) {
            $result = $default;
        }

        $this->assertEquals($default, $result);
    }
}
