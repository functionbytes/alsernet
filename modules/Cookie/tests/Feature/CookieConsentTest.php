<?php

namespace Modules\Cookie\Tests\Feature;

use Modules\Cookie\Models\CookieConsentLog;
use Modules\Cookie\Tests\TestCase;

class CookieConsentTest extends TestCase
{
    // -------------------------------------------------------------------------
    // POST /cookie/consent — store consent
    // -------------------------------------------------------------------------

    public function test_consent_can_be_logged_accept_all(): void
    {
        $this->postJson(route('cookie.consent.store'), [
            'action' => 'accept_all',
        ])->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseCount('cookie_consent_logs', 1);
        $this->assertDatabaseHas('cookie_consent_logs', ['action' => 'accept_all']);
    }

    public function test_consent_can_be_logged_reject_all(): void
    {
        $this->postJson(route('cookie.consent.store'), [
            'action' => 'reject_all',
        ])->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('cookie_consent_logs', ['action' => 'reject_all']);
    }

    public function test_consent_can_be_logged_custom(): void
    {
        $this->postJson(route('cookie.consent.store'), [
            'action' => 'custom',
            'accepted_categories' => ['analytics'],
        ])->assertOk();

        $log = CookieConsentLog::first();

        $this->assertNotNull($log);
        $this->assertEquals('custom', $log->action);
        $this->assertContains('analytics', $log->accepted_categories);
    }

    // -------------------------------------------------------------------------
    // GET /cookie/consent/status
    // -------------------------------------------------------------------------

    public function test_consent_status_returns_json(): void
    {
        $this->getJson(route('cookie.consent.status'))
            ->assertOk()
            ->assertJsonStructure(['has_consent']);
    }

    // -------------------------------------------------------------------------
    // Validation
    // -------------------------------------------------------------------------

    public function test_consent_requires_valid_action(): void
    {
        $this->postJson(route('cookie.consent.store'), [
            'action' => 'invalid_action',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['action']);

        $this->assertDatabaseEmpty('cookie_consent_logs');
    }

    public function test_consent_requires_action_field(): void
    {
        $this->postJson(route('cookie.consent.store'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['action']);
    }

    public function test_consent_rejects_invalid_categories(): void
    {
        $this->postJson(route('cookie.consent.store'), [
            'action' => 'custom',
            'accepted_categories' => ['invalid_category'],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['accepted_categories.0']);
    }

    // -------------------------------------------------------------------------
    // Admin logs view
    // -------------------------------------------------------------------------

    public function test_consent_logs_require_auth(): void
    {
        $this->get(route('settings.cookie.logs'))
            ->assertRedirect(route('auth.login'));
    }

    public function test_consent_logs_are_visible_to_admin(): void
    {
        $user = $this->createUser(['Cookie.settings.index']);

        CookieConsentLog::create([
            'session_id' => 'test-session-id-123456789012345678901',
            'ip_hash' => hash('sha256', '192.168.1.1'),
            'action' => 'accept_all',
            'accepted_categories' => [],
            'user_agent' => 'TestBrowser/1.0',
            'version' => '1.0',
        ]);

        $this->actingAs($user)
            ->get(route('settings.cookie.logs'))
            ->assertOk();
    }

    // -------------------------------------------------------------------------
    // IP hashing
    // -------------------------------------------------------------------------

    public function test_ip_is_hashed_not_stored_plainly(): void
    {
        $this->postJson(route('cookie.consent.store'), [
            'action' => 'accept_all',
        ])->assertOk();

        $log = CookieConsentLog::first();

        $this->assertNotNull($log);

        // The stored value must be a sha256 hex string (64 chars), not a raw IP
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $log->ip_hash);

        // Confirm it is NOT a plain IP address pattern
        $this->assertDoesNotMatchRegularExpression('/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/', $log->ip_hash);
    }

    // -------------------------------------------------------------------------
    // Duplicate prevention
    // -------------------------------------------------------------------------

    public function test_duplicate_consent_within_five_minutes_is_not_double_logged(): void
    {
        // First request
        $this->postJson(route('cookie.consent.store'), ['action' => 'accept_all'])
            ->assertOk();

        // Second request within the 5-minute window
        $this->postJson(route('cookie.consent.store'), ['action' => 'reject_all'])
            ->assertOk();

        // Only one record should exist because the IP is the same within the window
        $this->assertDatabaseCount('cookie_consent_logs', 1);
    }

    public function test_user_can_update_preferences_within_five_minutes(): void
    {
        $this->postJson(route('cookie.consent.store'), ['action' => 'accept_all'])
            ->assertOk();

        $this->postJson(route('cookie.consent.store'), [
            'action' => 'reject_all',
            'is_update' => true,
        ])->assertOk();

        $this->assertDatabaseCount('cookie_consent_logs', 2);
    }

    public function test_dedup_still_applies_without_is_update_flag(): void
    {
        $this->postJson(route('cookie.consent.store'), ['action' => 'accept_all'])
            ->assertOk();

        $this->postJson(route('cookie.consent.store'), ['action' => 'reject_all'])
            ->assertOk();

        $this->assertDatabaseCount('cookie_consent_logs', 1);
    }
}
