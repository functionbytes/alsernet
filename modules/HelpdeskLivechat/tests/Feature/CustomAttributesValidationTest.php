<?php

namespace Modules\HelpdeskLivechat\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Helpdesk\Models\ConversationStatus;
use Modules\HelpdeskLivechat\Database\Factories\WebFactory;
use Tests\TestCase;

/**
 * Regression tests for custom_attributes limits in StoreWidgetConversationRequest.
 *
 * Covers:
 *  - More than 20 keys → 422 with error on custom_attributes
 *  - A value exceeding 255 characters → 422 with error on custom_attributes.*
 *  - A non-string value → 422 with error on custom_attributes.*
 *  - Exactly 20 keys with short strings → passes validation (200)
 */
class CustomAttributesValidationTest extends TestCase
{
    use RefreshDatabase;

    private string $websiteToken;

    protected function setUp(): void
    {
        parent::setUp();

        ConversationStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1]
        );

        $this->websiteToken = WebFactory::new()->create()->website_token;
    }

    // -----------------------------------------------------------------------
    // Failure paths
    // -----------------------------------------------------------------------

    public function test_more_than_20_keys_returns_422(): void
    {
        $attributes = [];
        for ($i = 1; $i <= 21; $i++) {
            $attributes["key_{$i}"] = 'value';
        }

        $this->postJson(route('helpdesk-livechat.widget.conversation.store'), [
            'website_token' => $this->websiteToken,
            'custom_attributes' => $attributes,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['custom_attributes']);
    }

    public function test_value_exceeding_255_chars_returns_422(): void
    {
        $this->postJson(route('helpdesk-livechat.widget.conversation.store'), [
            'website_token' => $this->websiteToken,
            'custom_attributes' => [
                'key' => str_repeat('a', 256),
            ],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['custom_attributes.key']);
    }

    public function test_non_string_value_returns_422(): void
    {
        $this->postJson(route('helpdesk-livechat.widget.conversation.store'), [
            'website_token' => $this->websiteToken,
            'custom_attributes' => [
                'nested' => ['not', 'a', 'string'],
            ],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['custom_attributes.nested']);
    }

    // -----------------------------------------------------------------------
    // Happy path
    // -----------------------------------------------------------------------

    public function test_exactly_20_keys_with_valid_strings_passes_validation(): void
    {
        $attributes = [];
        for ($i = 1; $i <= 20; $i++) {
            $attributes["key_{$i}"] = "value_{$i}";
        }

        $response = $this->postJson(route('helpdesk-livechat.widget.conversation.store'), [
            'website_token' => $this->websiteToken,
            'email' => 'visitor@example.com',
            'custom_attributes' => $attributes,
        ]);

        // Validation should pass — 200 or 500 (backend error), but not 422.
        $this->assertNotEquals(422, $response->status(), 'Validation should pass for 20 attributes with valid strings');
    }

    public function test_null_custom_attributes_passes_validation(): void
    {
        $response = $this->postJson(route('helpdesk-livechat.widget.conversation.store'), [
            'website_token' => $this->websiteToken,
            'email' => 'visitor@example.com',
            'custom_attributes' => null,
        ]);

        $this->assertNotEquals(422, $response->status());
    }

    public function test_missing_custom_attributes_passes_validation(): void
    {
        $response = $this->postJson(route('helpdesk-livechat.widget.conversation.store'), [
            'website_token' => $this->websiteToken,
            'email' => 'visitor@example.com',
        ]);

        $this->assertNotEquals(422, $response->status());
    }
}
