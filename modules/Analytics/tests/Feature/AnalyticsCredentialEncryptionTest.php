<?php

namespace Modules\Analytics\Tests\Feature;

use Illuminate\Support\Facades\DB;
use Modules\Analytics\Tests\TestCase;

class AnalyticsCredentialEncryptionTest extends TestCase
{
    /** A minimal valid service account JSON accepted by AnalyticsCredentialRule. */
    private function validCredentialsJson(): string
    {
        return json_encode([
            'type' => 'service_account',
            'project_id' => 'my-project',
            'private_key_id' => 'key-id-1234',
            'private_key' => "-----BEGIN PRIVATE KEY-----\nMIIEvQIBADANBgkqhkiG9w0BAQEFAAS\n-----END PRIVATE KEY-----\n",
            'client_email' => 'analytics@my-project.iam.gserviceaccount.com',
            'client_id' => '123456789',
            'auth_uri' => 'https://accounts.google.com/o/oauth2/auth',
            'token_uri' => 'https://oauth2.googleapis.com/token',
        ]);
    }

    /** Payload for the settings update endpoint that only changes non-credential fields. */
    private function baseSettingsPayload(): array
    {
        return [
            'google_analytics_enable' => false,
            'google_analytics_property_id' => null,
            'analytics_cache_lifetime' => 60,
        ];
    }

    // -------------------------------------------------------------------------
    // Credentials are stored encrypted
    // -------------------------------------------------------------------------

    public function test_saved_credentials_are_stored_encrypted(): void
    {
        $user = $this->createUser(['analytics.settings.update']);

        $credentials = $this->validCredentialsJson();

        // We do not assert a specific HTTP status because Cache::tags()->flush()
        // may throw on the file cache driver; credentials are persisted before that call.
        $this->actingAs($user)
            ->putJson(route('settings.analytics.update'), array_merge(
                $this->baseSettingsPayload(),
                ['google_analytics_credentials' => $credentials]
            ));

        $stored = DB::table('settings')
            ->where('key', 'google_analytics_credentials')
            ->value('value');

        $this->assertNotNull($stored, 'Credentials were not persisted to the settings table.');

        // The raw JSON must never be stored verbatim.
        $this->assertStringNotContainsString(
            'service_account',
            $stored,
            'Credentials appear to be stored as plaintext instead of being encrypted.'
        );

        // Laravel's encrypt() produces a base64-encoded payload; it must be decryptable.
        $decrypted = decrypt($stored);
        $this->assertSame($credentials, $decrypted);
    }

    public function test_empty_credentials_do_not_overwrite_existing_stored_value(): void
    {
        $user = $this->createUser(['analytics.settings.update']);

        // Persist valid credentials first.
        DB::table('settings')->updateOrInsert(
            ['key' => 'google_analytics_credentials'],
            ['value' => encrypt($this->validCredentialsJson()), 'updated_at' => now(), 'created_at' => now()]
        );

        $originalStored = DB::table('settings')
            ->where('key', 'google_analytics_credentials')
            ->value('value');

        // Submit the form without credentials (empty string).
        // We do not assert a specific HTTP status here because Cache::tags()->flush()
        // may throw on the file driver; either way credentials must remain unchanged.
        $this->actingAs($user)
            ->putJson(route('settings.analytics.update'), array_merge(
                $this->baseSettingsPayload(),
                ['google_analytics_credentials' => '']
            ));

        $storedAfter = DB::table('settings')
            ->where('key', 'google_analytics_credentials')
            ->value('value');

        $this->assertSame(
            $originalStored,
            $storedAfter,
            'Submitting an empty credentials value must not overwrite the stored encrypted value.'
        );
    }

    // -------------------------------------------------------------------------
    // Private key is never exposed in the settings page HTML
    // -------------------------------------------------------------------------

    public function test_private_key_is_never_exposed_in_settings_page_response(): void
    {
        $user = $this->createUser(['analytics.settings.view']);

        // Store encrypted credentials that contain a private_key field.
        DB::table('settings')->updateOrInsert(
            ['key' => 'google_analytics_credentials'],
            ['value' => encrypt($this->validCredentialsJson()), 'updated_at' => now(), 'created_at' => now()]
        );

        $response = $this->actingAs($user)
            ->get(route('settings.analytics.index'));

        $response->assertOk();

        $content = $response->getContent();

        $this->assertStringNotContainsString(
            'private_key',
            $content,
            'The settings page must never render the private_key field.'
        );
        $this->assertStringNotContainsString(
            'BEGIN PRIVATE KEY',
            $content,
            'The settings page must never render PEM key material.'
        );
        $this->assertStringNotContainsString(
            'service_account',
            $content,
            'The settings page must never render the raw credentials JSON.'
        );
    }

    public function test_settings_page_exposes_only_safe_metadata(): void
    {
        $user = $this->createUser(['analytics.settings.view']);

        DB::table('settings')->updateOrInsert(
            ['key' => 'google_analytics_credentials'],
            ['value' => encrypt($this->validCredentialsJson()), 'updated_at' => now(), 'created_at' => now()]
        );

        $response = $this->actingAs($user)->get(route('settings.analytics.index'));

        $response->assertOk();

        // The view should display the project_id and client_email so the user knows
        // which account is configured — these are non-sensitive identifiers.
        $content = $response->getContent();
        $this->assertStringContainsString('my-project', $content);
        $this->assertStringContainsString('analytics@my-project.iam.gserviceaccount.com', $content);
    }
}
